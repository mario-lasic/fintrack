<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivateHouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_switch_the_active_household(): void
    {
        $user = User::factory()->create();
        $currentHousehold = $this->createHouseholdFor($user, 'Current household');
        $nextHousehold = $this->createHouseholdFor($user, 'Next household');

        $response = $this->actingAs($user)
            ->withSession(['active_household_id' => $currentHousehold->id])
            ->from('/')
            ->post(route('households.activate', ['household' => $nextHousehold]));

        $response->assertRedirect('/');
        $response->assertSessionHas('active_household_id', $nextHousehold->id);
    }

    public function test_user_cannot_activate_a_household_without_membership(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $currentHousehold = $this->createHouseholdFor($user, 'Current household');
        $otherHousehold = $this->createHouseholdFor($otherUser, 'Other household', 'owner');

        $response = $this->actingAs($user)
            ->withSession(['active_household_id' => $currentHousehold->id])
            ->post(route('households.activate', ['household' => $otherHousehold]));

        $response->assertForbidden();
        $response->assertSessionHas('active_household_id', $currentHousehold->id);
    }

    public function test_request_body_cannot_override_the_household_in_the_route(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $userHousehold = $this->createHouseholdFor($user, 'User household');
        $otherHousehold = $this->createHouseholdFor($otherUser, 'Other household', 'owner');

        $response = $this->actingAs($user)
            ->post(route('households.activate', ['household' => $otherHousehold]), [
                'household_id' => $userHousehold->id,
                'household' => $userHousehold->id,
            ]);

        $response->assertForbidden();
        $response->assertSessionMissing('active_household_id');
    }

    public function test_deactivated_member_cannot_activate_a_household(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();

        $currentHousehold = $this->createHouseholdFor($user, 'Current household', 'owner');
        $targetHousehold = $this->createHouseholdFor($owner, 'Target household', 'owner');

        $membership = new HouseholdMembership;
        $membership->user_id = $user->id;
        $membership->household_id = $targetHousehold->id;
        $membership->role = 'member';
        $membership->deactivated_at = now();
        $membership->save();


        $response = $this->actingAs($user)
            ->withSession(['active_household_id' => $currentHousehold->id])
            ->post(route('households.activate', ['household' => $targetHousehold]));
        $response->assertForbidden();
        $response->assertSessionHas(['active_household_id' => $currentHousehold->id]);
    }

    private function createHouseholdFor(User $user, string $name, string $role = 'member'): Household
    {
        $household = new Household;
        $household->name = $name;
        $household->save();

        $membership = new HouseholdMembership;
        $membership->user_id = $user->id;
        $membership->household_id = $household->id;
        $membership->role = $role;
        $membership->save();

        return $household;
    }
}
