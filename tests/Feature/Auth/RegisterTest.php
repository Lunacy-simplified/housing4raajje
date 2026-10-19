<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Return a valid registration form.
     * Individual tests can override or remove fields.
     */
    private function validForm(array $overrides = [])
    {
        return array_merge([
            'name' => 'Aisha Ali',
            'email' => 'aisha@example.com',
            'phone' => '7771234',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'terms' => '1',
        ], $overrides);
    }

    public function test_client_can_register_without_an_id()
    {
        $response = $this->post(
            '/register/client',
            $this->validForm()
        );

        $response->assertRedirect(route('client.dashboard'));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        $user = User::where('email', 'aisha@example.com')->firstOrFail();

        $this->assertNotNull($user->clientProfile);
        $this->assertNull($user->ownerProfile);

        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $user->id,
            'national_id_or_passport' => null,
        ]);

        $this->assertDatabaseCount('owner_profiles', 0);
    }

    public function test_owner_can_register_with_an_id()
    {
        $response = $this->post('/register/owner', $this->validForm([
            'national_id_or_passport' => 'A123456',
        ]));

        $response->assertRedirect(route('owner.dashboard'));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        $user = User::where('email', 'aisha@example.com')->firstOrFail();

        $this->assertNotNull($user->ownerProfile);
        $this->assertNull($user->clientProfile);

        $this->assertDatabaseHas('owner_profiles', [
            'user_id' => $user->id,
            'national_id_or_passport' => 'A123456',
        ]);

        $this->assertDatabaseCount('client_profiles', 0);
    }

    public function test_owner_cannot_register_without_an_id()
    {
        $response = $this->post(
            '/register/owner',
            $this->validForm()
        );

        $response->assertSessionHasErrors('national_id_or_passport');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('owner_profiles', 0);
    }

    public function test_duplicate_owner_id_is_rejected_after_normalization()
    {
        // Create an existing owner directly, without running registration.
        $owner = User::factory()->role('owner')->create([
            'phone' => '7777654',
        ]);

        $owner->ownerProfile()->create([
            'national_id_or_passport' => 'A123456',
        ]);

        // Attempt to register another owner with the same ID.
        $response = $this->post('/register/owner', $this->validForm([
            'national_id_or_passport' => '  a123456  ',
        ]));

        $response->assertSessionHasErrors('national_id_or_passport');

        // The failed request must not create another account.
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('owner_profiles', 1);
    }


    public function test_staff_cannot_access_registration_page()
    {
        $this->get('/register/system_admin')
            ->assertNotFound();

        $this->post('/register/system_admin', $this->validForm())
            ->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }


    public function test_duplicate_email_is_rejected_even_with_different_case()
    {
        User::factory()->role('client')->create([
            'email' => 'aisha@example.com',
            'phone' => '7777654',
        ]);

        $response = $this->post('/register/client', $this->validForm([
            'email' => 'AISHA@EXAMPLE.COM',
        ]));

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_phone_is_rejected()
    {
        User::factory()->role('client')->create([
            'email' => 'existing@example.com',
            'phone' => '7771234',
        ]);

        $response = $this->post(
            '/register/client',
            $this->validForm()
        );

        $response->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_landline_number_is_rejected()
    {
        $response = $this->post('/register/client', $this->validForm([
            'phone' => '3301234',
        ]));

        $response->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_passwords_must_match()
    {
        $response = $this->post('/register/client', $this->validForm([
            'password_confirmation' => 'Different123',
        ]));

        $response->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_weak_password_is_rejected()
    {
        $response = $this->post('/register/client', $this->validForm([
            'password' => 'password',
            'password_confirmation' => 'password',
        ]));

        $response->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_terms_acceptance()
    {
        $form = $this->validForm();
        unset($form['terms']);

        $response = $this->post('/register/client', $form);

        $response->assertSessionHasErrors('terms');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_is_stored_as_a_hash()
    {
        $this->post('/register/client', $this->validForm())
            ->assertRedirect(route('client.dashboard'));

        $user = User::where('email', 'aisha@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('Secret123', $user->password));
        $this->assertNotSame('Secret123', $user->password);
    }

    public function test_client_id_is_ignored_if_submitted()
    {
        $response = $this->post('/register/client', $this->validForm([
            'national_id_or_passport' => 'A123456',
        ]));

        $response->assertRedirect(route('client.dashboard'));
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'aisha@example.com')->firstOrFail();

        $this->assertNotNull($user->clientProfile);
        $this->assertNull($user->ownerProfile);

        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $user->id,
            'national_id_or_passport' => null,
        ]);

        $this->assertDatabaseCount('owner_profiles', 0);
    }
}
