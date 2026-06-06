<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TeamMember = 'team_member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::TeamMember => 'Team Member',
        };
    }
}
