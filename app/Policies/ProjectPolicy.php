<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * Owners manage projects; members only read them. Filament resolves this
 * policy for ProjectResource automatically, so the Create button, the
 * Edit/Delete row actions and the pages themselves all follow it — nothing in
 * the resource has to know about roles.
 *
 * The role comes from the central tenant_user pivot for the CURRENT tenant
 * (Filament::getTenant()), so the same user can be an owner in one workspace
 * and a read-only member in another.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isOwner($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isOwner($user);
    }

    private function isOwner(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $user->isOwnerOf($tenant);
    }
}
