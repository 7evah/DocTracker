<?php

namespace Database\Seeders;

use App\Models\Discipline;
use Illuminate\Database\Seeder;

class DisciplineSeeder extends Seeder
{
    /**
     * Engineering disciplines used as document-number prefixes (§10).
     * Codes follow common EPC practice so the demo reads as real to engineers.
     */
    public function run(): void
    {
        $disciplines = [
            ['code' => 'CV', 'name' => 'Génie civil', 'description' => 'Terrassements, fondations, ouvrages en béton.'],
            ['code' => 'ST', 'name' => 'Structures', 'description' => 'Charpentes métalliques et structures porteuses.'],
            ['code' => 'ME', 'name' => 'Mécanique', 'description' => 'Équipements rotatifs, statiques et manutention.'],
            ['code' => 'PI', 'name' => 'Tuyauterie', 'description' => 'Réseaux de tuyauterie, supportage et isométries.'],
            ['code' => 'EL', 'name' => 'Électricité', 'description' => 'Distribution HT/BT, cheminements et éclairage.'],
            ['code' => 'IN', 'name' => 'Instrumentation', 'description' => 'Contrôle-commande, boucles et automatismes.'],
            ['code' => 'PR', 'name' => 'Procédés', 'description' => 'Bilans matière, PFD, P&ID et spécifications procédé.'],
            ['code' => 'AR', 'name' => 'Architecture', 'description' => 'Bâtiments, aménagements et second œuvre.'],
            ['code' => 'HS', 'name' => 'HSE', 'description' => 'Hygiène, sécurité, environnement et études de risques.'],
            ['code' => 'XX', 'name' => 'Autre', 'description' => 'Documents transverses ou non classés.'],
        ];

        foreach ($disciplines as $index => $discipline) {
            Discipline::updateOrCreate(
                ['code' => $discipline['code']],
                $discipline + ['sort_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
