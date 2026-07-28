<?php 
namespace App\Models;

use Bhitti\Database\QueryBuilder;

class User extends QueryBuilder
{
	// protected ?string $defaultConnection = 'sqlite';
	protected string $defaultTable = 'users';
	protected array $defaultSelect = ['name', 'email'];

}