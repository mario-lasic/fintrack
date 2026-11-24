<?php

namespace App\Http\Controllers;

use App\Actions\Households\CreateHousehold;
use App\Actions\Households\CreateHouseholdInvitation;
use App\Http\Requests\StoreHouseholdInvitationRequest;
use App\Http\Requests\StoreHouseholdRequest;
use App\Models\Household;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    public function store(StoreHouseholdRequest $request)
    {
        $createHousehold = new CreateHousehold;
        $createHousehold->handle($request->user(), $request->validated('name'));

        return back();
    }

    public function activate(Household $household, Request $request)
    {
        $user = $request->user();
        $hasActiveMembership = $user->householdMemberships()
            ->where(['household_id' => $household->id])
            ->whereNull('deactivated_at')
            ->exists();
        if (! $hasActiveMembership) {
            abort(403);
        }
        $request->session()->put(['active_household_id' => $household->id]);

        return back();
    }

    public function storeInvitation(StoreHouseholdInvitationRequest $request, Household $household)
    {
        $action = new CreateHouseholdInvitation;
        $action->handle($household, $request->user(), $request->validated('email'));

        return back();
    }
}
