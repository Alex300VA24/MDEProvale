<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Almacén clave-valor de parámetros del sistema editables por el administrador.
 * Las lecturas se cachean 60 s y toleran que la tabla aún no exista (devuelven
 * el valor por defecto) para no romper despliegues o pruebas sin migrar.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /**
     * Devuelve el valor de una clave o el valor por defecto indicado.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = Cache::remember("setting:{$key}", 60, fn () => static::query()->find($key)?->value);
        } catch (\Throwable) {
            return $default;
        }

        return $value ?? $default;
    }

    /**
     * Crea o actualiza una clave e invalida su caché.
     */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget("setting:{$key}");
    }
}
