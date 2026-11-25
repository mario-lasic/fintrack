<?php

namespace Tests\Feature;

use App\Actions\Households\AcceptHouseholdInvitation;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AcceptHouseholdInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_recipient_can_accept_pending_invitation(): void
    {
        [
            'household' => $household,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $action = new AcceptHouseholdInvitation;
        $membership = $action->handle($recipient, $token);

        $this->assertSame($household->id, $membership->household_id);
        $this->assertSame($recipient->id, $membership->user_id);
        $this->assertSame('member', $membership->role);
        $this->assertNull($membership->deactivated_at);
        $this->assertDatabaseHas('household_memberships',
            [
                'id' => $membership->id,
                'user_id' => $recipient->id,
                'household_id' => $household->id,
                'role' => 'member',
                'deactivated_at' => null,
            ]);
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue($invitation->accepted_at->equalTo(now()));
    }

    public function test_deactivated_membership_is_reactivated(): void
    {
        [
            'household' => $household,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $existingMembership = new HouseholdMembership;
        $existingMembership->household_id = $household->id;
        $existingMembership->user_id = $recipient->id;
        $existingMembership->role = 'member';
        $existingMembership->deactivated_at = now()->subDays(7);
        $existingMembership->save();

        $action = new AcceptHouseholdInvitation;
        $membership = $action->handle($recipient, $token);

        $this->assertSame($existingMembership->id, $membership->id);
        $this->assertSame($recipient->id, $membership->user_id);
        $this->assertSame($household->id, $membership->household_id);
        $this->assertSame('member', $membership->role);
        $this->assertNull($membership->deactivated_at);
        $this->assertDatabaseHas('household_memberships',
            [
                'id' => $membership->id,
                'user_id' => $recipient->id,
                'household_id' => $household->id,
                'role' => 'member',
                'deactivated_at' => null,
            ]);
        $this->assertDatabaseCount('household_memberships', 2);
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue($invitation->accepted_at->equalTo(now()));
    }

    public function test_former_owner_is_reactivated_as_member(): void
    {
        [
            'household' => $household,
            'ownerMembership' => $ownerMembership,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $existingMembership = new HouseholdMembership;
        $existingMembership->household_id = $household->id;
        $existingMembership->user_id = $recipient->id;
        $existingMembership->role = 'owner';
        $existingMembership->deactivated_at = now()->subDays(7);
        $existingMembership->save();
        $action = new AcceptHouseholdInvitation;
        $membership = $action->handle($recipient, $token);

        $this->assertSame($existingMembership->id, $membership->id);
        $this->assertSame($recipient->id, $membership->user_id);
        $this->assertSame($household->id, $membership->household_id);
        $this->assertSame('member', $membership->role);
        $this->assertNull($membership->deactivated_at);
        $this->assertDatabaseHas('household_memberships',
            [
                'id' => $membership->id,
                'user_id' => $recipient->id,
                'household_id' => $household->id,
                'role' => 'member',
                'deactivated_at' => null,
            ]);
        $this->assertDatabaseHas('household_memberships', [
            'id' => $ownerMembership->id,
            'role' => 'owner',
            'deactivated_at' => null,
        ]);
        $this->assertDatabaseCount('household_memberships', 2);
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue($invitation->accepted_at->equalTo(now()));
    }

    #[DataProvider('invalidInvitationProvider')]
    public function test_invalid_invitation_acceptance_is_rejected(
        string $scenario,
        string $expectedField,
        string $expectedMessage
    ): void {
        [
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        switch ($scenario) {
            case 'unverified':
                $recipient->email_verified_at = null;
                $recipient->save();
                break;

            case 'invalid_token':
                $token = 'invalid-token';
                break;

            case 'incorrect_recipient':
                $recipient = User::factory()->create([
                    'email_verified_at' => now(),
                ]);
                break;

            case 'expired':
                $invitation->expires_at = now()->subHour();
                $invitation->save();
                break;

            case 'accepted':
                $invitation->accepted_at = now()->subHour();
                $invitation->save();
                break;
        }

        $originalAcceptedAt = $invitation->getRawOriginal('accepted_at');
        $initialMembershipCount = HouseholdMembership::count('*');
        $action = app(AcceptHouseholdInvitation::class);

        try {
            $action->handle($recipient, $token);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($expectedField, $e->errors());
            $this->assertContains($expectedMessage, $e->errors()[$expectedField]);
        }

        $this->assertDatabaseCount('household_memberships', $initialMembershipCount);

        $this->assertDatabaseHas('household_invitations', [
            'id' => $invitation->id,
            'accepted_at' => $originalAcceptedAt,
        ]);
    }

    #[DataProvider('activeMembershipProvider')]
    public function test_active_membership_cannot_accept_invitation(
        string $role
    ): void {
        [
            'household' => $household,
            'ownerMembership' => $ownerMembership,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        if ($role === 'owner') {
            $ownerMembership->role = 'member';
            $ownerMembership->save();
        }

        $existingMembership = new HouseholdMembership;
        $existingMembership->household_id = $household->id;
        $existingMembership->user_id = $recipient->id;
        $existingMembership->role = $role;
        $existingMembership->deactivated_at = null;
        $existingMembership->save();

        $action = app(AcceptHouseholdInvitation::class);

        try {
            $action->handle($recipient, $token);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('token', $e->errors());
            $this->assertContains('You are already a member of this household.', $e->errors()['token']);
        }
        $this->assertDatabaseHas('household_memberships', [
            'id' => $existingMembership->id,
            'user_id' => $existingMembership->user_id,
            'household_id' => $existingMembership->household_id,
            'role' => $role,
        ]);
        $existingMembership->refresh();
        $this->assertNull($existingMembership->deactivated_at);
        $this->assertDatabaseCount('household_memberships', 2);
        $this->assertDatabaseHas('household_invitations',
            [
                'household_id' => $household->id,
                'email' => $recipient->email,
                'token_hash' => hash('sha256', $token),
                'accepted_at' => null,
            ]);
    }

    #[DataProvider('persistenceFailureProvider')]
    public function test_acceptance_is_rolled_back_on_persistence_failure(
        bool $hasExistingMembership,
        bool $failMembershipSave,
        string $expectedMessage
    ): void {
        [
            'household' => $household,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $existingMembership = null;

        if ($hasExistingMembership) {
            $existingMembership = new HouseholdMembership;
            $existingMembership->household_id = $household->id;
            $existingMembership->user_id = $recipient->id;
            $existingMembership->role = 'owner';
            $existingMembership->deactivated_at = now()->subDays(7);
            $existingMembership->save();
        }

        $originalDeactivatedAt = $existingMembership
            ?->getRawOriginal('deactivated_at');

        $initialMembershipCount = HouseholdMembership::count();

        if ($failMembershipSave) {
            HouseholdMembership::saving(
                static function (HouseholdMembership $membership) use ($recipient): ?bool {
                    return $membership->user_id === $recipient->id
                        ? false
                        : null;
                }
            );
        } else {
            HouseholdInvitation::saving(
                static function (HouseholdInvitation $model) use ($invitation): ?bool {
                    return $model->id === $invitation->id
                        ? false
                        : null;
                }
            );
        }
        $action = app(AcceptHouseholdInvitation::class);
        try {
            $action->handle($recipient, $token);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $this->assertDatabaseCount('household_memberships', $initialMembershipCount);
        $this->assertNull($invitation->fresh()->accepted_at);
        if ($hasExistingMembership) {
            $this->assertDatabaseHas('household_memberships', [
                'id' => $existingMembership->id,
                'household_id' => $household->id,
                'user_id' => $recipient->id,
                'role' => 'owner',
                'deactivated_at' => $originalDeactivatedAt,
            ]);
        } else {
            $this->assertDatabaseMissing('household_memberships', [
                'household_id' => $household->id,
                'user_id' => $recipient->id,
            ]);
        }
    }

    private function arrangePendingInvitation(): array
    {
        $this->freezeSecond();

        $owner = User::factory()->create();

        $household = new Household;
        $household->name = 'Test Household';
        $household->save();

        $ownerMembership = new HouseholdMembership;
        $ownerMembership->household_id = $household->id;
        $ownerMembership->user_id = $owner->id;
        $ownerMembership->role = 'owner';
        $ownerMembership->deactivated_at = null;
        $ownerMembership->save();

        $recipient = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = Str::random(64);

        $invitation = HouseholdInvitation::create([
            'household_id' => $household->id,
            'invited_by_user_id' => $owner->id,
            'email' => $recipient->email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(24),
        ]);

        return compact(
            'owner',
            'household',
            'ownerMembership',
            'recipient',
            'token',
            'invitation',
        );
    }

    public static function invalidInvitationProvider(): array
    {
        return [
            'unverified user' => [
                'unverified',
                'email',
                'You must verify your email address before accepting an invitation.',
            ],
            'invalid token' => [
                'invalid_token',
                'token',
                'This invitation is invalid.',
            ],
            'incorrect recipient' => [
                'incorrect_recipient',
                'token',
                'This invitation is invalid.',
            ],
            'expired invitation' => [
                'expired',
                'token',
                'This invitation has expired.',
            ],
            'already accepted invitation' => [
                'accepted',
                'token',
                'This invitation has already been accepted.',
            ],
        ];
    }

    public static function activeMembershipProvider(): array
    {
        return [
            'active member' => ['member'],
            'active owner' => ['owner'],
        ];
    }

    public static function persistenceFailureProvider(): array
    {
        return [
            'membership creation fails' => [
                false,
                true,
                'Failed to create household membership.',
            ],
            'membership reactivation fails' => [
                true,
                true,
                'Failed to reactivate household membership.',
            ],
            'invitation save fails after creation' => [
                false,
                false,
                'Failed to mark invitation as accepted.',
            ],
            'invitation save fails after reactivation' => [
                true,
                false,
                'Failed to mark invitation as accepted.',
            ],
        ];
    }
}
