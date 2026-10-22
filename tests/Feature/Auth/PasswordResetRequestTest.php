<?php

namespace Tests\Feature\Auth;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    public function test_registered_email_creates_a_single_pending_request(): void
    {
        $this->seedBaseData();
        $user = $this->adminUser();

        $this->postJson('/password-reset-request', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, Notification::where('type', 'password_reset')->where('requested_by', $user->id)->count());

        $this->postJson('/password-reset-request', ['email' => $user->email])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_invalid_or_unknown_email_is_rejected(): void
    {
        $this->postJson('/password-reset-request', ['email' => 'no-es-correo'])->assertStatus(422);
        $this->postJson('/password-reset-request', ['email' => 'nadie@example.com'])->assertStatus(422);
    }
}
