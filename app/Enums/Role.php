<?php

namespace App\Enums;

enum Role: string
{
    case Student    = 'student';
    case Teacher    = 'teacher';
    case Superadmin = 'superadmin';
    case Buyer      = 'buyer';
    case Seller     = 'seller';

    public static function commerceRoles(): array
    {
        return [self::Buyer->value, self::Seller->value];
    }

    public static function publicRegistrationRoles(): array
    {
        return [self::Student->value, self::Teacher->value, ...self::commerceRoles()];
    }
}