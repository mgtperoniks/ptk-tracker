<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;

class MtcUserSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::where('name', 'Maintenance')->firstOrFail();

        // 1. Kabag MTC
        $kabag = User::firstOrCreate(
            ['email' => 'kabagmtc@peroniks.com'],
            [
                'name' => 'Kabag Maintenance',
                'password' => Hash::make('password123'),
                'department_id' => $dept->id,
            ]
        );
        $kabag->assignRole('kabag_mtc');

        // 2. Admin MTC
        $admin = User::firstOrCreate(
            ['email' => 'adminmtc@peroniks.com'],
            [
                'name' => 'Admin Maintenance',
                'password' => Hash::make('password123'),
                'department_id' => $dept->id,
            ]
        );
        $admin->assignRole('admin_mtc');

        $this->command->info('Users kabagmtc@peroniks.com and adminmtc@peroniks.com created/updated.');
    }
}
