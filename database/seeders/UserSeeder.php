<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::query()->firstWhere('name', 'owner');
        $karyawanRole = Role::query()->firstWhere('name', 'karyawan');

        $users = [
            [
                'name' => 'Dapmon',
                'phone' => '081300000001',
                'password' => 'Dapmon123A',
                'role_id' => $ownerRole?->id,
                'position' => 'Owner',
                'division' => 'Manajemen',
                'shift' => 'Pagi',
                'employment_status' => 'aktif',
            ],
            [
                'name' => 'Firman',
                'phone' => '081300000002',
                'password' => 'Firman123A',
                'role_id' => $karyawanRole?->id,
                'position' => 'Staf Operasional',
                'division' => 'Operasional',
                'shift' => 'Pagi',
                'employment_status' => 'aktif',
            ],
            [
                'name' => 'Rafi',
                'phone' => '081300000003',
                'password' => 'Rafi12345A',
                'role_id' => $karyawanRole?->id,
                'position' => 'Admin Keuangan',
                'division' => 'Keuangan',
                'shift' => 'Siang',
                'employment_status' => 'aktif',
            ],
        ];

        $allowedPhones = array_map(static fn (array $user): string => $user['phone'], $users);
        User::query()->whereNotIn('phone', $allowedPhones)->delete();

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['phone' => $user['phone']],
                [
                    'name' => $user['name'],
                    'role_id' => $user['role_id'],
                    'password' => Hash::make($user['password']),
                    'position' => $user['position'],
                    'division' => $user['division'],
                    'phone' => $user['phone'],
                    'shift' => $user['shift'],
                    'employment_status' => $user['employment_status'],
                ],
            );
        }
    }
}