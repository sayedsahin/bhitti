<?php

declare(strict_types=1);

return static function (): void {
    $user = db()->table('users')->where('username', 'admin')->exists();

    if ($user === true) {
        return;
    }

    $userId = db()->table('users')->insert([
            'email' => 'admin@example.com',
            'name' => 'admin',
            'username' => 'admin',
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
