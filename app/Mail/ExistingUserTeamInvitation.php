<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ExistingUserTeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The team invitation instance.
     *
     * @var \App\Models\TeamInvitation
     */
    public $invitation;

    /**
     * The user's current team.
     *
     * @var \App\Models\Team
     */
    public $currentTeam;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\TeamInvitation  $invitation
     * @param  \App\Models\Team  $currentTeam
     * @return void
     */
    public function __construct(TeamInvitation $invitation, Team $currentTeam)
    {
        $this->invitation = $invitation;
        $this->currentTeam = $currentTeam;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.team-invitation-existing-user', [
            'acceptUrl' => URL::signedRoute('team-invitations.accept-with-confirmation', [
                'invitation' => $this->invitation,
            ]),
            'declineUrl' => URL::signedRoute('team-invitations.decline', [
                'invitation' => $this->invitation,
            ]),
        ])->subject(__('Team Invitation'));
    }
}
