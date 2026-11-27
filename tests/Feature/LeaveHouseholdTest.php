<?php

namespace Tests\Feature;

use App\Actions\Households\CreateHousehold;
use App\Actions\Households\LeaveHousehold;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaveHouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_leave_household(): void
    {
        $now = $this->freezeSecond();

        $owner = User::factory()->create();
        $member = User::factory()->create();

        $household = (new CreateHousehold)
            ->handle($owner, 'Test household');

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $member->id;
        $membership->role = 'member';
        $membership->save();
        $originalId = $membership->id;

        $action = new LeaveHousehold;

        $action->handle($household, $member);

        $membership->refresh();
        $this->assertDatabaseHas('household_memberships',
            [
                'user_id' => $member->id,
                'household_id' => $household->id,
                'id' => $originalId,
                'deactivated_at' => $now,
            ]);
        $this->assertNotNull($membership->deactivated_at);
        $this->assertTrue($membership->deactivated_at->equalTo($now));
        $this->assertSame('member', $membership->role);
        $this->assertSame(1, $household->activeOwnerMemberships()->count());
        $this->assertDatabaseCount('household_memberships', 2);
    }

    #[DataProvider('usersWithoutLeavePermission')]
    public function test_unauthorized_user_cannot_leave_household(
        ?string $role,
        bool $deactivated,
        bool $isOnlyOwner,
    ): void {
        $this->freezeSecond();

        $owner = User::factory()->create();

        $action = new CreateHousehold;
        $household = $action->handle($owner, 'Target household');

        $user = $owner;

        if (! $isOnlyOwner) {
            $user = User::factory()->create();

            if ($role === null) {
                $action->handle($user, 'Other household');
            } else {
                $membership = new HouseholdMembership;
                $membership->household_id = $household->id;
                $membership->user_id = $user->id;
                $membership->role = $role;
                $membership->deactivated_at = $deactivated
                    ? now()->subDay()
                    : null;
                $membership->save();
            }
        }

        $originalMemberships = HouseholdMembership::query()
            ->orderBy('id')
            ->get()
            ->toArray();

        $leaveHousehold = new LeaveHousehold;

        try {
            $leaveHousehold->handle($household, $user);

            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException) {
            // Expected exception.
        }
        $newMemberships = HouseholdMembership::query()
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->assertSame($originalMemberships, $newMemberships);
    }

    public static function usersWithoutLeavePermission(): array
    {
        return [
            'last active owner' => ['owner', false, true],
            'owner with another active owner' => ['owner', false, false],
            'deactivated member' => ['member', true, false],
            'deactivated owner' => ['owner', true, false],
            'owner of another household' => [null, false, false],
        ];
    }
}
