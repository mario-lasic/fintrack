<?php

namespace Tests\Feature;

use App\Actions\Households\CreateHousehold;
use App\Actions\Households\CreateHouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateHouseholdInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_owner_cannot_create_household_invitation(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $invitedBy = User::factory()->create();

        $household = (new CreateHousehold)
            ->handle($owner, 'Test household');

        $membership = new HouseholdMembership;
        $membership->household_id = $household->id;
        $membership->user_id = $invitedBy->id;
        $membership->role = 'owner';
        $membership->deactivated_at = now();
        $membership->save();

        $email = 'recipient@example.test';

        $action = new CreateHouseholdInvitation;

        try {
            $action->handle($household, $invitedBy, $email);

            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
        }
        $this->assertDatabaseMissing('household_invitations',
            ['household_id' => $household->id, 'email' => $email]);
        Mail::assertNothingSent();
    }
}
