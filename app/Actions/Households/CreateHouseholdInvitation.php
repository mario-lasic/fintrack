<?php

namespace App\Actions\Households;

use App\Mail\HouseholdInvitationMail;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateHouseholdInvitation
{
    public function handle(Household $household, User $invitedBy, string $email): HouseholdInvitation
    {
        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($household, $invitedBy, $email, $token) {
            Household::whereKey($household->id)->lockForUpdate()->firstOrFail();

            $isActiveMember = HouseholdMembership::where('household_id', $household->id)
                ->whereNull('deactivated_at')
                ->whereHas('user', function ($query) use ($email) {
                    $query->where('email', $email);
                })->exists();

            if ($isActiveMember) {
                throw ValidationException::withMessages([
                    'email' => 'This email address already belongs to an active household member.',
                ]);
            }

            $existing = HouseholdInvitation::where('household_id', $household->id)
                ->where('email', $email)
                ->first();

            if ($existing !== null && $existing->isPending()) {
                throw ValidationException::withMessages([
                    'email' => 'This email address already has a pending invitation.',
                ]);
            }

            $data = [
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(24),
                'invited_by_user_id' => $invitedBy->id,
            ];

            if ($existing !== null) {
                $existing->accepted_at = null;
                $existing->fill($data);
                $existing->save();

                return $existing;
            }

            return HouseholdInvitation::create($data + [
                'household_id' => $household->id,
                'email' => $email,
            ]);
        });

        Mail::to($email)->send(new HouseholdInvitationMail($token));

        return $invitation;
    }
}
