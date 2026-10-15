<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\OwnerProfile;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Target the standard operational branch from BranchSeeder
        $mleBranch = Branch::where('code', 'MLE')->firstOrFail();

        // Comprehensive array loop configuration mapping out all 6 users
        $demoUsers = [
            [
                'email' => 'admin@h4r.local',
                'name' => 'System Administrator',
                'password' => 'SecretAdmin123!',
                'role_code' => 'system_admin',
                'profile_type' => 'staff',
                'profile_data' => [
                    'staff_code' => 'H4R-STF-0001',
                    'branch_id' => null, // Empty branch is restricted to system admin only
                    'job_title' => 'Chief Technology Officer',
                    'hired_on' => '2025-01-01',
                ],
            ],
            [
                'email' => 'manager@h4r.local',
                'name' => 'Malé Branch Manager',
                'password' => 'SecretManager123!',
                'role_code' => 'branch_manager',
                'profile_type' => 'staff',
                'profile_data' => [
                    'staff_code' => 'H4R-STF-0002',
                    'branch_id' => $mleBranch->id,
                    'job_title' => 'Branch Manager',
                    'hired_on' => '2025-06-01',
                ],
            ],
            [
                'email' => 'specialist@h4r.local',
                'name' => 'Property Specialist',
                'password' => 'SecretSpecialist123!',
                'role_code' => 'property_specialist',
                'profile_type' => 'staff',
                'profile_data' => [
                    'staff_code' => 'H4R-STF-0003',
                    'branch_id' => $mleBranch->id,
                    'job_title' => 'Lettings Specialist',
                    'hired_on' => '2025-08-15',
                ],
            ],
            [
                'email' => 'reception@h4r.local',
                'name' => 'Malé Front Desk',
                'password' => 'SecretReception123!',
                'role_code' => 'receptionist',
                'profile_type' => 'staff',
                'profile_data' => [
                    'staff_code' => 'H4R-STF-0004',
                    'branch_id' => $mleBranch->id,
                    'job_title' => 'Front Desk Receptionist',
                    'hired_on' => '2026-01-10',
                ],
            ],
            [
                'email' => 'owner@h4r.local',
                'name' => 'Property Owner Account',
                'password' => 'SecretOwner123!',
                'role_code' => 'owner',
                'profile_type' => 'owner',
                'profile_data' => [
                    'national_id_or_passport' => 'A123456',
                ],
            ],
            [
                'email' => 'client@h4r.local',
                'name' => 'Rental Client Account',
                'password' => 'SecretClient123!',
                'role_code' => 'client',
                'profile_type' => 'client',
                'profile_data' => [
                    'max_monthly_rent' => 25000.00,
                    'preferred_rooms' => 2,
                    'preferred_term_months' => 12,
                ],
            ],
        ];

        foreach ($demoUsers as $userData) {
            // Strict role confirmation lookup
            $role = Role::where('code', $userData['role_code'])->firstOrFail();

            // Set properties manually to bypass Fillable guardrails cleanly
            $user = User::firstOrNew(['email' => $userData['email']]);
            $user->name = $userData['name'];
            $user->password = $userData['password']; // Plain string; automatically handled by hashed cast
            $user->role_id = $role->id;
            $user->is_active = true;
            $user->save();

            // Handle relational profile mapping extensions
            if ($userData['profile_type'] === 'staff') {
                StaffProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    $userData['profile_data']
                );
            } elseif ($userData['profile_type'] === 'owner') {
                OwnerProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    $userData['profile_data']
                );
            } elseif ($userData['profile_type'] === 'client') {
                ClientProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    $userData['profile_data']
                );
            }
        }
    }
}
