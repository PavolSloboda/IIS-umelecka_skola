<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class AtelierService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	public function createAtelier(string $name, string $admin_email) : void
	{
		$admin = $this->database->table('users')->where('email', $admin_email)->fetch();

		if(!$admin)
		{
			throw new \Exception("User with email: {$admin_email} not found, please make usre you've entered the correct email");
		}
		//TODO
		//add the necessary roles
	//	if($admin->role != xyz)
	//	{
	//		throw new \Exception("User with email: {$admin_email} does not have the required roles to aminister an atelier");
	//	}

		$this->database->table('ateliers')->insert(['name' => $name, 'admin_id' => $admin->user_id,]);
	}

	/*
	* @return Nette\Database\table\ActiveRow[]
	*/
	public function showAllAteliers() : array
	{
		return $this->database->table('ateliers')->fetchAll();
	}

}
