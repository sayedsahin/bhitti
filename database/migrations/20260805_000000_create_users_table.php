<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint as Table;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('users', static function (Table $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique()->unique();
            $table->string('password')->nullable();
            $table->string('verification_token')->nullable();
            $table->tinyInteger('email_verified')->default(0);
            $table->string('reset_token')->nullable();
            $table->dateTime('reset_expires')->nullable();
            $table->timestamps();
        });

        Schema::statement(
            'INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)',
            ['admin', 'admin', 'admin@example.com', '$2a$12$XCLFNvnBKSbd8GOCeY6msOcjOpimvLHQ0btSOYKM5wT54BjsVliEO']
        );
    },

    'down' => static function (): void {
        Schema::dropIfExists('users');
    },
];

