<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SistemaException;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * Solicitud pública de restablecimiento de contraseña desde el login: avisa a
 * los administradores en lugar de enviar un correo.
 */
class PasswordResetRequestController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications)
    {
        try {
            $result = $notifications->requestPasswordReset((string) $request->email);
        } catch (SistemaException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de recuperación enviada correctamente. Un administrador revisará tu solicitud.',
        ] + $result);
    }
}
