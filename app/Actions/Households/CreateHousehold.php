<?php

namespace App\Actions\Households;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateHousehold
{
    public function handle(User $user, string $name): Household
    {
        return DB::transaction(function () use ($user, $name) {
            $household = new Household;
            $household->name = $name;
            $household->save();

            $membership = new HouseholdMembership;
            $membership->user_id = $user->id;
            $membership->household_id = $household->id;
            $membership->role = 'owner';
            $membership->save();

            return $household;
        });
    }
}
