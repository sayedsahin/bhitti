<?php

namespace Bhitti\Http;

final class TrustedProxy
{
    public static function isSecureRequest(array $server): bool
    {
        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            return true;
        }

        if (!self::isTrustedProxy($server)) {
            return false;
        }

        $proto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';

        if ($proto) {
            $firstProto = strtolower(trim(explode(',', $proto)[0]));

            if ($firstProto === 'https') {
                return true;
            }
        }

        $ssl = strtolower($server['HTTP_X_FORWARDED_SSL'] ?? '');

        return $ssl === 'on';
    }

    private static function isTrustedProxy(array $server): bool
    {
        $proxyIp  = $server['REMOTE_ADDR'] ?? '';

        $trusted = config('app.trusted_proxies', []);

        return in_array($proxyIp , $trusted, true);
    }

    public static function clientIp(array $server): ?string
    {
        $remoteAddress = $server['REMOTE_ADDR'] ?? null;

        if (!$remoteAddress) {
            return null;
        }

        if (!self::isTrustedProxy($server)) {
            return $remoteAddress;
        }

        $forwardedFor = $server['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($forwardedFor === '') {
            return $remoteAddress;
        }

        $ips = array_reverse(
            array_map('trim', explode(',', $forwardedFor))
        );

        $trustedProxies = config('app.trusted_proxies', []);

        foreach ($ips as $ip) {
            if (
                filter_var($ip, FILTER_VALIDATE_IP)
                && !in_array($ip, $trustedProxies, true)
            ) {
                return $ip;
            }
        }

        return $remoteAddress;
    }
}