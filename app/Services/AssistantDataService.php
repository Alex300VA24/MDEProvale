<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\People;
use App\Models\Pecosa;
use App\Models\State;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resuelve consultas puntuales de datos del sistema (conteos, presidenta de un
 * comité, beneficiarios de un comité, pecosas de un periodo) usando únicamente
 * lecturas seguras a la base de datos. Devuelve texto plano ya formateado en
 * español, sin Markdown, o null cuando la pregunta no corresponde a este
 * servicio (para que el flujo caiga en la guía de navegación / modelo).
 */
class AssistantDataService
{
    private const MESES = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
        'julio' => 7, 'agosto' => 8, 'setiembre' => 9, 'septiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    ];

    private const NOMBRE_MES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function resolve(string $question): ?string
    {
        $q = Str::lower(Str::ascii(trim($question)));

        // Solicitud de reporte ("creame un reporte de pecosas de enero"): la
        // resuelve AssistantReportService, no este servicio de conteos.
        if (preg_match('/\b(reporte|reportes|informe|informes|listado|listados|padron|padrones)\b/', $q)
            && preg_match('/\b(crea|gener|elabor|arma|haz|hag|prepar|export|descarg|emit|saca|imprim|dame|damelo|quiero|necesito|obten)/', $q)) {
            return null;
        }

        return $this->presidentaDeComite($q)
            ?? $this->beneficiariosDeComite($q)
            ?? $this->comitesActivos($q)
            ?? $this->pecosasDelPeriodo($q)
            ?? $this->totalesGenerales($q);
    }

    /**
     * Resuelve la consulta usando los turnos anteriores para entender respuestas
     * breves como "el 375", "ese comité" o "¿y cuántos beneficiarios tiene?".
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    public function resolveConversation(array $messages): ?string
    {
        $userMessages = collect($messages)
            ->filter(fn (array $message) => ($message['role'] ?? null) === 'user')
            ->pluck('content')
            ->filter(fn ($content) => is_string($content) && trim($content) !== '')
            ->values()
            ->all();

        if ($userMessages === []) {
            return null;
        }

        $current = (string) end($userMessages);
        $currentNormalized = Str::lower(Str::ascii(trim($current)));

        $beneficiariesByPartner = $this->beneficiariesOfPartner($currentNormalized);
        if ($beneficiariesByPartner !== null) {
            return $beneficiariesByPartner;
        }

        $intent = $this->committeeIntent($currentNormalized, array_slice($userMessages, 0, -1));

        if ($intent === null && $this->looksLikeFollowUp($currentNormalized)) {
            foreach (array_reverse(array_slice($userMessages, 0, -1)) as $previous) {
                $intent = $this->committeeIntent(
                    Str::lower(Str::ascii((string) $previous)),
                    []
                );

                if ($intent !== null) {
                    break;
                }
            }
        }

        if ($intent === null) {
            return $this->resolve($current);
        }

        [$matches, $searchedTerm] = $this->committeeFromConversation($userMessages);

        if ($matches->count() > 1) {
            return $this->listarCoincidencias($matches);
        }

        $committee = $matches->first();
        if (! $committee) {
            return $searchedTerm
                ? $this->comiteNoEncontrado($searchedTerm)
                : $this->askForCommittee($intent);
        }

        return $intent === 'president'
            ? $this->presidentAnswer($committee)
            : $this->beneficiaryAnswer($committee);
    }

    /**
     * Busca el comité mencionado en el texto y lo devuelve solo si la
     * coincidencia es inequívoca. Pensado para el generador de reportes.
     */
    public function buscarComitePublico(string $texto): ?Association
    {
        $termino = $this->extraerNombreComite(Str::lower(Str::ascii($texto)));
        if (Str::length($termino) < 3) {
            return null;
        }

        $coincidencias = $this->coincidenciasComite($termino);

        return $coincidencias->count() === 1 ? $coincidencias->first() : null;
    }

