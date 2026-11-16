<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            'management_fee_percent' => ['10', 'Management fee taken from collected rent before owner payouts, in percent'],
            'renewal_reminder_days' => ['30', 'How many days before a lease ends the client and owner are reminded'],
        ];

        foreach ($parameters as $key => [$value, $description]) {
            DB::table('system_parameters')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
