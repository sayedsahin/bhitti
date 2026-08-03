<?php

declare(strict_types=1);

namespace App\Supports;

use App\Supports\Auth;
use RuntimeException;

final class Role
{
    private static array $cache = [];

    public static function userRoles(?int $userId = null): array
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return [];
        }

        // request cache
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }

        $roles = db()->table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->pluck('roles.name');

        return self::$cache[$userId] = $roles;
    }

    public static function has(string $role): bool
    {
        return in_array($role, self::userRoles(), true);
    }

    public static function any(array $roles): bool
    {
        $userRoles = self::userRoles();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public static function all(array $roles): bool
    {
        $userRoles = self::userRoles();

        foreach ($roles as $role) {
            if (! in_array($role, $userRoles, true)) {
                return false;
            }
        }

        return true;
    }

    public static function assign(int $userId, string $roleName): void
    {
        $roleId = db()->table('roles')
            ->where('name', $roleName)
            ->value('id');

        if (! $roleId) {
            throw new RuntimeException("Role not found: $roleName");
        }

        db()->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        unset(self::$cache[$userId]);
    }

    public static function remove(int $userId, string $roleName): void
    {
        $roleId = db()->table('roles')
            ->where('name', $roleName)
            ->value('id');

        db()->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();

        unset(self::$cache[$userId]);
    }
}