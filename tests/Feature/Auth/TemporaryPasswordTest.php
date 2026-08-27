<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\TemporaryPasswordIssued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * The forgot-password flow mails a one-off password instead of a reset link,
 * and the account is held on the change-password screen until it picks a real
 * one (§4).
 */
class TemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'karim@yopmail.com',
            'password' => 'original-password',
            'status' => UserStatus::Active,
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Issuing
    |--------------------------------------------------------------------------
    */

    public function test_requesting_one_mails_a_temporary_password(): void
    {
        Notification::fake();

        $user = $this->user();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendTemporaryPassword')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, TemporaryPasswordIssued::class);

        $this->assertNotNull($user->fresh()->temporary_password);
    }

    /**
     * The whole reason the temporary password is stored beside the real one:
     * otherwise submitting somebody else's address on a public form would
     * cost them access to their own account.
     */
    public function test_issuing_one_leaves_the_existing_password_working(): void
    {
        Notification::fake();

        $user = $this->user();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendTemporaryPassword');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    /** The form must not become a way of discovering who holds an account. */
    public function test_an_unknown_address_is_answered_the_same_way(): void
    {
        Notification::fake();

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'nobody@yopmail.com')
            ->call('sendTemporaryPassword')
            ->assertHasNoErrors();

        Notification::assertNothingSent();
    }

    /** A credential they could not use anyway is just a credential in an inbox. */
    public function test_a_deactivated_account_is_sent_nothing(): void
    {
        Notification::fake();

        $user = $this->user(['status' => UserStatus::Inactive]);

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendTemporaryPassword');

        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->temporary_password);
    }

    /*
    |--------------------------------------------------------------------------
    | Signing in with it
    |--------------------------------------------------------------------------
    */

    public function test_a_temporary_password_signs_in_and_forces_a_change(): void
    {
        $user = $this->user();
        $temporary = $user->issueTemporaryPassword();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', $temporary)
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->must_change_password);

        // One sign-in, not a second standing credential.
        $this->assertNull($user->fresh()->temporary_password);
    }

    public function test_the_real_password_still_signs_in_while_one_is_outstanding(): void
    {
        $user = $this->user();
        $user->issueTemporaryPassword();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'original-password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        // Signing in normally is not a forced-change situation.
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_an_expired_temporary_password_is_refused(): void
    {
        $user = $this->user();
        $temporary = $user->issueTemporaryPassword();

        $user->forceFill([
            'temporary_password_expires_at' => now()->subMinute(),
        ])->save();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', $temporary)
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | The forced change
    |--------------------------------------------------------------------------
    */

    public function test_an_account_owing_a_change_is_held_on_that_screen(): void
    {
        $user = $this->user(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertRedirect(route('password.change'));
    }

    /** Being stuck in the app with no way out would be worse than the block. */
    public function test_the_change_screen_and_logout_stay_reachable(): void
    {
        $user = $this->user(['must_change_password' => true]);

        $this->actingAs($user)->get(route('password.change'))->assertOk();
        $this->actingAs($user)->post(route('logout'))->assertRedirect();
    }

    public function test_choosing_a_password_releases_the_account(): void
    {
        $user = $this->user(['must_change_password' => true]);

        $this->actingAs($user);

        Volt::test('pages.auth.change-password')
            ->set('password', 'un-nouveau-mot-de-passe')
            ->set('password_confirmation', 'un-nouveau-mot-de-passe')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('un-nouveau-mot-de-passe', $user->password));

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = $this->user(['must_change_password' => true]);

        $this->actingAs($user);

        Volt::test('pages.auth.change-password')
            ->set('password', 'un-nouveau-mot-de-passe')
            ->set('password_confirmation', 'quelque-chose-dautre')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    /** An account with nothing outstanding is not diverted. */
    public function test_a_normal_account_is_not_diverted(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
