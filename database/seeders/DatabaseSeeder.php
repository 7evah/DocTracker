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
        */
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoUserSeeder::class,
                ProjectSeeder::class,
                DocumentSeeder::class,
            ]);
        }
    }
}
