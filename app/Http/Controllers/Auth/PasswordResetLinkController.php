<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $user = \App\Models\User::where('email', $request->email)->first();
            if ($user) {
                Notification::create([
                    'type' => 'password_reset',
                    'title' => 'Solicitud de recuperación de contraseña',
                    'description' => 'El usuario ' . $user->email . ' solicitó restablecer su contraseña.',
                    'status' => 'pending',
                    'user_id' => $user->id,
                    'requested_by' => $user->id,
                    'requested_at' => now(),
                    'metadata' => [
                        'email' => $user->email,
                        'username' => $user->username,
                    ],
                ]);
            }
            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
