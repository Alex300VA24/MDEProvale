<?php

namespace Tests\Feature\Auth;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionExpirationResponseTest extends TestCase
{
    public function test_expired_inertia_logout_redirects_to_president_login(): void
    {
        $request = Request::create('/portal-presidentas/logout', 'POST');
        $request->headers->set('X-Inertia', 'true');
        $this->app->instance('request', $request);

        $response = app(Handler::class)->render($request, new TokenMismatchException());

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            route('president.login', ['expired' => 1]),
            $response->headers->get('X-Inertia-Location')
        );
    }

    public function test_expired_dashboard_json_request_returns_recovery_details(): void
    {
        $request = Request::create('/dashboard-api/inicio', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(Handler::class)->render($request, new TokenMismatchException());

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame(true, $response->getData(true)['session_expired']);
        $this->assertSame(
            route('login', ['expired' => 1]),
            $response->getData(true)['redirect']
        );
    }

    public function test_stale_csrf_with_active_session_is_not_treated_as_expired(): void
    {
        // Simula una sesión válida (usuario autenticado): el 419 solo significa
        // token CSRF desincronizado, no una expiración real de sesión.
        Auth::shouldReceive('check')->andReturn(true);

        $request = Request::create('/portal-presidentas/logout', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = app(Handler::class)->render($request, new TokenMismatchException());

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame(true, $response->getData(true)['csrf_expired']);
        $this->assertArrayNotHasKey('session_expired', $response->getData(true));
    }
}
