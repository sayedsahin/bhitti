<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint as Table;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('api_tokens', static function (Table $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token', 255)->unique();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    },

    'down' => static function (): void {
        Schema::dropIfExists('api_tokens');
    },
];
