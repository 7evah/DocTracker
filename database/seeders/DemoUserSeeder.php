<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * The demo cast required by §55: 1 admin, 1 PM, 2 engineers, 2 reviewers,
     * 1 approver, 1 viewer.
     *
     * Credentials are intentionally obvious and are only ever seeded in local
     * development — see the guard in DatabaseSeeder. These are fictional
     * accounts on a prototype; they are not JESA credentials (§55).
     */
    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            [
                'name' => 'Hamza El Badaoui',
                'email' => 'admin@docflow.test',
                'department' => 'Systèmes d’information',
                'job_title' => 'Administrateur applicatif',
                'phone' => '+212 522 00 00 01',
                'role' => UserRole::Administrator,
            ],
            [
                'name' => 'Nadia Benchekroun',
                'email' => 'chef.projet@docflow.test',
                'department' => 'Direction de projets',
                'job_title' => 'Chef de projet senior',
                'phone' => '+212 522 00 00 02',
                'role' => UserRole::ProjectManager,
            ],
            [
                'name' => 'Youssef Amrani',
                'email' => 'ingenieur1@docflow.test',
                'department' => 'Tuyauterie',
                'job_title' => 'Ingénieur tuyauterie',
                'phone' => '+212 522 00 00 03',
                'role' => UserRole::Engineer,
            ],
            [
                'name' => 'Salma Tazi',
                'email' => 'ingenieur2@docflow.test',
                'department' => 'Génie civil',
                'job_title' => 'Ingénieure génie civil',
                'phone' => '+212 522 00 00 04',
                'role' => UserRole::Engineer,
            ],
            [
                'name' => 'Karim Oulhaj',
                'email' => 'verificateur1@docflow.test',
                'department' => 'Électricité',
                'job_title' => 'Ingénieur électricité senior',
                'phone' => '+212 522 00 00 05',
                'role' => UserRole::Reviewer,
            ],
            [
                'name' => 'Imane Rachidi',
                'email' => 'verificateur2@docflow.test',
                'department' => 'Procédés',
                'job_title' => 'Ingénieure procédés senior',
                'phone' => '+212 522 00 00 06',
                'role' => UserRole::Reviewer,
            ],
            [
                'name' => 'Rachid El Malki',
                'email' => 'approbateur@docflow.test',
                'department' => 'Direction technique',
                'job_title' => 'Directeur technique',
                'phone' => '+212 522 00 00 07',
                'role' => UserRole::Approver,
            ],
            [
                'name' => 'Leila Bouzid',
                'email' => 'lecteur@docflow.test',
                'department' => 'Qualité',
                'job_title' => 'Chargée qualité documentaire',
                'phone' => '+212 522 00 00 08',
                'role' => UserRole::Viewer,
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                $data + [
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'locale' => 'fr',
                ],
            );

            // `status` is guarded against mass assignment, so set it explicitly.
            $user->forceFill(['status' => UserStatus::Active])->save();

            $user->syncRoles([$role->value]);
        }
    }
}
