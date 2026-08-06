<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create('remember_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    },

    'down' => static function (): void {
        Schema::dropIfExists('remember_tokens');
    },
];
