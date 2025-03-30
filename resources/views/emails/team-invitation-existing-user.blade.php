@component('mail::message')
# Team Invitation

You have been invited to join the **{{ $invitation->team->name }}** team!

**Important Note**: You are currently a member of the **{{ $currentTeam->name }}** team. If you accept this invitation, you will be removed from your current team and added to the **{{ $invitation->team->name }}** team.

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation & Switch Teams
@endcomponent

@component('mail::button', ['url' => $declineUrl, 'color' => 'red'])
Decline Invitation
@endcomponent

If you did not expect to receive an invitation to this team, you may discard this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
