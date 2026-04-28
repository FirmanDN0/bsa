<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RelationalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            DashboardDataSeeder::class,
            ProductCodeSyncSeeder::class,
        ]);
    }
}
