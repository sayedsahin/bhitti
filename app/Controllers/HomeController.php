<?php

namespace App\Controllers;

use App\Supports\Auth;

class HomeController extends Controller
{
	public function index()
	{
		$users = [];
		if (Auth::check()) {
			$users[] = Auth::user();
		}

		return view('home', [
			'title' => 'Home',
			'users' => $users
		]);
	}

	public function apiIndex()
	{
		$users = [];
		if (Auth::check()) {
			$users[] = Auth::user();
		}

		return response()->json([
			'title' => 'Home',
			'users' => $users
		]);
	}
}
