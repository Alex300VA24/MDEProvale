<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                    'session_expired' => true,
                    'redirect' => route('login'),
                ], 419);
            }

            $request->session()->flash('session_expired', true);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', ['expired' => 1]);
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'No autenticado.',
                'session_expired' => true,
                'redirect' => route('login'),
            ], 401);
        }

        return redirect()->guest(route('login'))->with('session_expired', true);
    }
}
