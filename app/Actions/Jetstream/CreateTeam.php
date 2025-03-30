<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        // Check if the user already has a team
        if ($user->allTeams()->count() > 0) {
            throw ValidationException::withMessages([
                'name' => __('You already have a team. You can only belong to one team at a time.'),
            ])->errorBag('createTeam');
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        return DB::transaction(function () use ($user, $input) {
            return tap(Team::forceCreate([
                'user_id' => $user->id,
                'name' => $input['name'],
                'personal_team' => false,
            ]), function (Team $team) use ($user) {
                $this->addTeamMember($team, $user, 'owner');
            });
        });
    }

    /**
     * Add a team member to the given team.
     */
    protected function addTeamMember(Team $team, User $user, string $role = null): void
    {
        $team->users()->attach(
            $user, ['role' => $role]
        );

        $user->switchTeam($team);
    }
}
