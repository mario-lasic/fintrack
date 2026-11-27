<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

class HouseholdPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function activate(User $user, Household $household): bool
    {
        return $user->householdMemberships()
            ->where(['household_id' => $household->id])
            ->whereNull('deactivated_at')
            ->exists();
    }

    public function invite(User $user, Household $household): bool
    {
        return $user->householdMemberships()
            ->where('household_id', $household->id)
            ->where('role', 'owner')
            ->whereNull('deactivated_at')
            ->exists();
    }

    public function leave(User $user, Household $household): bool
    {
        return $user->householdMemberships()
            ->where('household_id', $household->id)
            ->where('role', 'member')
            ->whereNull('deactivated_at')
            ->exists();
    }

    public function removeMember(
        User $actor,
        Household $household,
        User $target,
    ): bool {
        return $actor->householdMemberships()
            ->where('household_id', $household->id)
            ->where('role', 'owner')
            ->whereNull('deactivated_at')
            ->exists() && $target->householdMemberships()
            ->where('household_id', $household->id)
            ->where('role', 'member')
            ->whereNull('deactivated_at')
            ->exists();
    }
}
