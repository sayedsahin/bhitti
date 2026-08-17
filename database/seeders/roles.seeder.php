<?php

declare(strict_types=1);


return static function (): void {
    db()->raw('INSERT INTO roles (name) VALUES (?), (?)', ['admin', 'user'])->execute();
};