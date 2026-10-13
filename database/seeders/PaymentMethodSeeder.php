<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $methods = [
            'bank_deposit' => 'Bank deposit',
            'online_transfer' => 'Online transfer',
        ];

        foreach ($methods as $code => $name) {
            DB::table('payment_methods')->updateOrInsert(
                ['code' => $code], // Keyed on unique column
                [
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
