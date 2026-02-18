<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Jalankan seeder inisialisasi lain (jika ada)
         */
        if (class_exists(InitSeeder::class)) {
            $this->call(InitSeeder::class);
        }

        /**
         * Jalankan seeder Role & Permission
         * (Akan membuat role, permission, dan user contoh jika diaktifkan)
         */
        $this->call(RolePermissionSeeder::class);

        /**
         * Jalankan seeder PTK (kalau ada)
         */
        if (class_exists(PTKInitSeeder::class)) {
            $this->call(PTKInitSeeder::class);
        }

        /**
         * Jalankan seeder MTC User
         */
        if (class_exists(MtcUserSeeder::class)) {
            $this->call(MtcUserSeeder::class);
        }
    }
}
