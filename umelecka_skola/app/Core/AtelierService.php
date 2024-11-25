<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use App\Core\RolesService;
use App\Core\UsersService;

final class AtelierService
{
	private Explorer $database;
	private RolesService $roles;
	private $users;

	public function __construct(Explorer $database, RolesService $roles, UsersService $users)
	{
		$this->database = $database;
		$this->roles = $roles;
		$this->users = $users;
	}

	//checks whether the atelier is empty
	public function isAtelierEmpty(int $id) : bool
	{
		$users = $this->database->table('user_atelier')->where('atelier_id', $id)->fetch();
		$devices = $this->database->table('devices')->where('atelier_id', $id)->fetch();
		if(!$users && !$devices)
		{
			return True;
		}
		return False;
	}

	//handles atelier creation and saving it to the database
	public function createAtelier(string $name, string $admin_email) : void
	{
		$admin = $this->database->table('users')->where('email', $admin_email)->fetch();

		if(!$admin)
		{
			throw new \Exception("User with email: {$admin_email} not found, please make sure you've entered the correct email");
		}

		if(!$this->roles->userWithEmailHasRoleWithName($admin->email, 'atelier_manager') && !$this->roles->userWithEmailHasRoleWithName($admin->email, 'admin'))
		{
			throw new \Exception("User must be atelier manageror admin to administrate an atelier");
		}

		$this->database->table('ateliers')->insert(['name' => $name, 'admin_id' => $admin->user_id,]);
	}

	//returns all ateliers
	/*
	* @return Nette\Database\table\ActiveRow[]
	*/
	public function showAllAteliers() : array
	{
		return $this->database->table('ateliers')->fetchAll();
	}

	//handles the editing of the atelier
	public function editAtelier(int $id, string $name, string $admin_email) : void
	{
		$admin = $this->database->table('users')->where('email', $admin_email)->fetch();

		if(!$admin)
		{
			throw new \Exception("User with email: {$admin_email} not found, please make sure you've entered the correct email");
		}

		if(!$this->roles->userWithEmailHasRoleWithName($admin_email, 'atelier_manager'))
		{
			throw new \Exception("User with email: {$admin_email} is not a atelier manager and can't be assigned an atelier");
		}

		$this->database->table('ateliers')->where('atelier_id', $id)->update(['name' => $name, 'admin_id' => $admin->user_id]);
	}

	//hnaldes the deltion of an atelier
	public function deleteAtelier(int $id) : void
	{
		$this->database->table('ateliers')->where('atelier_id', $id)->delete();
	}

	//returns the atelier with the specified id
	public function getAtelierById(int $id) : \Nette\Database\Table\ActiveRow
	{
		return $this->database->table('ateliers')->where('atelier_id', $id)->fetch();
	}

	//returns the email of the atelier with the specified id
	public function getAdminEmailByAtelierId(int $id) : string
	{
		$atelier = $this->getAtelierById($id);
		return $this->database->table('users')->where('user_id', $atelier->admin_id)->fetch()->email;
	}

	//returns whether the current user is the manager of the specified atelier
	public function isCurrUserAdminOfAtelierWithId(int $atelier_id, int $user_id) : bool
	{
		return($user_id == $this->database->table('ateliers')->where('atelier_id', $atelier_id)->fetch()->admin_id);
	}

	//handles adding a user to the atelier
	public function addUserWithIdToAtelierWithId(int $user_id, int $atelier_id): void
	{
		$this->database->table('user_atelier')->insert(['user_id' => $user_id, 'atelier_id' => $atelier_id]);
	}

	//handles removing a user from the atelier
	public function removeUserWithIdFromAtelierWithId(int $user_id, int $atelier_id) : void
	{
		$this->database->table('user_atelier')->where('user_id', $user_id)->where('atelier_id', $atelier_id)->delete();
	}

	//gets the emails of users with the atelier manager role
	public function getAdminAtelierEmails() : array
	{
		$adminUsers = $this->users->getUsers();
		$adminAtelierEmails = array();

		foreach($adminUsers as $a) {
		    if(!$this->roles->userWithEmailHasRoleWithName($a['email'], 'atelier_manager') && !$this->roles->userWithEmailHasRoleWithName($a['email'], 'admin')) continue;
		    $adminAtelierEmails[] = $a['email'];
		}
		return $adminAtelierEmails;
	}
}
