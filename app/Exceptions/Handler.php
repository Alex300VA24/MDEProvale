<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419 || ! ($e->getPrevious() instanceof TokenMismatchException)) {
                return null;
            }

            // Un 419 puede darse con la sesión intacta (token CSRF vencido por
            // token regenerado en otra pestaña/intento de login). En ese caso el
            // usuario sigue autenticado y NO debe salir al login: se refresca el
            // token. Solo si la sesión realmente expiró se trata como tal.
            if ($this->isRequestAuthenticated($request)) {
                return $this->staleCsrfResponse($request);
            }

            return $this->expiredSessionResponse($request);
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $loginUrl = $this->expiredSessionLoginUrl($request);

        if ($request->inertia()) {
            return Inertia::location($loginUrl);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'No autenticado.',
                'session_expired' => true,
                'redirect' => $loginUrl,
            ], 401);
        }

        return redirect()->guest($loginUrl)->with('session_expired', true);
    }

    private function isRequestAuthenticated($request): bool
    {
        try {
            return auth()->check();
        } catch (\Throwable $e) {
            // Sin sesión disponible (p. ej. en tests) se asume expirada.
            return false;
        }
    }

    private function staleCsrfResponse($request)
    {
        if ($request->inertia()) {
            return Inertia::location($request->fullUrl());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Tu token de seguridad venció. Vuelve a intentar la acción.',
                'csrf_expired' => true,
            ], 419);
        }

        return redirect()->back()->withInput()->with(
            'csrf_expired',
            'Tu token de seguridad venció. Reintenta la acción.'
        );
    }

    private function expiredSessionResponse($request)
    {
        $loginUrl = $this->expiredSessionLoginUrl($request);

        if ($request->inertia()) {
            return Inertia::location($loginUrl);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                'session_expired' => true,
                'redirect' => $loginUrl,
            ], 419);
        }

        $request->session()->flash('session_expired', true);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($loginUrl);
    }

    private function expiredSessionLoginUrl($request): string
    {
        $route = $request->is('portal-presidentas*')
            ? 'president.login'
            : 'login';

        return route($route, ['expired' => 1]);
    }
}
