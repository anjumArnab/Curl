<?php

namespace App\Policies;

use App\Models\Environment;
use App\Models\User;

class EnvironmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Environment $environment): bool
    {
        return true;
    }

    /**
     * Both admins and team members manage environments used for testing.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Environment $environment): bool
    {
        return true;
    }

    public function delete(User $user, Environment $environment): bool
    {
        return true;
    }
}
