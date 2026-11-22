<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreHouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_household()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/')->post(route('households.store'), ['name' => 'Test']);
        $response->assertRedirect('/');
        $household = Household::where('name', 'Test')->firstOrFail();
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_household_name_is_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/')->post(route('households.store'), ['name' => '']);
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseEmpty('household_memberships');
        $this->assertDatabaseEmpty('households');
    }

    public function test_guest_cannot_create_a_household()
    {
        $response = $this->actingAsGuest()->post(route('households.store'), ['name' => 'Test']);
        $response->assertRedirect(route('login'));
        $this->assertDatabaseEmpty('household_memberships');
        $this->assertDatabaseEmpty('households');
    }
}
