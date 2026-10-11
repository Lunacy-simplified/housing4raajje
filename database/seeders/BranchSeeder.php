<?php


namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $branches = [
            'MLE' => ['branch_no' => 1, 'name' => 'Malé'],
            'HLM' => ['branch_no' => 2, 'name' => 'Hulhumalé'],
            'VIL' => ['branch_no' => 3, 'name' => 'Villingili'],
        ];

        foreach ($branches as $code => $data) {
            DB::table('branches')->updateOrInsert(
                ['code' => $code],
                [
                    'branch_no' => $data['branch_no'],
                    'name' => $data['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        
        // before refactoring

        // DB::table('branches')->updateOrInsert(
        //     ['code' => 'MLE'],
        //     ['branch_no' => 1, 'name' => 'Malé', 'created_at' => $now, 'updated_at' => $now]
        // );

        // DB::table('branches')->updateOrInsert(
        //     ['code' => 'HLM'],
        //     ['branch_no' => 2, 'name' => 'Hulhumalé', 'created_at' => $now, 'updated_at' => $now]
        // );

        // DB::table('branches')->updateOrInsert(
        //     ['code' => 'VIL'],
        //     ['branch_no' => 3, 'name' => 'Villingili', 'created_at' => $now, 'updated_at' => $now]
        // );
    }
}
