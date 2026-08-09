<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint as Table;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('roles', static function (Table $table): void {
            $table->id();
            $table->string('name', 255)->unique();
            $table->timestamps();
        });

        Schema::statement(
            'INSERT INTO roles (name) VALUES (?), (?)',
            ['admin', 'user']
        );
    },

    'down' => static function (): void {
        Schema::dropIfExists('roles');
    },
];
