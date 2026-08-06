<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('users', static function (Blueprint $table): void {
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
    },

    'down' => static function (): void {
        Schema::dropIfExists('users');
    },
];

