<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;

trait HasSingleTeam
{
    /**
     * Determine if the given team is the current team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function isCurrentTeam($team)
    {
        return $this->currentTeam && $team->id === $this->currentTeam->id;
    }

    /**
     * Get the current team of the user's context.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTeam(): BelongsTo
    {
        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(Jetstream::teamModel(), 'current_team_id');
    }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * Get all of the teams the user owns or belongs to.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allTeams()
    {
        $teams = $this->ownedTeams;
        
        if ($this->currentTeam) {
            $teams = $teams->merge([$this->currentTeam]);
        }
        
        return $teams->unique('id')->values();
    }

    /**
     * Get all of the teams the user owns.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Jetstream::teamModel());
    }

    /**
     * Get all of the teams the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Jetstream::teamModel(), Jetstream::membershipModel())
                        ->withPivot('role')
                        ->withTimestamps()
                        ->as('membership');
    }

    /**
     * Get the user's "personal" team.
     *
     * @return \App\Models\Team
     */
    public function personalTeam()
    {
        return $this->ownedTeams->where('personal_team', true)->first();
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id == $team->{$this->getForeignKey()};
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->ownsTeam($team) || $this->teams->contains(function ($t) use ($team) {
            return $t->id === $team->id;
        });
    }

    /**
     * Get the role that the user has on the team.
     *
     * @param  mixed  $team
     * @return \Laravel\Jetstream\Role|null
     */
    public function teamRole($team)
    {
        if ($this->ownsTeam($team)) {
            return new OwnerRole;
        }

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        $role = $team->users
            ->where('id', $this->id)
            ->first()
            ->membership
            ->role;

        return $role ? Jetstream::findRole($role) : null;
    }

    /**
     * Determine if the user has the given role on the given team.
     *
     * @param  mixed  $team
     * @param  string  $role
     * @return bool
     */
    public function hasTeamRole($team, string $role)
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        return $this->belongsToTeam($team) && optional(Jetstream::findRole($team->users->where(
            'id', $this->id
        )->first()->membership->role))->key === $role;
    }

    /**
     * Get the user's permissions for the given team.
     *
     * @param  mixed  $team
     * @return array
     */
    public function teamPermissions($team)
    {
        if ($this->ownsTeam($team)) {
            return ['*'];
        }

        if (! $this->belongsToTeam($team)) {
            return [];
        }

        return (array) optional($this->teamRole($team))->permissions;
    }

    /**
     * Determine if the user has the given permission on the given team.
     *
     * @param  mixed  $team
     * @param  string  $permission
     * @return bool
     */
    public function hasTeamPermission($team, string $permission)
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        if (! $this->belongsToTeam($team)) {
            return false;
        }

        if (in_array(HasApiTokens::class, class_uses_recursive($this)) &&
            ! $this->tokenCan($permission) &&
            $this->currentAccessToken() !== null) {
            return false;
        }

        $permissions = $this->teamPermissions($team);

        return in_array($permission, $permissions) ||
               in_array('*', $permissions) ||
               (Jetstream::hasRoles() && $this->hasTeamRole($team, $permission));
    }

    /**
     * Remove the user from the given team.
     *
     * @param  mixed  $team
     * @return void
     */
    public function removeFromTeam($team)
    {
        if ($team->users()->detach($this->id)) {
            $this->currentTeam()->dissociate()->save();
            
            // Check if the user has any teams left
            if ($this->allTeams()->count() === 0) {
                // Create a new personal team for the user
                $personalTeam = \App\Models\Team::forceCreate([
                    'user_id' => $this->id,
                    'name' => $this->name,
                    'personal_team' => true,
                ]);
                
                // Add the user to the team and switch to it
                $personalTeam->users()->attach(
                    $this, ['role' => 'owner']
                );
                
                $this->switchTeam($personalTeam);
                
                // Reload the user to ensure the membership relation is loaded
                $this->load('membership');
            }
        }
    }

    /**
     * Get the user's role on a given team.
     *
     * @param  mixed  $team
     * @return string
     */
    public function teamRoleName($team)
    {
        if ($this->ownsTeam($team)) {
            return 'Owner';
        }

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        return optional($this->teamRole($team))->name;
    }
}

class OwnerRole
{
    /**
     * The key identifier for the role.
     *
     * @var string
     */
    public $key = 'owner';

    /**
     * The name identifier for the role.
     *
     * @var string
     */
    public $name = 'Owner';

    /**
     * The permissions for the role.
     *
     * @var array
     */
    public $permissions = ['*'];
}
