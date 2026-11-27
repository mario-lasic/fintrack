<?php

namespace App\Actions\Households;

use App\Models\Household;
use App\Models\User;
use Illuminate\Contracts\Session\Session;

class ResolveActiveHousehold
{
    public function handle(User $user, Session $session): ?Household
    {
        $householdId = $session->get('active_household_id');

        if (! $householdId) {
            return null;
        }

        $hasActiveMembership = $user->householdMemberships()
            ->where('household_id', $householdId)
            ->whereNull('deactivated_at')
            ->exists();

        if (! $hasActiveMembership) {
            $session->forget('active_household_id');
            return null;
        }

        return Household::find($householdId);
    }
}
