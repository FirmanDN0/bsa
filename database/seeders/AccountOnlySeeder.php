<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountOnlySeeder extends Seeder
{
    public function run(): void
    {
        $this->truncateBusinessTables();

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    private function truncateBusinessTables(): void
    {
        $tables = [
            'order_items',
            'finance_transactions',
            'orders',
            'reports',
            'activity_logs',
            'growth_reports',
            'calendar_events',
            'customers',
            'products',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}