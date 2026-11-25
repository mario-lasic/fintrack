<?php

namespace App\Actions\Households;

use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AcceptHouseholdInvitation
{
    public function handle(User $user, string $token): HouseholdMembership
    {
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'You must verify your email address before accepting an invitation.',
            ]);
        }
        $invitation = $this->findInvitationByToken($token);

        return DB::transaction(function () use ($user, $invitation, $token) {
            $lockedInvitation = $this->lockInvitationForAcceptance($invitation, $token);
            $this->validateInvitationRecipient($user, $lockedInvitation);
            $this->validateInvitationState($lockedInvitation);
            $membership = $this->resolveMembershipForAcceptance($user, $lockedInvitation);
            $this->markInvitationAsAccepted($lockedInvitation);

            return $membership;
        });
    }

    private function findInvitationByToken(string $token): HouseholdInvitation
    {
        $tokenHash = hash('sha256', $token);
        $invitation = HouseholdInvitation::where('token_hash', $tokenHash)->first();
        if ($invitation === null) {
            throw ValidationException::withMessages(['token' => 'This invitation is invalid.']);
        }

        return $invitation;
    }

    private function lockInvitationForAcceptance(
        HouseholdInvitation $invitation,
        string $token,
    ): HouseholdInvitation {
        Household::whereKey($invitation->household_id)->lockForUpdate()->firstOrFail();
        $tokenHash = hash('sha256', $token);
        $lockedInvitation = HouseholdInvitation::whereKey($invitation->id)->where('token_hash', $tokenHash)
            ->lockForUpdate()
            ->first();
        if ($lockedInvitation === null) {
            throw ValidationException::withMessages(['token' => 'This invitation is invalid.']);
        }

        return $lockedInvitation;
    }

    private function validateInvitationRecipient(
        User $user,
        HouseholdInvitation $invitation
    ): void {
        $userEmail = Str::lower(trim($user->email));
        $invitationEmail = Str::lower(trim($invitation->email));
        if ($userEmail !== $invitationEmail) {
            throw ValidationException::withMessages(['token' => 'This invitation is invalid.']);
        }
    }

    private function validateInvitationState(
        HouseholdInvitation $invitation
    ): void {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages(['token' => 'This invitation has already been accepted.']);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'This invitation has expired.']);
        }
    }

    private function findMembershipForAcceptance(
        User $user,
        HouseholdInvitation $invitation
    ): ?HouseholdMembership {
        return HouseholdMembership::where('household_id', $invitation->household_id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();
    }

    private function createMembership(
        User $user,
        HouseholdInvitation $invitation
    ): HouseholdMembership {
        $membership = new HouseholdMembership;
        $membership->household_id = $invitation->household_id;
        $membership->user_id = $user->id;
        $membership->role = 'member';
        $membership->deactivated_at = null;
        if (! $membership->save()) {
            throw new RuntimeException('Failed to create household membership.');
        }

        return $membership;
    }

    private function reactivateMembership(
        HouseholdMembership $membership
    ): HouseholdMembership {
        $membership->role = 'member';
        $membership->deactivated_at = null;
        if (! $membership->save()) {
            throw new RuntimeException('Failed to reactivate household membership.');
        }

        return $membership;
    }

    private function resolveMembershipForAcceptance(
        User $user,
        HouseholdInvitation $invitation
    ): HouseholdMembership {
        $membership = $this->findMembershipForAcceptance($user, $invitation);
        if ($membership === null) {
            return $this->createMembership($user, $invitation);
        }
        if ($membership->deactivated_at === null) {
            throw ValidationException::withMessages([
                'token' => 'You are already a member of this household.',
            ]);
        }

        return $this->reactivateMembership($membership);
    }

    private function markInvitationAsAccepted(
        HouseholdInvitation $invitation
    ): void {
        $invitation->accepted_at = now();
        if (! $invitation->save()) {
            throw new RuntimeException('Failed to mark invitation as accepted.');
        }
    }
}
