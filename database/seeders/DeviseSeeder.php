<?php

namespace Database\Seeders;

use App\Models\Devise;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Devise::firstOrCreate([
            'devise' => 'XAF',
            'name' => 'CFA franc BEAC',
            'symbol' => 'F CFA',
            'unity' => 'Franc',
        ]);
        // Devise::firstOrCreate([
        //     'devise' => 'XOF',
        //     'name' => 'CFA franc BCEAO',
        //     'symbol' => 'CFA',
        //     'unity' => 'Franc',
        // ]);
    }
}