    /**
     * Resuelve el periodo (año, mes) mencionado en el texto.
     *
     * @return array{0:int,1:int,2:string,3:bool} [anio, mes, etiqueta, mencionado]
     */
    public function periodoPublico(string $texto): array
    {
        $q = Str::lower(Str::ascii($texto));
        [$anio, $mes, $etiqueta] = $this->resolverPeriodo($q);
        $mencionado = (bool) preg_match('/\bmes\b/', $q) || $this->contieneMes($q);

        return [$anio, $mes, $etiqueta, $mencionado];
    }

    private function presidentaDeComite(string $q): ?string
    {
        if (! str_contains($q, 'presidenta') || ! $this->mencionaComite($q)) {
            return null;
        }

        $termino = $this->extraerNombreComite($q);
        if (Str::length($termino) < 3) {
            return null;
        }

        $coincidencias = $this->coincidenciasComite($termino);
        if ($coincidencias->count() > 1) {
            return $this->listarCoincidencias($coincidencias);
        }

        $comite = $coincidencias->first();
        if (! $comite) {
            return $this->comiteNoEncontrado($termino);
        }

        return $this->presidentAnswer($comite);
    }

    private function beneficiariosDeComite(string $q): ?string
    {
        if (! str_contains($q, 'beneficiari') || ! $this->mencionaComite($q)) {
            return null;
        }

        // "el comité con más beneficiarios" es una consulta de ranking: la
        // atiende la guía de navegación, no este servicio.
        if (preg_match('/\b(mas|mayor|menos|menor|top|ranking)\b/', $q)) {
            return null;
        }

        $termino = $this->extraerNombreComite($q);
        if (Str::length($termino) < 3) {
            return null;
        }

        $coincidencias = $this->coincidenciasComite($termino);
        if ($coincidencias->count() > 1) {
            return $this->listarCoincidencias($coincidencias);
        }

        $comite = $coincidencias->first();
        if (! $comite) {
            return $this->comiteNoEncontrado($termino);
        }

        return $this->beneficiaryAnswer($comite);
    }

    private function comitesActivos(string $q): ?string
    {
        if (! $this->mencionaComite($q)) {
            return null;
        }

        $pideConteo = (bool) preg_match('/\b(cuantos|cuantas|numero|cantidad|total|hay|existen|tenemos)\b/', $q);
        $pideActivos = (bool) preg_match('/\b(activos?|activas?|vigentes?|habilitados?|habilitadas?)\b/', $q);

        if (! $pideConteo && ! $pideActivos) {
            return null;
        }

        $total = Association::count();
        $vigenteId = State::idFor(State::CURRENT);
        $activos = $vigenteId ? Association::where('state_id', $vigenteId)->count() : $total;

        return "Comités activos:\n\n"
            ."- Comités activos (estado vigente): {$activos}\n"
            ."- Total de comités registrados: {$total}\n\n"
            ."El estado de un comité no cambia mes a mes; el conteo corresponde a la fecha actual ("
            .now()->format('d/m/Y').").";
    }

    private function pecosasDelPeriodo(string $q): ?string
    {
        if (! str_contains($q, 'pecosa')) {
            return null;
        }

        $pideConteo = (bool) preg_match('/\b(cuantas|cuantos|numero|cantidad|total|generad|generada|generadas|emitid|emitidas|registrad|registradas|hechas|creadas|van|llevamos|hay)\b/', $q);
        $mencionaPeriodo = (bool) preg_match('/\bmes\b/', $q) || $this->contieneMes($q);

        if (! $pideConteo && ! $mencionaPeriodo) {
            return null;
        }

        [$anio, $mes, $etiqueta] = $this->resolverPeriodo($q);
        [$inicio, $fin] = Pecosa::deliveryPeriodRange($anio, $mes);

        $total = Pecosa::whereBetween('delivery_date', [
            $inicio->toDateString(), $fin->toDateString(),
        ])->count();

        return "Pecosas de {$etiqueta}:\n\n"
            ."- Pecosas generadas: {$total}\n"
            ."- Periodo considerado: {$inicio->format('d/m/Y')} al {$fin->format('d/m/Y')}";
    }

