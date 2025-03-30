<?php

namespace App\Listeners;

use App\Actions\Jetstream\AddTeamMember;
use App\Models\TeamInvitation;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Session;

class HandleTeamInvitationAfterLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(protected AddTeamMember $addTeamMember)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $request = request();
        
        // Check if there's an invitation ID in the request
        if ($request->has('invitation')) {
            $invitationId = $request->input('invitation');
            $invitation = TeamInvitation::find($invitationId);
            
            // Check if the invitation exists and belongs to the logged-in user
            if ($invitation && $invitation->email === $event->user->email) {
                // Get the user's current teams
                $currentTeams = $event->user->allTeams();
                
                // Remove the user from all their current teams
                foreach ($currentTeams as $team) {
                    if ($event->user->belongsToTeam($team)) {
                        $team->removeUser($event->user);
                    }
                }
                
                // Add the user to the new team
                $this->addTeamMember->add(
                    $invitation->team->owner,
                    $invitation->team,
                    $invitation->email,
                    $invitation->role
                );
                
                // Switch to the new team
                $event->user->switchTeam($invitation->team);
                
                // Delete the invitation
                $invitation->delete();
                
                // Add a success message to the session
                Session::flash('status', __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]));
            }
        }
    }
}
