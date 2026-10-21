<?php

namespace App\Services;

use App\Exceptions\ReniecException;
use App\Models\People;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonService
{
    private const PHOTO_TTL_MINUTES = 15;

    public function __construct(
        private PersonRepositoryInterface $personRepo,
        private CatalogRepositoryInterface $catalogs,
        private ReniecService $reniec
    ) {
    }

    /** Listado de personas con los sectores para el filtro. */
    public function indexData(array $filters): array
    {
        return [
            'people' => $this->personRepo->paginateFiltered($filters),
            'placeSectors' => $this->catalogs->placeSectors(['id', 'place_id', 'sector_id']),
        ];
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->personRepo->paginateFiltered($filters, $perPage, ['placeSector.place:id,title', 'placeSector.sector:id,title']);
    }

    public function placeSectors()
    {
        return $this->catalogs->placeSectors();
    }

    public function detail(People $person): People
    {
        return $this->personRepo->loadPlaceSector($person);
    }

    public function create(array $data): People
    {
        return $this->personRepo->createPerson($data);
    }

    /**
     * Consulta el DNI en RENIEC. La foto no se devuelve: queda cifrada en caché
     * bajo un token atado al usuario y al DNI.
     *
     * @throws ReniecException
     */
    public function lookupReniec(string $dni, string $userId): array
    {
        $person = $this->reniec->consultar($dni);

        $photo = $person['photo'] ?? null;
        unset($person['photo']);

        if ($photo) {
            $photoToken = (string) Str::uuid();
            Cache::put($this->photoKey($photoToken), [
                'user_id' => $userId,
                'dni' => $dni,
                'encrypted_photo' => Crypt::encryptString($photo),
            ], now()->addMinutes(self::PHOTO_TTL_MINUTES));
            $person['photo_token'] = $photoToken;
        }

        return $person;
    }

    /** @throws ReniecException */
    public function reniecPhoto(People $person): ?string
    {
        return $this->reniec->consultar($person->dni)['photo'] ?? null;
    }

    /**
     * Registra una persona; si trae `reniec_photo_token` valida y adjunta la foto consultada.
     *
     * @throws ValidationException si el token expiró, no pertenece al usuario/DNI o no se puede descifrar
     */
    public function register(array $data, string $userId): People
    {
        $photoToken = $data['reniec_photo_token'] ?? null;
        unset($data['reniec_photo_token']);

        if ($photoToken) {
            $data['reniec_photo'] = $this->claimPhoto($photoToken, $userId, (string) $data['dni']);
        }

        $person = $this->personRepo->createPerson($data);

        if ($photoToken) {
            Cache::forget($this->photoKey($photoToken));
        }

        return $person;
    }

    public function update(People $person, array $data): bool
    {
        return $this->personRepo->updatePerson($person, $data);
    }

    /**
     * @throws \DomainException si la persona está vinculada a un socio o beneficiario
     */
    public function delete(People $person): void
    {
        if ($this->personRepo->isLinkedToPartnerOrBeneficiary($person)) {
            throw new \DomainException('No se puede eliminar la persona porque está asociada a un socio o beneficiario');
        }

        $this->personRepo->deletePerson($person);
    }

    private function claimPhoto(string $photoToken, string $userId, string $dni): string
    {
        $cached = Cache::get($this->photoKey($photoToken));
        $belongsToRequest = is_array($cached)
            && hash_equals((string) ($cached['user_id'] ?? ''), $userId)
            && hash_equals((string) ($cached['dni'] ?? ''), $dni);

        if (! $belongsToRequest) {
            throw ValidationException::withMessages([
                'reniec_photo_token' => 'La foto de RENIEC expiró. Consulte nuevamente el DNI.',
            ]);
        }

        try {
            return Crypt::decryptString($cached['encrypted_photo']);
        } catch (DecryptException) {
            throw ValidationException::withMessages([
                'reniec_photo_token' => 'No se pudo validar la foto de RENIEC. Consulte nuevamente el DNI.',
            ]);
        }
    }

    private function photoKey(string $token): string
    {
        return "reniec-photo:{$token}";
    }
}
