<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
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

        Association::hydratePresidents(collect([$comite]));
        $nombre = $comite->president_name ?? $comite->getPresidentName();

        $titulo = "Presidenta del comité {$comite->name}:";

        if (! $nombre) {
            return $titulo."\n\n"
                ."- Este comité aún no tiene una presidenta asignada.\n"
                ."- Para designarla: abre Comités y Reconocimientos, ubica el comité y usa Asignar Presidenta.";
        }

        return $titulo."\n\n"
            ."- Comité: {$comite->name}".($comite->code ? " (código {$comite->code})" : '')."\n"
            ."- Presidenta: {$nombre}";
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

        $socias = Partner::where('association_id', $comite->id)->count();
        $beneficiarios = Beneficiarie::whereHas(
            'partner',
            fn ($partner) => $partner->where('association_id', $comite->id)
        )->count();

        return "Beneficiarios del comité {$comite->name}:\n\n"
            ."- Beneficiarios registrados: {$beneficiarios}\n"
            ."- Socias titulares: {$socias}\n\n"
            ."Para el detalle nominal pídeme: crea un reporte de beneficiarios del comité {$comite->name}.";
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

    private function extraerNombreComite(string $q): string
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
            'llamado', 'llamada', 'nombre', 'y', 'a', 'se', 'que', 'me', 'digas', 'dime',
            'indica', 'indicame', 'este', 'esta', 'ese', 'esa', 'un', 'una',
        ];

        $tokens = array_values(array_filter(
            explode(' ', trim($texto)),
            fn ($palabra) => $palabra !== '' && ! in_array($palabra, $stop, true)
        ));

        return trim(implode(' ', $tokens));
    }

    private function coincidenciasComite(string $termino): Collection
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
            ."- Revisa la ortografía del nombre.\n"
            ."- También puedes indicarme el código del comité.\n"
            ."- Para ver la lista completa abre Comités y Reconocimientos.";
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
