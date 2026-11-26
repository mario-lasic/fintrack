<x-mail::message>
    # Household Invitation

    You have been invited to join a household on FinTrack.

    Click the button below to review and accept your invitation.

    <x-mail::button :url="$invitationUrl">
        Accept invitation
    </x-mail::button>

    <x-mail::panel>
        This invitation expires 24 hours after it is issued.
    </x-mail::panel>

    If you were not expecting this invitation, you can safely ignore this email.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
