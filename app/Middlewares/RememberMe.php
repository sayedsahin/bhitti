<?php

namespace App\Middlewares;

use Bhitti\Session\Session;
use Bhitti\Session\RememberToken;
use Bhitti\Session\Cookie;
use App\Supports\Auth;
use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

final class RememberMe implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        if (Session::get('auth_user_id')) {
            return null;
        }

        $raw = Cookie::get('remember_token');
        if (!$raw) {
            return null;
        }

        $hash = hash('sha256', $raw);
        $row = db()->table('remember_tokens')
            ->where('token_hash', $hash)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$row) {
            Cookie::forget('remember_token');
            return null;
        }

        $userAgent = (string) request()->header('user-agent', '');

        if (!hash_equals((string) $row->user_agent, $userAgent)) {
            Cookie::forget('remember_token');

            return null;
        }

        Session::regenerate();
        Session::set('auth_user_id', (int) $row->user_id);
        Auth::setViaRemember(true);

        // Rotate token
        $new = RememberToken::generate();

        db()->table('remember_tokens')->where('id', $row->id)->update([
            'token_hash' => $new['hash'],
        ]);

        Cookie::set('remember_token', $new['raw'], 86400 * 30);
        return null;
    }
}
