@component('mail::message')
# Team Invitation

You have been invited to join the **{{ $invitation->team->name }}** team!

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation
@endcomponent

If you did not expect to receive an invitation to this team, you may discard this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
