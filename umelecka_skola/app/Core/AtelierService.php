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
			throw new \Exception("User with email: {$admin_email} not found, please make sure you've entered the correct email");
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

	public function editAtelier(int $id, string $name, string $admin_email) : void
	{
		$admin = $this->database->table('users')->where('email', $admin_email)->fetch();

		if(!$admin)
		{
			throw new \Exception("User with email: {$admin_email} not found, please make sure you've entered the correct email");
		}

		$this->database->table('ateliers')->where('atelier_id', $id)->update(['name' => $name, 'admin_id' => $admin->user_id]);
	}

	public function deleteAtelier(int $id) : void
	{
		$this->database->table('ateliers')->where('atelier_id', $id)->delete();
	}

	public function getAtelierById(int $id) : \Nette\Database\Table\ActiveRow
	{
		return $this->database->table('ateliers')->where('atelier_id', $id)->fetch();
	}

	public function getAdminEmailByAtelierId(int $id) : string
	{
		$atelier = $this->getAtelierById($id);
		return $this->database->table('users')->where('user_id', $atelier->admin_id)->fetch()->email;
	}
}
