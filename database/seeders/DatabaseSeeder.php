<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Reference data — always safe to seed, in any environment.
        $this->call([
            RolesAndPermissionsSeeder::class,
            DisciplineSeeder::class,
        ]);

        /*
        | Demo accounts use well-known passwords, so they are restricted to
        | local/testing (§55). Running `db:seed` on a real deployment will
        | create roles and disciplines but no logins.
        |
        | Deliberately stops here: this gives every role a login with zero
        | business data attached, so a tester builds up projects, documents,
        | reviews, approvals and tasks themselves (see TESTING.md) rather than
        | inheriting a pre-populated story. The richer, fully-populated demo
        | seeders (ProjectSeeder, DocumentSeeder, ReviewSeeder, ApprovalSeeder,
        | TaskSeeder, NotificationSeeder) still exist and still pass their own
        | tests — run them explicitly with `php artisan db:seed --class=...`
        | when a filled-in showcase dataset is wanted instead.
        */
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoUserSeeder::class,
            ]);
        }
    }
}
