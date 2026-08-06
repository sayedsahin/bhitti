<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('user_roles', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });
    },

    'down' => static function (): void {
        Schema::dropIfExists('user_roles');
    },
];
