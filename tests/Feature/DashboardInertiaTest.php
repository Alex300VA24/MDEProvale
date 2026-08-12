<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class DashboardInertiaTest extends TestCase
{
    use SeedsBaseData;

    public function test_dashboard_renders_inertia_shell(): void
    {
        $this->seedBaseData();

        $response = $this->actingAs($this->adminUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('auth.user.id')
                ->has('auth.user.name')
                ->has('auth.user.rol_id')
                ->has('modules')
            );

        // El cliente @inertiajs/react v3 solo lee la página inicial desde el
        // <script data-page="app" type="application/json">. Sin esto, /dashboard
        // lanza "Cannot read properties of null (reading 'component')".
        $response->assertSee('data-page="app"', false)
            ->assertSee('type="application/json"', false);
    }
}
