<?php

namespace Database\Seeders;

use App\Models\Journal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Journal::firstOrCreate([
            'name' => 'Journal des ventes',
            'type' => 'SALE',
            'short_name'  => 'JV',
            'product_account_id' => 921,
        ]);

        Journal::firstOrCreate([
            'name' => 'Journal des achats',
            'type' => 'PURCHASE',
            'short_name'  => 'JAC',
            'expense_account_id' => 707,
        ]);

        Journal::firstOrCreate([
            'name' => 'Operations diverses',
            'type' => 'OTHER',
            'short_name'  => 'OD',
        ]);

        Journal::firstOrCreate([
            'name' => 'Valorisations de inventaire',
            'type' => 'OTHER',
            'short_name'  => 'STJ',
        ]);

        Journal::firstOrCreate([
            'name' => 'Journal de trésorerie',
            'type' => 'CASH' ,
            'short_name'  => 'JC',
            'cash_account_id' => 691,
            'suspense_account_id' => 668,
        ]);
    }
}