    private function totalesGenerales(string $q): ?string
    {
        if (! preg_match('/\b(cuantos|cuantas|cantidad|numero|total)\b/', $q)) {
            return null;
        }

        if (str_contains($q, 'beneficiari') && ! $this->mencionaComite($q)) {
            $total = Beneficiarie::count();

            return "Beneficiarios registrados:\n\n- Total de beneficiarios en el sistema: {$total}";
        }

        if (str_contains($q, 'socio') || str_contains($q, 'socia')) {
            $total = Partner::count();

            return "Socios registrados:\n\n- Total de socios en el sistema: {$total}";
        }

        return null;
    }

    private function mencionaComite(string $q): bool
    {
        return str_contains($q, 'comite')
            || str_contains($q, 'club de madres')
            || str_contains($q, 'clubes')
            || str_contains($q, 'club');
    }

    private function contieneMes(string $q): bool
    {
        foreach (array_keys(self::MESES) as $nombre) {
            if (str_contains($q, $nombre)) {
                return true;
            }
        }

        return false;
    }

    protected function extraerNombreComite(string $q): string
    {
        $texto = preg_replace('/[?!.,;:"\']/u', ' ', $q);

        foreach (['club de madres', 'comite', 'club'] as $marcador) {
            $pos = mb_strrpos($texto, $marcador);
            if ($pos !== false) {
                $texto = mb_substr($texto, $pos + mb_strlen($marcador));
                break;
            }
        }

        $stop = [
            'de', 'del', 'la', 'el', 'los', 'las', 'con', 'mas', 'menos', 'tiene', 'tienen',
            'hay', 'es', 'son', 'quien', 'quienes', 'cual', 'cuales', 'cuantos', 'cuantas',
            'beneficiarios', 'beneficiario', 'beneficiarias', 'beneficiaria', 'presidenta',
            'llamado', 'llamada', 'nombre', 'y', 'a', 'se', 'que', 'digas', 'dime',
            'indica', 'indicame', 'este', 'esta', 'ese', 'esa', 'un', 'una',
        ];

        $tokens = array_values(array_filter(
            explode(' ', trim($texto)),
            fn ($palabra) => $palabra !== '' && ! in_array($palabra, $stop, true)
        ));

        return trim(implode(' ', $tokens));
    }

    protected function coincidenciasComite(string $termino): Collection
    {
        $norm = fn ($valor) => Str::lower(Str::ascii((string) $valor));
        $objetivo = $norm($termino);

        $comites = Association::query()->get(['id', 'name', 'code', 'state_id']);

        $exacta = $comites->first(
            fn ($comite) => $norm($comite->name) === $objetivo || $norm($comite->code) === $objetivo
        );
        if ($exacta) {
            return collect([$exacta]);
        }

        return $comites->filter(function ($comite) use ($norm, $objetivo) {
            $nombre = $norm($comite->name);

            return $nombre !== '' && (str_contains($nombre, $objetivo) || str_contains($objetivo, $nombre));
        })->values();
    }

    private function listarCoincidencias(Collection $coincidencias): string
    {
        $lineas = $coincidencias->take(8)
            ->map(fn ($comite) => "- {$comite->name}".($comite->code ? " (código {$comite->code})" : ''))
            ->implode("\n");

        return "Encontré varios comités que coinciden:\n\n{$lineas}\n\n"
            ."Indícame el nombre completo o el código del comité que necesitas.";
    }

    private function comiteNoEncontrado(string $termino): string
    {
        return "No encontré un comité que coincida con \"{$termino}\".\n\n"
            .'¿Puedes indicarme el nombre completo o el código del comité?';
    }

