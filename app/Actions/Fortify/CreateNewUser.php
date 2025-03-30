<?php

namespace App\Actions\Fortify;

use App\Actions\Jetstream\AddTeamMember;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ];

        // Only require team_name if not registering with an invitation
        if (!isset($input['invitation']) || !$input['invitation']) {
            $rules['team_name'] = ['required', 'string', 'max:255'];
        }

        Validator::make($input, $rules)->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => explode('@', $input['email'])[0], // Use part of email as name
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            // Create the user's personal team if not registering with an invitation
            if (!isset($input['invitation']) || !$input['invitation']) {
                $this->createTeam($user, $input['team_name']);
            }

            // Check if the user was invited to a team
            if (isset($input['invitation']) && $input['invitation']) {
                $invitation = TeamInvitation::find($input['invitation']);
                
                if ($invitation && $invitation->email === $user->email) {
                    // Get the user's current teams
                    $currentTeams = $user->allTeams();
                    
                    // Remove the user from all their current teams
                    foreach ($currentTeams as $team) {
                        if ($user->belongsToTeam($team)) {
                            $team->removeUser($user);
                        }
                    }
                    
                    // Add the user to the invited team
                    app(AddTeamMember::class)->add(
                        $invitation->team->owner,
                        $invitation->team,
                        $invitation->email,
                        $invitation->role
                    );
                    
                    // Switch to the new team
                    $user->switchTeam($invitation->team);
                    
                    // Delete the invitation
                    $invitation->delete();
                }
            }

            return $user;
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user, string $teamName): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => $teamName,
            'personal_team' => true,
        ]));
    }
}
