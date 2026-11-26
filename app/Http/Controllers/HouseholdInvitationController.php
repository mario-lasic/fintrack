<?php

namespace App\Http\Controllers;

use App\Actions\Households\AcceptHouseholdInvitation;
use App\Http\Requests\AcceptHouseholdInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdInvitationController extends Controller
{
    public function show(string $token): Response
    {
        Inertia::encryptHistory();

        return Inertia::render('Households/Invitations/Show', [
            'token' => $token,
        ]);
    }

    public function accept(
        AcceptHouseholdInvitationRequest $request,
        AcceptHouseholdInvitation $action,
    ): RedirectResponse {
        $user = $request->user();
        $token = $request->validated('token');
        $action->handle($user, $token);

        return redirect()->route('dashboard')->with([
            'success' => 'Household invitation accepted successfully.',
        ]);
    }
}
