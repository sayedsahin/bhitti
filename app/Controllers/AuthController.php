<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middlewares\Authenticated;
use App\Middlewares\Guest;
use App\Supports\Auth;
use App\Supports\Role;
use Bhitti\Http\Middleware\Attributes\Middleware;
use Bhitti\Session\Cookie;
use Bhitti\Session\RememberToken;
use Bhitti\Validation\ValidationException;
use Bhitti\Validation\Validator;

class AuthController extends Controller
{

	public function __construct()
	{
	}

	public function login()
	{
		return view('auth.login', ['title' => 'Login']);
	}

	public function loginProcess()
	{

		/*
		|-----------------------------------------------------------
		| Validation way 1: Using Try-Catch
		|-----------------------------------------------------------
		*/

		$request = request();
		try {
			$data = Validator::make($request->all())
				->bail()
				->required(['email', 'password'])
				->nullable(['remember'])
				->email('email')
				->validated();

			// ✅ validated() Return only validated fields
			// $data['email']
			// $data['password']

		} catch (ValidationException $e) {
			$errors = $e->errors();
			return response()->redirect()->with(['errors' => $errors])->back();
		}

		$email = $data['email'];
		$password = $data['password'];

		$user = db()->table('users')
			->where('email', $email)
			->first();

		if (!$user || !password_verify($password, $user->password)) {
			return response()
				->redirect()
				->with(['error' => 'Incorrect User or Password'])
				->back();
		}

		// Session::regenerate();
		// Session::set('auth_user_id', (int) $user->id);
		Auth::login((int) $user->id);
		$role = Role::userRoles($user->id);
		if ($data['remember'] ?? false) {

			$token = RememberToken::generate();

			db()->table('remember_tokens')->insert([
				'user_id'    => $user->id,
				'token_hash' => $token['hash'],
				'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 30),
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'created_at' => date('Y-m-d H:i:s'),
			]);

			Cookie::set(
				'remember_token',
				$token['raw'],
				86400 * 30,
				'Lax'
			);
		}
		return response()->redirect('/')->with(['success' => 'Login Successful']);
	}

	#[Middleware(Guest::class)]
	public function registration()
	{
		return view('auth.register', ['title' => 'Register']);
	}


	#[Middleware(Guest::class)]
	public function registrationProcess()
	{

		/*
		|-----------------------------------------------------------
		| Validation way 2: Without Try-Catch, using fails() method
		|-----------------------------------------------------------
		*/
		$request = request();

		$validator = Validator::make($request->all())
			->required(['name', 'username', 'email', 'password', 'password_confirmation'])
			->string(['name', 'username', 'email', 'password', 'password_confirmation'])
			->email('email')
			->min(['password', 'password_confirmation'], 8)
			->confirmed('password');
		if ($validator->fails()) {
			$errors = $validator->errors();
			return response()->redirect()->with(['errors' => $errors])->back();
		}
		$input = $validator->validated();

		if ($input['password'] !== $input['password_confirmation']) {
			return response()->redirect()->with(['error' => 'Password confirmation does not match !'])->back();
		}

		$check_user = db()->table('users')
			->where('username', $input['username'])
			->count();

		if ($check_user) {
			return response()->redirect()->with(['error' => 'username already exist'])->back();
		}

		$check_email = db()->table('users')
			->where('email', $input['email'])
			->count();

		if ($check_email) {
			return response()->redirect()->with(['error' => 'email already exist'])->back();
		}

		$input['password'] = $password = password_hash($input['password'], PASSWORD_DEFAULT);

		$verificationToken = bin2hex(random_bytes(32));// verification token sent to email

		$user_id = db()->table('users')->insert([
			'name' => $input['name'],
			'username' => $input['username'],
			'email' => $input['email'],
			'password' => $input['password'],
			'verification_token' => hash('sha256', $verificationToken),
            'email_verified' => 0,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		], true);

		if (!$user_id) {
			return response()->redirect()->with(['error' => 'Registration failed'])->back();
		}

		// assign default role
		Role::assign($user_id, 'user');


		return response()->redirect('/login')->with(['success' => 'Registration Successful']);
	}

	public function logout()
	{
		$userId = Auth::id();

		if ($userId) {
			db()->table('remember_tokens')->where('user_id', $userId)->delete();
		}

		Auth::logout();
		return response()->redirect('/login');
	}

	#[Middleware(Guest::class)]
	public function forgot()
	{
		return view('forgot_password');
	}
}
