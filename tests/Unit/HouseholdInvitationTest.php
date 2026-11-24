<?php

namespace Tests\Unit;

use App\Models\HouseholdInvitation;
use Tests\TestCase;

class HouseholdInvitationTest extends TestCase
{
    public function test_unaccepted_invitation_is_pending(): void
    {
        $invitation = new HouseholdInvitation;
        $invitation->expires_at = now()->addDay();
        $invitation->accepted_at = null;

        $this->assertFalse($invitation->isExpired());
        $this->assertFalse($invitation->isAccepted());
        $this->assertTrue($invitation->isPending());
    }

    public function test_accepted_invitation_is_not_pending(): void
    {
        $invitation = new HouseholdInvitation;
        $invitation->expires_at = now()->addDay();
        $invitation->accepted_at = now()->subDay();

        $this->assertFalse($invitation->isExpired());
        $this->assertTrue($invitation->isAccepted());
        $this->assertFalse($invitation->isPending());
    }

    public function test_expired_invitation_is_not_pending(): void
    {
        $invitation = new HouseholdInvitation;
        $invitation->expires_at = now()->subDay();
        $invitation->accepted_at = null;

        $this->assertTrue($invitation->isExpired());
        $this->assertFalse($invitation->isAccepted());
        $this->assertFalse($invitation->isPending());
    }

    public function test_invitation_expires_at_exact_expiration_time(): void
    {
        $invitation = new HouseholdInvitation;
        $this->freezeSecond();
        $invitation->expires_at = now();
        $invitation->accepted_at = null;

        $this->assertFalse($invitation->isPending());
        $this->assertFalse($invitation->isAccepted());
        $this->assertTrue($invitation->isExpired());
    }

    public function test_accepted_invitation_remains_accepted_after_expiration(): void
    {
        $invitation = new HouseholdInvitation;
        $invitation->expires_at = now()->subDay();
        $invitation->accepted_at = now()->subDays(2);

        $this->assertTrue($invitation->isExpired());
        $this->assertFalse($invitation->isPending());
        $this->assertTrue($invitation->isAccepted());
    }
}
