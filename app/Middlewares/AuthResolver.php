<?php

namespace App\Middlewares;

use App\Supports\Auth;

class AuthResolver
{
    public  function handle()
    {
        try {
            Auth::setResolver(function (int $id) {
            return db()
                ->table('users')
                ->select('id', 'name', 'email', 'username')
                ->find($id);
            });
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
