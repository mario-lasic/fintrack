<?php

namespace App\Actions\Households;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LeaveHousehold
{
    public function handle(Household $household, User $user): void
    {
        DB::transaction(function () use ($household, $user) {
            Household::whereKey($household->id)->lockForUpdate()->firstOrFail();

            Gate::forUser($user)->authorize('leave', $household);

            $membership = $household->memberships()
                ->whereUserId($user->id)
                ->whereNull('deactivated_at')
                ->firstOrFail();

            $membership->deactivated_at = now();
            $membership->save();
        });
    }
}
