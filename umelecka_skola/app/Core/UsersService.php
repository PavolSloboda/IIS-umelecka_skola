<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class UsersService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	/*
	* @return Nette\Database\Table\ActiveRow
	*/
	public function getUsers() : array
	{
		return $this->database->table('users')->fetch();
	}

	public function getUsersBelongingToAtelier(int $id) : \Nette\Database\Table\Selection
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id', $ateliers->select('user_id'));
	}

	public function getUsersNotBelongingToAtelier(int $id): \Nette\Database\Table\Selection 
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id NOT', $ateliers->select('user_id'));
	}
}
