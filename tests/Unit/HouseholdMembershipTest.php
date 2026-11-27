<?php

namespace Tests\Unit;

use App\Models\HouseholdMembership;
use Tests\TestCase;

class HouseholdMembershipTest extends TestCase
{
    public function test_membership_is_active_when_deactivated_at_is_null(): void
    {
        $membership = new HouseholdMembership;
        $membership->deactivated_at = null;

        $this->assertTrue($membership->isActive());
    }

    public function test_membership_is_not_active_when_deactivated_at_is_set(): void
    {
        $membership = new HouseholdMembership;
        $membership->deactivated_at = '2026-11-27 12:00:00';

        $this->assertFalse($membership->isActive());
    }
}
