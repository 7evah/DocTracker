<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index as NotificationIndex;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCentreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(string $name = 'Utilisateur'): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'name' => $name]);
        $user->assignRole(UserRole::Engineer->value);

        return $user;
    }

    /**
     * Writes a notification row directly. The delivery side is already covered
     * by the review, approval and task suites; what matters here is the
     * reading UI, so the payload is built rather than dispatched.
     */
    private function notify(User $user, string $message = 'Une notification', ?string $url = null, bool $read = false): string
    {
        $id = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\ReviewAssigned',
            'data' => [
                'type' => 'review.assigned',
                'icon' => 'eye',
                'color' => 'sky',
                'message' => $message,
                'url' => $url,
            ],
            'read_at' => $read ? now() : null,
        ]);

        return $id;
    }

    /*
    |--------------------------------------------------------------------------
    | Reading
    |--------------------------------------------------------------------------
    */

    public function test_the_centre_lists_the_users_notifications(): void
    {
        $user = $this->user();
        $this->notify($user, 'Le document ME-1023 vous a été affecté');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Le document ME-1023 vous a été affecté');
    }

    /** The centre must never surface another person's notifications. */
    public function test_it_only_shows_your_own_notifications(): void
    {
        $mine = $this->user('Moi');
        $theirs = $this->user('Quelqu’un d’autre');

        $this->notify($mine, 'Ma notification');
        $this->notify($theirs, 'Leur notification');

        Livewire::actingAs($mine)
            ->test(NotificationIndex::class)
            ->assertSee('Ma notification')
            ->assertDontSee('Leur notification');
    }

    public function test_the_unread_filter_narrows_the_list(): void
    {
        $user = $this->user();

        $this->notify($user, 'Toujours non lue');
        $this->notify($user, 'Déjà lue', read: true);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->set('filter', 'unread')
            ->assertSee('Toujours non lue')
            ->assertDontSee('Déjà lue');
    }

    public function test_the_read_filter_narrows_the_list(): void
    {
        $user = $this->user();

        $this->notify($user, 'Toujours non lue');
        $this->notify($user, 'Déjà lue', read: true);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->set('filter', 'read')
            ->assertSee('Déjà lue')
            ->assertDontSee('Toujours non lue');
    }

    /*
    |--------------------------------------------------------------------------
    | Marking (§26)
    |--------------------------------------------------------------------------
    */

    public function test_a_notification_can_be_marked_read_and_unread(): void
    {
        $user = $this->user();
        $id = $this->notify($user);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('markReadWithToast', $id);

        $this->assertNotNull($user->notifications()->find($id)->read_at);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('markUnread', $id);

        $this->assertNull($user->notifications()->find($id)->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $user = $this->user();

        $this->notify($user, 'Une');
        $this->notify($user, 'Deux');
        $this->notify($user, 'Trois');

        $this->assertSame(3, $user->unreadNotifications()->count());

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('markAllReadWithToast');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    /** Marking everything read must not reach across users. */
    public function test_mark_all_read_leaves_other_users_untouched(): void
    {
        $mine = $this->user('Moi');
        $theirs = $this->user('Autre');

        $this->notify($mine);
        $this->notify($theirs);

        Livewire::actingAs($mine)
            ->test(NotificationIndex::class)
            ->call('markAllReadWithToast');

        $this->assertSame(0, $mine->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $theirs->fresh()->unreadNotifications()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Opening
    |--------------------------------------------------------------------------
    */

    public function test_opening_marks_read_and_redirects_to_the_target(): void
    {
        $user = $this->user();
        $id = $this->notify($user, url: '/documents');

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('open', $id)
            ->assertRedirect('/documents');

        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    /** A payload with no destination still marks read rather than erroring. */
    public function test_opening_a_notification_without_a_url_falls_back_to_the_centre(): void
    {
        $user = $this->user();
        $id = $this->notify($user, url: null);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('open', $id)
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Ownership (§39)
    |--------------------------------------------------------------------------
    */

    /**
     * Every lookup goes through the user's own relation, so someone else's
     * id is simply not found — it never reaches a permission check.
     */
    public function test_another_users_notification_cannot_be_marked_read(): void
    {
        $mine = $this->user('Moi');
        $theirs = $this->user('Autre');

        $id = $this->notify($theirs);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($mine)
            ->test(NotificationIndex::class)
            ->call('markReadWithToast', $id);
    }

    public function test_another_users_notification_cannot_be_deleted(): void
    {
        $mine = $this->user('Moi');
        $theirs = $this->user('Autre');

        $id = $this->notify($theirs);

        try {
            Livewire::actingAs($mine)
                ->test(NotificationIndex::class)
                ->call('delete', $id);

            $this->fail('Deleting another user\'s notification should not be possible.');
        } catch (ModelNotFoundException) {
            // Expected.
        }

        $this->assertNotNull($theirs->notifications()->find($id));
    }

    /*
    |--------------------------------------------------------------------------
    | Housekeeping
    |--------------------------------------------------------------------------
    */

    public function test_deleting_read_notifications_keeps_unread_ones(): void
    {
        $user = $this->user();

        $this->notify($user, 'Non lue');
        $this->notify($user, 'Lue', read: true);

        Livewire::actingAs($user)
            ->test(NotificationIndex::class)
            ->call('deleteRead');

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Header bell (§43)
    |--------------------------------------------------------------------------
    */

    public function test_the_bell_reports_the_unread_count(): void
    {
        $user = $this->user();

        $this->notify($user);
        $this->notify($user);

        Livewire::actingAs($user)
            ->test(Bell::class)
            ->assertSee('2');
    }

    /** The list is built only once opened, not on every page render. */
    public function test_the_bell_loads_its_list_only_when_opened(): void
    {
        $user = $this->user();
        $this->notify($user, 'Notification récente');

        $component = Livewire::actingAs($user)->test(Bell::class);
        $component->assertDontSee('Notification récente');

        $component->call('toggle')->assertSee('Notification récente');
    }

    public function test_the_bell_can_mark_everything_read(): void
    {
        $user = $this->user();
        $this->notify($user);

        Livewire::actingAs($user)
            ->test(Bell::class)
            ->call('markAllRead');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