    /**
     * @param  list<string>  $previousMessages
     */
    private function committeeIntent(string $question, array $previousMessages): ?string
    {
        if (preg_match('/\b(presidenta|presidente|preside|presidir)\b/', $question)) {
            return 'president';
        }

        if (! str_contains($question, 'beneficiari')) {
            return null;
        }

        if (preg_match('/\b(mas|mayor|menos|menor|top|ranking)\b/', $question)) {
            return null;
        }

        if ($this->mencionaComite($question)) {
            return 'beneficiaries';
        }

        foreach ($previousMessages as $previous) {
            if ($this->mencionaComite(Str::lower(Str::ascii((string) $previous)))) {
                return 'beneficiaries';
            }
        }

        return null;
    }

    private function looksLikeFollowUp(string $question): bool
    {
        return str_word_count($question) <= 8
            || (bool) preg_match('/\b(ese|esa|este|esta|mismo|misma|su|codigo)\b/', $question);
    }

    /**
     * @param  list<string>  $userMessages
     * @return array{0:Collection,1:?string}
     */
    private function committeeFromConversation(array $userMessages): array
    {
        $searchedTerm = null;

        foreach (array_reverse($userMessages) as $message) {
            $normalized = Str::lower(Str::ascii((string) $message));
            $term = $this->extraerNombreComite($normalized);

            if (Str::length($term) < 2) {
                continue;
            }

            $matches = $this->coincidenciasComite($term);
            if ($matches->isNotEmpty()) {
                return [$matches, $term];
            }

            if ($searchedTerm === null && ($this->mencionaComite($normalized) || $this->looksLikeFollowUp($normalized))) {
                $searchedTerm = $term;
            }
        }

        return [collect(), $searchedTerm];
    }

    protected function presidentAnswer(Association $committee): string
    {
        Association::hydratePresidents(collect([$committee]));
        $name = $committee->president_name ?? $committee->getPresidentName();
        $title = "Presidenta del comité {$committee->name}:";

        if (! $name) {
            return $title."\n\n"
                ."- Este comité aún no tiene una presidenta asignada.\n"
                ."- Para designarla: abre Comités y Reconocimientos, ubica el comité y usa Asignar Presidenta.";
        }

        return $title."\n\n"
            ."- Comité: {$committee->name}".($committee->code ? " (código {$committee->code})" : '')."\n"
            ."- Presidenta: {$name}";
    }

    protected function beneficiaryAnswer(Association $committee): string
    {
        $partners = Partner::where('association_id', $committee->id)->count();
        $beneficiaries = Beneficiarie::whereHas(
            'partner',
            fn ($partner) => $partner->where('association_id', $committee->id)
        )->count();

        return "Beneficiarios del comité {$committee->name}:\n\n"
            ."- Beneficiarios registrados: {$beneficiaries}\n"
            ."- Socias titulares: {$partners}\n\n"
            ."Para el detalle nominal pídeme: crea un reporte de beneficiarios del comité {$committee->name}.";
    }

    private function beneficiariesOfPartner(string $question): ?string
    {
        if (! str_contains($question, 'beneficiari') || $this->mencionaComite($question)) {
            return null;
        }

        $identity = $this->extractPartnerIdentity($question);
        if (Str::length($identity) < 3) {
            return null;
        }

        $partners = $this->matchingPartners($identity);
        if ($partners->isEmpty()) {
            return "No encontré una socia titular que coincida con \"{$identity}\".\n\n"
                .'¿Puedes indicarme su nombre completo o DNI?';
        }

        $people = $partners
            ->groupBy(fn (Partner $partner) => $partner->person_id ?: $this->personName($partner->people))
            ->values();

        if ($people->count() > 1) {
            $options = $people->take(8)->map(function (Collection $personPartners) {
                $partner = $personPartners->first();
                $committee = $partner->association?->name;

                return '- '.$this->personName($partner->people).($committee ? " — Comité {$committee}" : '');
            })->implode("\n");

            return "Encontré varias socias que coinciden:\n\n{$options}\n\n"
                .'Indícame el nombre completo o DNI de la socia.';
        }

        return $this->partnerBeneficiariesAnswer($people->first());
    }

