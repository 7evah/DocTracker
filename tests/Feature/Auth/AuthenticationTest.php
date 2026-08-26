<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        /*
        | Rendered, not merely registered. assertHasErrors() alone passed for
        | a long time while the page showed the user nothing at all: Flux
        | derives the field it reports errors for from wire:model, but the
        | explicit name="email" on the input overrode that, so it looked for
        | "email" while Livewire had recorded the failure under "form.email".
        | A wrong password simply sat there silently.
        */
        $component->assertSee(trans('auth.failed'));

        $this->assertGuest();
    }

    /** Deactivated accounts must not obtain a session even with valid credentials. */
    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component->assertHasErrors('form.email');

        $this->assertGuest();
    }

    public function test_suspended_users_can_not_authenticate(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertGuest();
    }

    public function test_successful_login_records_last_activity(): void
    {
        $user = User::factory()->create(['last_active_at' => null]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertNotNull($user->fresh()->last_active_at);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
