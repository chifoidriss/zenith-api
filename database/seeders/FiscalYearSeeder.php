<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FiscalYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FiscalYear::firstOrCreate([
            'name' => 'Année Fiscale '.date("Y"),
            'start_date' => date("Y").'-01-01',
            'end_date' => date("Y").'-12-31'
        ]);
    }
}
