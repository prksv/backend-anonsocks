<?php

namespace App\Enums\User;

enum UserRoles: string
{
    case ADMIN = 'ADMIN';
    case SUPPORT_AGENT = 'SUPPORT_AGENT';
}
