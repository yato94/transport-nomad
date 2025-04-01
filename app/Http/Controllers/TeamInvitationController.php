<?php

namespace App\Http\Controllers;

use App\Actions\Jetstream\AddTeamMember;
use App\Models\TeamInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TeamInvitation  $invitation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request, TeamInvitation $invitation)
    {
        // If the user is not logged in, check if they already have an account
        if (!$request->user()) {
            // Check if a user with this email already exists
            $userExists = \App\Models\User::where('email', $invitation->email)->exists();
            
            if ($userExists) {
                // If the user exists, redirect to login page with invitation and action=accept
                return redirect()->route('login', [
                    'invitation' => $invitation->id, 
                    'email' => $invitation->email,
                    'action' => 'accept'
                ]);
            } else {
                // If the user doesn't exist, redirect to register page with invitation
                return redirect()->route('register', ['invitation' => $invitation->id, 'email' => $invitation->email]);
            }
        }

        // If the logged-in user's email doesn't match the invitation email
        if ($request->user()->email !== $invitation->email) {
            throw new AuthorizationException;
        }

        // Get the user's current teams
        $currentTeams = $request->user()->allTeams();
        
        // Remove the user from all their current teams
        foreach ($currentTeams as $team) {
            if ($request->user()->belongsToTeam($team)) {
                $team->removeUser($request->user());
            }
        }
        
        // Add the user to the new team
        app(AddTeamMember::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );
        
        // Switch to the new team
        $request->user()->switchTeam($invitation->team);

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    /**
     * Accept a team invitation with confirmation for existing users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TeamInvitation  $invitation
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function acceptWithConfirmation(Request $request, TeamInvitation $invitation)
    {
        // If the user is not logged in, check if they already have an account
        if (!$request->user()) {
            // Check if a user with this email already exists
            $userExists = \App\Models\User::where('email', $invitation->email)->exists();
            
            if ($userExists) {
                // If the user exists, redirect to login page with invitation and action=accept
                return redirect()->route('login', [
                    'invitation' => $invitation->id, 
                    'email' => $invitation->email,
                    'action' => 'accept'
                ]);
            } else {
                // If the user doesn't exist, redirect to register page with invitation
                return redirect()->route('register', ['invitation' => $invitation->id, 'email' => $invitation->email]);
            }
        }

        // If the logged-in user's email doesn't match the invitation email
        if ($request->user()->email !== $invitation->email) {
            throw new AuthorizationException;
        }

        // Show confirmation page if this is a GET request
        if ($request->isMethod('get')) {
            return view('teams.accept-invitation-confirmation', [
                'invitation' => $invitation,
                'currentTeam' => $request->user()->currentTeam,
            ]);
        }

        // Process the acceptance if this is a POST request
        // Get the user's current teams
        $currentTeams = $request->user()->allTeams();
        
        // Remove the user from all their current teams
        foreach ($currentTeams as $team) {
            if ($request->user()->belongsToTeam($team)) {
                $team->removeUser($request->user());
            }
        }
        
        // Add the user to the new team
        app(AddTeamMember::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );
        
        // Switch to the new team
        $request->user()->switchTeam($invitation->team);

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    /**
     * Show confirmation page for declining a team invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TeamInvitation  $invitation
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function declineWithConfirmation(Request $request, TeamInvitation $invitation)
    {
        // If the user is not logged in, check if they already have an account
        if (!$request->user()) {
            // Check if a user with this email already exists
            $userExists = \App\Models\User::where('email', $invitation->email)->exists();
            
            if ($userExists) {
                // If the user exists, redirect to login page with invitation and action=decline
                return redirect()->route('login', [
                    'invitation' => $invitation->id, 
                    'email' => $invitation->email,
                    'action' => 'decline'
                ]);
            } else {
                // If the user doesn't exist, redirect to register page with invitation
                return redirect()->route('register', ['invitation' => $invitation->id, 'email' => $invitation->email]);
            }
        }

        // If the logged-in user's email doesn't match the invitation email
        if ($request->user()->email !== $invitation->email) {
            throw new AuthorizationException;
        }

        return view('teams.decline-invitation-confirmation', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Decline a team invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TeamInvitation  $invitation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function decline(Request $request, TeamInvitation $invitation)
    {
        // If the user is not logged in, check if they already have an account
        if (!$request->user()) {
            // Check if a user with this email already exists
            $userExists = \App\Models\User::where('email', $invitation->email)->exists();
            
            if ($userExists) {
                // If the user exists, redirect to login page with invitation and action=decline
                return redirect()->route('login', [
                    'invitation' => $invitation->id, 
                    'email' => $invitation->email,
                    'action' => 'decline'
                ]);
            } else {
                // If the user doesn't exist, redirect to register page with invitation
                return redirect()->route('register', ['invitation' => $invitation->id, 'email' => $invitation->email]);
            }
        }

        // If the logged-in user's email doesn't match the invitation email
        if ($request->user()->email !== $invitation->email) {
            throw new AuthorizationException;
        }

        // For POST requests, check if we have signature parameters
        if ($request->has('signature') && $request->has('expires')) {
            // Recreate the signed URL parameters
            $request->query->add([
                'signature' => $request->input('signature'),
                'expires' => $request->input('expires')
            ]);
            
            // Check if the signature is valid
            if (!$request->hasValidSignature()) {
                throw new AuthorizationException;
            }
        }

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('You have declined the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }
}
