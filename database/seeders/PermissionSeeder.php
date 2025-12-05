<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'ARTICLE' => 'Gestion des articles',
            'HOSTING' => 'Gestion hébergement',
            'RESTAURANT' => 'Gestion restaurant',
            'BAR' => 'Gestion Bar',
            'PAY_RH' => 'Paie et Ressources humaines',
            'PURCHASE' => 'Gestion de achats',
            'ACCOUNTING' => 'Gestion comptable',
            'STOCKING' => 'Gestion des stocks',
            'SETTING' => 'Gestion des paramètres',
            'USER_ROLE' => 'Gestion des utilisateurs et rôles',
        ];

        foreach ($data as $key => $value) {
            Permission::firstOrCreate([
                'code' => $key,
                'name' => $value,
            ]);
        }
    }
}
