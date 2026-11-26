<?php

namespace Tests\Feature;

use App\Mail\HouseholdInvitationMail;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcceptHouseholdInvitationHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_verified_user_can_view_invitation_confirmation(): void
    {
        [
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $response = $this->actingAs($recipient)
            ->get(route('household.invitations.show', ['token' => $token]));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Households/Invitations/Show')
            ->where('token', $token));
        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseCount('household_memberships', 1);
    }

    public function test_verified_recipient_can_accept_invitation_via_http(): void
    {
        [
            'household' => $household,
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $response = $this->actingAs($recipient)
            ->post(route('household.invitations.accept'), [
                'token' => $token,
            ]);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas(['success' => 'Household invitation accepted successfully.']);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('household_memberships', [
            'user_id' => $recipient->id,
            'household_id' => $household->id,
            'deactivated_at' => null,
            'role' => 'member',
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $invitation->refresh();
        $this->assertTrue($invitation->accepted_at->equalTo(now()));
        $this->assertTrue(
            $response->headers->hasCacheControlDirective('no-store')
        );
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_invitation_email_contains_confirmation_link(): void
    {
        $token = Str::random(64);

        $mail = new HouseholdInvitationMail($token);

        $expectedUrl = route('household.invitations.show', [
            'token' => $token,
        ]);

        $mail->assertSeeInHtml($expectedUrl);
        $mail->assertSeeInHtml('Accept invitation');
        $mail->assertSeeInHtml('This invitation expires 24 hours after it is issued.');
    }

    #[DataProvider('unauthorizedAccessProvider')]
    public function test_unauthorized_user_cannot_access_invitation_endpoints(
        string $userState,
        string $method,
        string $expectedRoute
    ): void {
        [
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        if ($userState === 'unverified') {
            $recipient->email_verified_at = null;
            $recipient->save();

            $this->actingAs($recipient);
        }
        if ($method === 'GET') {
            $response = $this->get(route('household.invitations.show', ['token' => $token]));
        } else {
            $response = $this->post(route('household.invitations.accept'), [
                'token' => $token,
            ]);
        }
        $response->assertRedirect(route($expectedRoute));
        $this->assertDatabaseCount('household_memberships', 1);
        $this->assertNull($invitation->fresh()->accepted_at);
        $response->assertSessionMissing('success');
        $this->assertTrue(
            $response->headers->hasCacheControlDirective('no-store')
        );
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    #[DataProvider('invalidTokenProvider')]
    public function test_invalid_invitation_token_is_rejected(
        array $payload
    ): void {
        [
            'recipient' => $recipient,
            'token' => $token,
            'invitation' => $invitation,
        ] = $this->arrangePendingInvitation();

        $confirmationUrl = route('household.invitations.show', [
            'token' => $token,
        ]);
        $response = $this->actingAs($recipient)
            ->from($confirmationUrl)
            ->post(route('household.invitations.accept'), $payload);
        $response->assertRedirect($confirmationUrl);
        $response->assertSessionHasErrors('token');
        $response->assertSessionMissing('success');
        $this->assertDatabaseCount('household_memberships', 1);
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_invitation_confirmation_protects_sensitive_response(): void
    {
        [
            'recipient' => $recipient,
            'token' => $token,
        ] = $this->arrangePendingInvitation();

        $response = $this->actingAs($recipient)
            ->get(route('household.invitations.show', ['token' => $token]));
        $response->assertOk();
        $this->assertTrue(
            $response->headers->hasCacheControlDirective('no-store')
        );
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertSame(true, Assert::fromTestResponse($response)->toArray()['encryptHistory']);
    }

    public function test_invitation_token_is_not_flashed_to_session(): void
    {
        [
            'recipient' => $recipient,
            'token' => $token,
        ] = $this->arrangePendingInvitation();

        $confirmationUrl = route('household.invitations.show', [
            'token' => $token,
        ]);

        $payload = [
            'token' => str_repeat('a', 63),
            'note' => 'Ordinary form input',
        ];

        $response = $this->actingAs($recipient)
            ->from($confirmationUrl)
            ->post(route('household.invitations.accept'), $payload);
        $response->assertRedirect($confirmationUrl);
        $response->assertSessionHasErrors('token');
        $response->assertSessionMissing('_old_input.token');
        $response->assertSessionHas(['_old_input.note' => $payload['note']]);
        $this->assertTrue(
            $response->headers->hasCacheControlDirective('no-store')
        );
        $response->assertHeader('Referrer-Policy', 'no-referrer');
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
            'household',
            'recipient',
            'token',
            'invitation',
        );
    }

    public static function unauthorizedAccessProvider(): array
    {
        return [
            'guest GET' => ['guest', 'GET', 'login'],
            'guest POST' => ['guest', 'POST', 'login'],
            'unverified GET' => ['unverified', 'GET', 'verification.notice'],
            'unverified POST' => ['unverified', 'POST', 'verification.notice'],
        ];
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'missing token' => [[]],
            'null token' => [['token' => null]],
            'integer token' => [['token' => 123456]],
            'array token' => [['token' => ['invalid']]],
            'short token' => [['token' => str_repeat('a', 63)]],
            'long token' => [['token' => str_repeat('a', 65)]],
            'unknown token' => [['token' => str_repeat('a', 64)]],
        ];
    }
}
