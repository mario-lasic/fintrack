<?php

namespace Tests\Feature;

use App\Actions\Households\CreateHousehold;
use App\Mail\HouseholdInvitationMail;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreHouseholdInvitationTest extends TestCase
{
    use RefreshDatabase;

    private const string EMAIL = 'ana@example.test';

    public function test_owner_can_send_a_household_invitation(): void
    {
        Mail::fake();
        $now = $this->freezeSecond();
        $owner = User::factory()->create();
        $household = $this->createHouseholdOwnedBy($owner);

        $this->actingAs($owner)
            ->from('/')
            ->post($this->invitationUrl($household), ['email' => self::EMAIL])
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $invitation = HouseholdInvitation::query()->sole();

        $this->assertSame($household->id, $invitation->household_id);
        $this->assertSame($owner->id, $invitation->invited_by_user_id);
        $this->assertSame(self::EMAIL, $invitation->email);
        $this->assertTrue($invitation->expires_at->equalTo($now->copy()->addHours(24)));
        $this->assertDatabaseCount('household_memberships', 1);

        Mail::assertSent(
            HouseholdInvitationMail::class,
            fn (HouseholdInvitationMail $mail): bool => $mail->hasTo(self::EMAIL)
                && $mail->token !== ''
                && hash('sha256', $mail->token) === $invitation->token_hash,
        );
        Mail::assertSentCount(1);
    }

    #[DataProvider('usersWithoutInvitationPermission')]
    public function test_non_owner_cannot_send_a_household_invitation(
        ?string $role,
        bool $deactivated,
    ): void {
        Mail::fake();
        $owner = User::factory()->create();
        $household = $this->createHouseholdOwnedBy($owner);
        $actor = User::factory()->create();

        if ($role === null) {
            $this->createHouseholdOwnedBy($actor);
        } else {
            $membership = new HouseholdMembership;
            $membership->household_id = $household->id;
            $membership->user_id = $actor->id;
            $membership->role = $role;
            $membership->deactivated_at = $deactivated ? now() : null;
            $membership->save();
        }

        $this->actingAs($actor)
            ->post($this->invitationUrl($household), ['email' => self::EMAIL])
            ->assertForbidden();

        $this->assertDatabaseEmpty('household_invitations');
        Mail::assertNothingSent();
    }

    /**
     * @return array<string, array{?string, bool}>
     */
    public static function usersWithoutInvitationPermission(): array
    {
        return [
            'active member' => ['member', false],
            'owner of another household' => [null, false],
            'deactivated owner' => ['owner', true],
        ];
    }

    public function test_duplicate_pending_invitation_is_rejected(): void
    {
        Mail::fake();
        $this->freezeSecond();
        $owner = User::factory()->create();
        $household = $this->createHouseholdOwnedBy($owner);
        $url = $this->invitationUrl($household);

        $this->actingAs($owner)
            ->from('/')
            ->post($url, ['email' => self::EMAIL])
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $original = HouseholdInvitation::query()->sole();

        $this->from('/')
            ->post($url, ['email' => '  ANA@EXAMPLE.TEST  '])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('household_invitations', 1);
        $this->assertDatabaseHas('household_invitations', [
            'id' => $original->id,
            'email' => self::EMAIL,
            'token_hash' => $original->token_hash,
        ]);
        Mail::assertSentCount(1);
    }

    public function test_expired_invitation_can_be_replaced(): void
    {
        Mail::fake();
        $now = $this->freezeSecond();
        $owner = User::factory()->create();
        $household = $this->createHouseholdOwnedBy($owner);
        $oldHash = hash('sha256', 'expired-token');

        $invitation = HouseholdInvitation::query()->create([
            'household_id' => $household->id,
            'invited_by_user_id' => $owner->id,
            'email' => self::EMAIL,
            'token_hash' => $oldHash,
            'expires_at' => $now,
        ]);

        $this->actingAs($owner)
            ->from('/')
            ->post($this->invitationUrl($household), ['email' => self::EMAIL])
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $invitation->refresh();

        $this->assertDatabaseCount('household_invitations', 1);
        $this->assertNotSame($oldHash, $invitation->token_hash);
        $this->assertTrue($invitation->expires_at->equalTo($now->copy()->addHours(24)));

        Mail::assertSent(
            HouseholdInvitationMail::class,
            fn (HouseholdInvitationMail $mail): bool => $mail->hasTo(self::EMAIL)
                && hash('sha256', $mail->token) === $invitation->token_hash,
        );
        Mail::assertSentCount(1);
    }

    private function createHouseholdOwnedBy(User $owner): Household
    {
        return (new CreateHousehold)->handle($owner, 'Test household');
    }

    private function invitationUrl(Household $household): string
    {
        return route('household.invitations.store', ['household' => $household]);
    }
}