    protected function extractPartnerIdentity(string $question): string
    {
        if (! preg_match('/beneficiari(?:o|a|os|as)?\s+(?:de|del)\s+(.+)$/u', $question, $match)) {
            return '';
        }

        $identity = preg_replace('/^(?:la|el)\s+(?:socia|socio|presidenta|presidente)\s+/u', '', trim($match[1]));
        $identity = preg_replace('/\b(?:por favor|registrados?|registradas?)\b/u', ' ', $identity);
        $identity = preg_replace('/[^a-z0-9\s]/u', ' ', $identity);

        return trim((string) preg_replace('/\s+/u', ' ', $identity));
    }

    protected function matchingPartners(string $identity): Collection
    {
        $partners = Partner::query()
            ->with([
                'people:id,names,father_lastname,mother_lastname,dni',
                'association:id,name,code',
                'beneficiaries.person:id,names,father_lastname,mother_lastname',
                'beneficiaries.relationship:id,title',
            ])
            ->whereHas('people', fn ($people) => $people->searchIdentity($identity))
            ->get();

        $normalizedIdentity = Str::lower(Str::ascii($identity));
        $exact = $partners->filter(
            fn (Partner $partner) => Str::lower(Str::ascii($this->personName($partner->people))) === $normalizedIdentity
                || (string) $partner->people?->dni === $identity
        );

        return $exact->isNotEmpty() ? $exact->values() : $partners;
    }

    protected function partnerBeneficiariesAnswer(Collection $partners): string
    {
        /** @var Partner $first */
        $first = $partners->first();
        $owner = $this->personName($first->people);
        $beneficiaries = $partners
            ->flatMap(fn (Partner $partner) => $partner->beneficiaries->map(function (Beneficiarie $beneficiary) use ($partner) {
                return [
                    'name' => $this->personName($beneficiary->person),
                    'relationship' => $beneficiary->relationship?->title,
                    'committee' => $partner->association?->name,
                ];
            }))
            ->unique(fn (array $beneficiary) => implode('|', array_map(
                static fn ($value) => (string) $value,
                $beneficiary
            )))
            ->values();

        if ($beneficiaries->isEmpty()) {
            return "Beneficiarios de {$owner}:\n\n- No tiene beneficiarios registrados.";
        }

        $lines = $beneficiaries->map(function (array $beneficiary, int $index) {
            $detail = array_filter([$beneficiary['relationship'], $beneficiary['committee']]);

            return ($index + 1).'. '.$beneficiary['name'].($detail ? ' — '.implode(' · ', $detail) : '');
        })->implode("\n");

        return "Beneficiarios de {$owner}:\n\n{$lines}\n\n- Total: {$beneficiaries->count()}";
    }

    private function personName(?People $person): string
    {
        if (! $person) {
            return 'Sin nombre';
        }

        return trim(implode(' ', array_filter([
            $person->names,
            $person->father_lastname,
            $person->mother_lastname,
        ])));
    }

    private function askForCommittee(string $intent): string
    {
        $detail = $intent === 'president' ? 'consultar su presidenta' : 'consultar sus beneficiarios';

        return "Necesito identificar el comité para {$detail}.\n\n"
            .'¿Cuál es su nombre completo o código?';
    }

    /**
     * @return array{0:int,1:int,2:string}
     */
    private function resolverPeriodo(string $q): array
    {
        $ahora = now();
        $anio = $ahora->year;
        $mes = $ahora->month;

        if (preg_match('/mes pasado|mes anterior/', $q)) {
            $ref = $ahora->copy()->subMonthNoOverflow();
            $anio = $ref->year;
            $mes = $ref->month;
        }

        foreach (self::MESES as $nombre => $numero) {
            if (str_contains($q, $nombre)) {
                $mes = $numero;
                if (preg_match('/\b(20\d{2})\b/', $q, $coincide)) {
                    $anio = (int) $coincide[1];
                } elseif ($numero > $ahora->month) {
                    $anio = $ahora->year - 1;
                }
                break;
            }
        }

        return [$anio, $mes, self::NOMBRE_MES[$mes]." de {$anio}"];
    }
}
