<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaseTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $terms = [
            6 => '6 months',
            12 => '1 year',
            24 => '2 years',
            60 => '5 years',
        ];

        foreach ($terms as $months => $label) {
            DB::table('lease_terms')->updateOrInsert(
                ['months' => $months],
                [
                    'label'      => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
