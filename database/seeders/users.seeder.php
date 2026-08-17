<?php

declare(strict_types=1);

return static function (): void {
    $user = db()->table('users')->where('username', 'user')->exists();

    if ($user === true) {
        return;
    }

    $userId = db()->table('users')->insert([
            'email' => 'user@example.com',
            'name' => 'user',
            'username' => 'user',
            'password' => password_hash('password', PASSWORD_DEFAULT),
    ], true);

    $roleId = db()->table('roles')
            ->where('name', 'user')
            ->value('id');

    db()->table('user_roles')->insert([
        'user_id' => $userId,
        'role_id' => $roleId,
    ]);
};
