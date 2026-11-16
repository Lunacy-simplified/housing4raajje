<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_deactivated_user_is_logged_out_on_their_next_request(): void
    {
        $user = User::factory()->role('receptionist')->create();

        $this->actingAs($user);

        $user->is_active = false;
        $user->save();

        $this->get('/staff')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_can_access_their_dashboard(): void
    {
        $user = User::factory()->role('receptionist')->create([
            'is_active' => true,
        ]);
        // Staff need a home branch to use the staff portal.
        $user->staffProfile()->create([
            'staff_code' => 'H4R-STF-9001',
            'branch_id' => \App\Models\Branch::value('id'),
        ]);

        $this->actingAs($user)
            ->get('/staff')
            ->assertOk();
    }
}
