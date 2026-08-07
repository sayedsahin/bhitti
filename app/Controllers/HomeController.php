<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middlewares\RoleMiddleware;
use App\Supports\Auth;
use Bhitti\Database\Database;
use Bhitti\Http\Middleware\Attributes\Middleware;

// #[Middleware(RoleMiddleware::class, ['user'])]
class HomeController extends Controller
{
	public function index()
	{
		$users = [];
		if (Auth::check()) {
			$user = Auth::user();

			$users = cache()->remember('cache_user_' . $user->id, 60, function () {
				return db()->table('users')->limit(10)->get();
			});

		}

		return view('home', [
			'title' => 'Home',
			'users' => $users
		]);
	}

	public function apiIndex()
	{
		$users = [];
		// if (Auth::check()) {
		// 	$users[] = Auth::user();
		// }

		return response()->json([
			'title' => 'Home',
			'users' => $users
		]);
	}
}
