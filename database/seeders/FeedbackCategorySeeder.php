<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedbackCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $categories = [
            'Financial constraint'     => ['is_rejection' => true,  'sort_order' => 1],
            'Location'                 => ['is_rejection' => true,  'sort_order' => 2],
            'Room size or layout'      => ['is_rejection' => true,  'sort_order' => 3],
            'Furnishing condition'     => ['is_rejection' => true,  'sort_order' => 4],
            'Client proceeding'        => ['is_rejection' => false, 'sort_order' => 5],
        ];

        foreach ($categories as $label => $data) {
            DB::table('feedback_categories')->updateOrInsert(
                ['label' => $label], // Keyed on unique column
                [
                    'is_rejection' => $data['is_rejection'],
                    'sort_order'   => $data['sort_order'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }
    }
}
