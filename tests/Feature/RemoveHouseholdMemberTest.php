<?php

namespace Tests\Feature;

use App\Actions\Households\CreateHousehold;
use App\Actions\Households\RemoveHouseholdMember;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemoveHouseholdMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_owner_can_remove_active_member(): void
    {
        $now = $this->freezeSecond();

        $owner = User::factory()->create();
        $member = User::factory()->create();

        $household = (new CreateHousehold)
            ->handle($owner, 'Target household');

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $member->id;
        $membership->role = 'member';
        $membership->save();

        $originalId = $membership->id;

        $action = new RemoveHouseholdMember;

        $action->handle($household, $owner, $member);

        $membership->refresh();
        $this->assertDatabaseHas('household_memberships', [
            'id' => $originalId,
            'user_id' => $member->id,
            'household_id' => $household->id,
        ]);
        $this->assertNotNull($membership->deactivated_at);
        $this->assertTrue($membership->deactivated_at->equalTo($now));
        $this->assertSame('member', $membership->role);
        $this->assertDatabaseHas('household_memberships', [
            'user_id' => $owner->id,
            'household_id' => $household->id,
            'role' => 'owner',
            'deactivated_at' => null,
        ]);
        $this->assertSame(1, $household->activeOwnerMemberships()->count());
        $this->assertDatabaseCount('household_memberships', 2);
    }

    #[DataProvider('invalidRemovalScenarios')]
    public function test_invalid_member_removal_is_rejected(
        string $actorState,
        string $targetState,
    ): void {
        $this->freezeSecond();

        $owner = User::factory()->create();

        $createHousehold = new CreateHousehold;

        $household = $createHousehold->handle(
            $owner,
            'Target household',
        );

        $actor = $actorState === 'active_owner'
            ? $owner
            : User::factory()->create();

        $target = $targetState === 'self'
            ? $actor
            : User::factory()->create();

        $participants = [
            [$actor, $actorState],
            [$target, $targetState],
        ];

        foreach ($participants as [$user, $state]) {
            if ($user->is($owner) || $state === 'self') {
                continue;
            }

            if ($state === 'outside_owner') {
                $createHousehold->handle($user, 'Other household');

                continue;
            }

            $membership = new HouseholdMembership;
            $membership->household_id = $household->id;
            $membership->user_id = $user->id;
            $membership->role = str_ends_with($state, 'owner')
                ? 'owner'
                : 'member';
            $membership->deactivated_at = str_starts_with(
                $state,
                'deactivated',
            ) ? now()->subDay() : null;

            $membership->save();
        }

        $originalMemberships = HouseholdMembership::query()
            ->orderBy('id')
            ->get()
            ->toArray();

        $action = new RemoveHouseholdMember;

        try {
            $action->handle($household, $actor, $target);

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

    public static function invalidRemovalScenarios(): array
    {
        return [
            'active member as actor' => [
                'active_member',
                'active_member',
            ],
            'deactivated owner as actor' => [
                'deactivated_owner',
                'active_member',
            ],
            'owner of another household as actor' => [
                'outside_owner',
                'active_member',
            ],
            'another active owner as target' => [
                'active_owner',
                'active_owner',
            ],
            'self removal' => [
                'active_owner',
                'self',
            ],
            'deactivated member as target' => [
                'active_owner',
                'deactivated_member',
            ],
            'deactivated owner as target' => [
                'active_owner',
                'deactivated_owner',
            ],
            'user outside household as target' => [
                'active_owner',
                'outside_owner',
            ],
        ];
    }
}
