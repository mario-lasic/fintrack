<?php

namespace Tests\Feature;

use App\Actions\Households\ResolveActiveHousehold;
use App\Actions\Households\CreateHousehold;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveActiveHouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_household_is_cleared_when_membership_is_removed()
    {
        $user = User::factory()->create();

        $household = new Household;
        $household->name = 'Test household';
        $household->save();

        $membership = new HouseholdMembership;
        $membership->user_id = $user->id;
        $membership->household_id = $household->id;
        $membership->role = 'member';
        $membership->save();

        $response = $this->actingAs($user)->from('/')->post(route('households.activate', $household));
        $response->assertRedirect('/');
        $response->assertSessionHas('active_household_id', $household->id);

        $membership->delete();

        $handler = new ResolveActiveHousehold;
        $session = app('session.store');
        $result = $handler->handle($user, $session);

        $this->assertNull($result);
        $this->assertFalse($session->exists('active_household_id'));
    }

    public function test_deactivated_membership_invalidates_active_household_context(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $household = app(CreateHousehold::class)
            ->handle($owner, 'Test Household');

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $member->id;
        $membership->role = 'member';
        $membership->deactivated_at = now();
        $membership->save();

        $session = app('session.store');
        $session->put('active_household_id', $household->id);

        $resolver = new ResolveActiveHousehold;
        $response = $resolver->handle($member, $session);
        $this->assertNull($response);
        $this->assertFalse($session->has('active_household_id'));
    }
}
