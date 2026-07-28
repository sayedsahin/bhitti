<?php

use App\Supports\Flash;
use App\Systems\Request;
use App\Systems\Response;

if (!function_exists('request')) {
	function request(): Request
	{
		static $req;

		if (! $req) {
			$req = Request::capture();
		}

		return $req;
	}
}

if (!function_exists('response')) {
	function response(string $content = '', int $status = 200): Response
	{
		return new Response($content, $status);
	}
}



if (!function_exists('flash')) {

    function flash(): string
    {
        return Flash::render();
    }
}


function is_ajax(): bool
{
    $header = request()->header('x-requested-with');
	return !empty($header) && strtolower($header) === 'xmlhttprequest';
}


function is_api_request(): bool
{
    $path = request()->path();

    return $path === '/api'
        || str_starts_with($path, '/api/');
}
