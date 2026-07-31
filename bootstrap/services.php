<?php

declare(strict_types=1);

use App\Middlewares\AuthResolver;
use Bhitti\Core\Application;

return static function (Application $app): void {

    /*
     * 1st Service
     */
    // Auth::setResolver(static function (int $id): mixed {
    //     return db()
    //         ->table('users')
    //         ->select('id', 'name', 'email', 'username')
    //         ->find($id);
    // });

    (new AuthResolver)->handle();

    /*
    * Second Service
    */
    //2nd Service code....
};