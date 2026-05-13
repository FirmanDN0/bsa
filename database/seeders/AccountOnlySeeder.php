<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountOnlySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan Role tersedia
        $ownerRole = Role::updateOrCreate(
            ['name' => 'Owner'],
            ['description' => 'Pemilik Usaha / Administrator Utama']
        );

        $karyawanRole = Role::updateOrCreate(
            ['name' => 'Karyawan'],
            ['description' => 'Staff Operasional Toko']
        );

        // 2. Buat Akun Owner (1 Akun)
        User::updateOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Dapmon',
                'role_id' => $ownerRole->id,
                'password' => Hash::make('password'),
                'position' => 'Owner',
                'division' => 'Management',
                'shift' => 'Pagi',
                'employment_status' => 'aktif',
            ]
        );

        // 3. Buat Akun Karyawan (2 Akun)
        User::updateOrCreate(
            ['phone' => '081234567891'],
            [
                'name' => 'Ahmad Permata',
                'role_id' => $karyawanRole->id,
                'password' => Hash::make('password'),
                'position' => 'Kasir',
                'division' => 'Penjualan',
                'shift' => 'Pagi',
                'employment_status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['phone' => '081234567892'],
            [
                'name' => 'Ayu Nugroho',
                'role_id' => $karyawanRole->id,
                'password' => Hash::make('password'),
                'position' => 'Staff Gudang',
                'division' => 'Logistik',
                'shift' => 'Siang',
                'employment_status' => 'aktif',
            ]
        );
    }
}
