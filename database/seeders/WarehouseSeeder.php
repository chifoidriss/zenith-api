<?php

namespace Database\Seeders;

use App\Models\Emplacement;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emplacement = Emplacement::firstOrCreate([
            'name' => 'Emplacement principal',
            'type' => 'Principal',
            'description' => 'Emplacement principal',
        ]);

        Warehouse::firstOrCreate([
            'emplacement_id' => $emplacement->id,
            'name' => 'Magasin Général',
            'short_name' => 'MG',
            'description' => 'Magasin Général des produits',
        ]);

        Warehouse::firstOrCreate([
            'emplacement_id' => $emplacement->id,
            'name' => 'Magasin des achats',
            'short_name' => 'purchase',
            'description' => 'Magasin temporaires des achats',
        ]);

        Warehouse::firstOrCreate([
            'emplacement_id' => $emplacement->id,
            'name' => 'Magasin du Bar',
            'short_name' => 'bar',
            'description' => 'Magasin des boissons',
        ]);

        Warehouse::firstOrCreate([
            'emplacement_id' => $emplacement->id,
            'name' => "Magasin d'hébergement",
            'short_name' => 'hosting',
            'description' => 'Magasin des produits consommables',
        ]);

        Warehouse::firstOrCreate([
            'emplacement_id' => $emplacement->id,
            'name' => "Magasin de cuisines",
            'short_name' => 'restaurant',
            'description' => 'Magasin des produits de cuisines',
        ]);
    }
}
