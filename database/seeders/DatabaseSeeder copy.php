<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CountryAndRegionSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ChartAccountSeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(DeviseSeeder::class);
        $this->call(FiscalYearSeeder::class);
        $this->call(JournalSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(UserAndRoleSeeder::class);
    }
}
