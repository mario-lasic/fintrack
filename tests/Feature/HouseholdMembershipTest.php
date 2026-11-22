<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseholdMembershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_membership_links_a_user_and_household()
    {
        $user = User::factory()->create();

        $household = new Household;
        $household->name = 'Test Household';
        $household->save();

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $user->id;
        $membership->role = 'owner';
        $membership->save();

        $this->assertTrue($user->is($membership->user));
        $this->assertTrue($household->is($membership->household));
        $this->assertTrue($user->householdMemberships->contains($membership));
        $this->assertTrue($household->memberships->contains($membership));
    }

    public function test_duplicate_membership_is_rejected()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $household = new Household;
        $household->name = 'Test Household';
        $household->save();

        $householdB = new Household;
        $householdB->name = 'Test HouseholdB';
        $householdB->save();

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $userA->id;
        $membership->role = 'owner';
        $membership->save();

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $userB->id;
        $membership->role = 'owner';
        $membership->save();

        $this->assertDatabaseHas('household_memberships', ['user_id' => $userB->id, 'household_id' => $household->id]);

        $membership = new HouseholdMembership;
        $membership->household_id = $householdB->id;
        $membership->user_id = $userA->id;
        $membership->role = 'owner';
        $membership->save();

        $this->assertDatabaseHas('household_memberships', ['user_id' => $userA->id, 'household_id' => $household->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        $membership2 = new HouseholdMembership;
        $membership2->household_id = $household->id;
        $membership2->user_id = $userA->id;
        $membership2->role = 'owner';
        $membership2->save();
    }

    public function test_membership_requires_an_existing_user()
    {
        $household = new Household;
        $household->name = 'Test Household';
        $household->save();

        $this->assertDatabaseMissing('users', ['id' => 2]);
        try {
            DB::transaction(function () use ($household) {
                $membership2 = new HouseholdMembership;
                $membership2->household_id = $household->id;
                $membership2->user_id = 2;
                $membership2->role = 'owner';
                $membership2->save();
            });
            $this->fail();
        } catch (QueryException $e) {
            $this->assertEquals('23503', $e->getCode());
        }
        $this->assertDatabaseEmpty('household_memberships');
    }

    public function test_membership_requires_an_existing_household()
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('households', ['id' => 2]);
        try {
            DB::transaction(function () use ($user) {
                $membership2 = new HouseholdMembership;
                $membership2->household_id = 2;
                $membership2->user_id = $user->id;
                $membership2->role = 'owner';
                $membership2->save();
            });
            $this->fail();
        } catch (QueryException $e) {
            $this->assertEquals('23503', $e->getCode());
        }
        $this->assertDatabaseEmpty('household_memberships');
    }
}
