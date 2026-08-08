<?php

declare(strict_types=1);

namespace App\Controllers;

class WelcomeController extends Controller
{
	public function index()
	{
		$users = [
			[
				'id' => 1,
				'name' => 'John Doe',
				'email' => 'john.doe@example.com'
			],
			[
				'id' => 2,
				'name' => 'Jane Smith',
				'email' => 'jane.smith@example.com'
			]
		];

		return view('welcome', [
			'title' => 'Welcome to Bhitti',
			'users' => $users
		]);
	}

	public function apiIndex()
	{
		$users = [
			[
				'id' => 1,
				'name' => 'John Doe',
				'email' => 'john.doe@example.com'
			],
			[
				'id' => 2,
				'name' => 'Jane Smith',
				'email' => 'jane.smith@example.com'
			]
		];

		return response()->json([
			'title' => 'Welcome to Bhitti',
			'users' => $users
		]);
	}
}
