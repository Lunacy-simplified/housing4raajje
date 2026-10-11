<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $roles = [
            'system_admin' => 'System Administrator',
            'branch_manager' => 'Branch Manager',
            'property_specialist' => 'Property Specialist',
            'receptionist' => 'Receptionist',
            'owner' => 'Property Owner',
            'client' => 'Client',
        ];

        foreach ($roles as $code => $name) {
            DB::table('roles')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name, 
                    'created_at' => $now, 
                    'updated_at' => $now,
                ]
            );
        }
    }
}
