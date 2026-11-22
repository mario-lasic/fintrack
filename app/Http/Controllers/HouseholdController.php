<?php

namespace App\Http\Controllers;

use App\Actions\Households\CreateHousehold;
use App\Http\Requests\StoreHouseholdRequest;

class HouseholdController extends Controller
{
    public function store(StoreHouseholdRequest $request)
    {
        $createHousehold = new CreateHousehold;
        $createHousehold->handle($request->user(), $request->validated('name'));

        return back();
    }
}
