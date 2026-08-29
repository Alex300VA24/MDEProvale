<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, SeedsBaseData;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $this->seedBaseData();

        $response = $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $this->seedBaseData();

        $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_xampp_spa_api_request_keeps_the_authenticated_session()
    {
        $this->seedBaseData();

        $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'password',
        ])->assertRedirect(RouteServiceProvider::HOME);

        $this->withHeaders([
            'Origin' => 'http://localhost',
            'Referer' => 'http://localhost/MDEProvale/public/dashboard',
        ])->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('username', 'testadmin');
    }
}
