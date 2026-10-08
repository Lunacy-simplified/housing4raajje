<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchIds = DB::table('branches')->pluck('id', 'code');
        $now = now();

        DB::table('districts')->updateOrInsert(
            ['name' => 'Henveiru'],
            ['branch_id' => $branchIds['MLE'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Galolhu'],
            ['branch_id' => $branchIds['MLE'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Machchangolhi'],
            ['branch_id' => $branchIds['MLE'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Maafannu'],
            ['branch_id' => $branchIds['MLE'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Hulhumalé Phase 1'],
            ['branch_id' => $branchIds['HLM'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Hulhumalé Phase 2'],
            ['branch_id' => $branchIds['HLM'], 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('districts')->updateOrInsert(
            ['name' => 'Villingili District'],
            ['branch_id' => $branchIds['VIL'], 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
