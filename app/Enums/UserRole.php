<?php

namespace App\Enums;

enum UserRole: int
{
    case MANAGER = 1;
    case SUPERVISOR = 2;
    case WAITER = 3;
    case RESTAURANT_OWNER = 4;

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::SUPERVISOR => 'Supervisor',
            self::WAITER => 'Waiter',
            self::RESTAURANT_OWNER => 'Restaurant Owner',
        };
    }

    public function redirectRoute(): string
    {
        return match ($this) {
            self::WAITER => 'menu-planning.mobile-view',
            self::MANAGER,
            self::SUPERVISOR,
            self::RESTAURANT_OWNER => 'dashboard',
        };
    }

    public function isDashboardRole(): bool
    {
        return in_array($this, [
            self::MANAGER,
            self::SUPERVISOR,
            self::RESTAURANT_OWNER,
        ]);
    }

    public function isWaiter(): bool
    {
        return $this === self::WAITER;
    }

    public static function fromId(int $roleId): ?self
    {
        return self::tryFrom($roleId);
    }
}