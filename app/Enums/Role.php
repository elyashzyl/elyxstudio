<?php

namespace App\Enums;

enum Role: string
{
    case Superadmin = 'superadmin';
    case Buyer = 'buyer';
    case Seller = 'seller';

    public static function commerceRoles(): array
    {
        return [self::Buyer->value, self::Seller->value];
    }

    public static function publicRegistrationRoles(): array
    {
        return self::commerceRoles();
    }

    public static function values(): array
    {
        return [self::Superadmin->value, ...self::commerceRoles()];
    }
}
