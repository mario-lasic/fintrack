<?php

namespace Tests\Feature;

use App\Actions\Households\CreateHousehold;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateHouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_household_assigns_the_creator_as_owner()
    {
        $user = User::factory()->create();
        $action = new CreateHousehold;
        $household = $action->handle($user, 'Test Household');

        $this->assertEquals('Test Household', $household->name);

        $membership = $household->memberships()->first();

        $this->assertNotNull($membership);
        $this->assertTrue($user->is($membership->user));
        $this->assertEquals('owner', $membership->role);
    }

    public function test_household_creation_is_rolled_back_when_owner_membership_creation_fails()
    {
        $this->assertDatabaseMissing('users', ['id' => 2]);
        $action = new CreateHousehold;
        $user = new User;
        $user->id = 2;
        try {
            $action->handle($user, 'Test Household');
            $this->fail();
        } catch (QueryException $e) {
            $this->assertEquals('23503', $e->getCode());
            $this->assertDatabaseMissing('households', ['name' => 'Test Household']);
        }
        $this->assertDatabaseEmpty('household_memberships');
    }
}
