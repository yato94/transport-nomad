<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewUserTeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The team invitation instance.
     *
     * @var \App\Models\TeamInvitation
     */
    public $invitation;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\TeamInvitation  $invitation
     * @return void
     */
    public function __construct(TeamInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.team-invitation', [
            'acceptUrl' => URL::signedRoute('team-invitations.accept', [
                'invitation' => $this->invitation,
            ]),
        ])->subject(__('Team Invitation'));
    }
}
