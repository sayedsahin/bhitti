<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Supports\Auth;
use App\Supports\Role;
use Bhitti\Validation\ValidationException;
use Bhitti\Validation\Validator;

class ApiAuthController extends Controller
{
    public function __construct()
    {
        // API routes use BearerAuth middleware from config/middleware.php
    }

    public function login()
    {
        $request = request();

        try {
            $data = Validator::make($request->json())
                ->required(['email', 'password'])
                ->email('email')
                ->validated();
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = db()->table('users')
            ->where('email', $data['email'])
            ->first();

        if (!$user || !password_verify($data['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!($user->email_verified ?? 1)) {
            return response()->json(['error' => 'Email not verified'], 403);
        }

        // Generate API token
        $token = bin2hex(random_bytes(32));
        db()->table('api_tokens')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 30), // 30 days
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => Role::userRoles($user->id)
            ]
        ], 200);
    }

    public function register()
    {
        $request = request();

        try {
            $data = Validator::make($request->json())
                ->required(['name', 'username', 'email', 'password', 'password_confirmation'])
                ->string(['name', 'username', 'email', 'password', 'password_confirmation'])
                ->email('email')
                ->min(['password', 'password_confirmation'], 8)
                ->confirmed('password')
                ->validated();
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return response()->json(['error' => 'Password confirmation does not match'], 422);
        }

        // Check existing user
        $existing = db()->table('users')
            ->where('username', $data['username'])
            ->orWhere('email', $data['email'])
            ->first();

        if ($existing) {
            $error = $existing->username === $data['username'] ? 'Username already exists' : 'Email already exists';
            return response()->json(['error' => $error], 422);
        }

        $verification_token = bin2hex(random_bytes(32));
		$tokenHash = hash('sha256', $verification_token);
        // Create user
        $userData = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'verification_token' => $tokenHash,
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $userId = db()->table('users')->insert($userData, true);

        if (!$userId) {
            return response()->json(['error' => 'Registration failed'], 500);
        }

        // Assign default role
        Role::assign($userId, 'user');

        // Send verification email (placeholder)
        // mail($data['email'], 'Verify Email', 'Token: ' . $userData['verification_token']);

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            // 'verification_token' => $userData['verification_token'] // For testing
        ], 201);
    }

    public function forgot()
    {
        $request = request();

        try {
            $data = Validator::make($request->json())
                ->required(['email'])
                ->email('email')
                ->validated();
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = db()->table('users')->where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'If the account exists, a reset link has been sent.'], 200);
        }

        $resetToken = bin2hex(random_bytes(32));
		$tokenHash = hash('sha256', $resetToken);

        db()->table('users')->where('id', $user->id)->update([
            'reset_token' => $tokenHash,
            'reset_expires' => date('Y-m-d H:i:s', time() + 3600) // 1 hour
        ]);

        // Send reset email (placeholder)
        // mail($data['email'], 'Reset Password', 'Token: ' . $resetToken);

        return response()->json([
            'message' => 'If the account exists, a reset link has been sent.',
            // 'reset_token' => $resetToken // For testing
        ], 200);
    }

    public function verify($token)
    {
        $tokenHash = hash('sha256', $token);
        $user = db()->table('users')->where('verification_token', $tokenHash)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid verification token'], 400);
        }

        db()->table('users')->where('id', $user->id)->update([
            'email_verified' => 1,
            'verification_token' => null
        ]);

        return response()->json(['message' => 'Email verified successfully'], 200);
    }

    public function logout()
    {
        $token = request()->bearerToken();

        if ($token === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hashedToken = hash('sha256', $token);

        $deleted = db()->table('api_tokens')
            ->where('token', $hashedToken)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Logged out successfully'], 200);
        }

        return response()->json(['error' => 'Invalid token'], 401);
    }

    // Optional: Get current user profile
    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => Role::userRoles($user->id)
            ]
        ], 200);
    }
}