<?php
namespace Bhitti\Session;

final class RememberToken
{
    public static function generate(): array
    {
        $raw = bin2hex(random_bytes(32));

        return [
            'raw'  => $raw,
            'hash' => hash('sha256', $raw),
        ];
    }
}
