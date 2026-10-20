<?php

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/**
 * El dashboard consume /api/* solo desde el SPA propio, con la misma sesión
 * web. Sanctum decide "stateful" por Origin/Referer; si falta o no coincide
 * con SANCTUM_STATEFUL_DOMAINS la API no ve la sesión (401) aunque el usuario
 * siga autenticado en las páginas web. Aquí siempre se usa la sesión.
 */
class AlwaysStatefulApi extends EnsureFrontendRequestsAreStateful
{
    public static function fromFrontend($request)
    {
        return true;
    }
}
