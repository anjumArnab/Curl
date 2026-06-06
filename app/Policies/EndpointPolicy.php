<?php

namespace App\Policies;

use App\Models\Endpoint;
use App\Models\User;

class EndpointPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Endpoint $endpoint): bool
    {
        return true;
    }

    /**
     * Both admins and team members author and edit endpoint documentation.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Endpoint $endpoint): bool
    {
        return true;
    }

    public function delete(User $user, Endpoint $endpoint): bool
    {
        return true;
    }
}
