<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Youssef Amrani')
            ->set('email', 'youssef.amrani@docflow.test')
            ->set('department', 'Tuyauterie')
            ->set('job_title', 'Ingénieur tuyauterie')
            ->set('phone', '+212 522 00 00 03')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Youssef Amrani', $user->name);
        $this->assertSame('youssef.amrani@docflow.test', $user->email);
        $this->assertSame('Tuyauterie', $user->department);
        $this->assertSame('Ingénieur tuyauterie', $user->job_title);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * `status` is administrative and excluded from $fillable, so a crafted
     * request must not be able to reactivate a suspended account (§39).
     *
     * Model::preventSilentlyDiscardingAttributes() is enabled outside
     * production, so the attempt throws rather than being quietly dropped —
     * the same input is a no-op in production, safe either way.
     */
    public function test_status_can_not_be_mass_assigned_from_the_profile_form(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->assertThrows(
            fn () => $user->fill(['status' => UserStatus::Active->value, 'name' => 'Tentative'])->save(),
            MassAssignmentException::class,
        );

        $this->assertSame(UserStatus::Suspended, $user->fresh()->status);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'nouveau-mot-de-passe-solide')
            ->set('password_confirmation', 'nouveau-mot-de-passe-solide')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-mot-de-passe-solide', $user->fresh()->password));
    }

    public function test_correct_current_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'wrong-password')
            ->set('password', 'nouveau-mot-de-passe-solide')
            ->set('password_confirmation', 'nouveau-mot-de-passe-solide')
            ->call('updatePassword')
            ->assertHasErrors('current_password');
    }
}
