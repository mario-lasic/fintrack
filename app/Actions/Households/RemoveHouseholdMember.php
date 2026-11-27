<?php

namespace App\Actions\Households;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemoveHouseholdMember
{
    public function handle(
        Household $household,
        User $actor,
        User $target,
    ): void {
        DB::transaction(function () use ($household, $actor, $target) {
            Household::whereKey($household->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('remove-member', [$household, $target]);
            $membership = $household->memberships()
                ->whereUserId($target->id)
                ->whereNull('deactivated_at')
                ->firstOrFail();
            $membership->deactivated_at = now();
            $membership->save();
        });
    }
}
