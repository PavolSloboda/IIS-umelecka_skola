<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class RolesService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	public function userWithIdHasRoleWithId(int $user_id, int $role_id) : bool
	{
		return !is_null($this->database->table('user_role')->where('user_id', $user_id)->where('role_id', $role_id)->fetch());
	}

	public function userWithEmailHasRoleWithName(string $email, string $name) : bool
	{
		$user = $this->database->table('users')->where('email', $email)->fetch();
		if(!$user)
		{
			throw new \Exception('User with email {$email} does not exist');
		}
		$role_id = $this->getRoleIdWithName($name);
		return $this->userWithIdHasRoleWithId($user->user_id, $role_id);
	}

	public function getRoleIdWithName(string $name) : int
	{
		$role = $this->database->table('roles')->where('name', $name)->fetch();
		if(!$role)
		{
			throw new \Exception('Role with name {$name} does not exist');
		}
		return $role->role_id;
	}

	public function getMyRoles(int $id) : \Nette\Database\Table\Selection
	{
		$user_roles = $this->database->table('user_role')->where('user_id', $id);

		return $this->database->table('roles')->where('role_id', $user_roles->select('role_id'));
	}

	// V RolesService
	public function getRoles(): array
	{
    // Získání všech rolí z tabulky 'roles'
    $roles = $this->database->table('roles')->fetchPairs('role_id', 'name');

    // Přidání "Registrovaný uživatel" (role bez role)
    $roles[0] = 'Registrovaný uživatel';  // Můžete použít '0' jako ID pro tuto roli

    // Seřazení rolí podle ID (nebo jiným způsobem, pokud je to potřeba)
    ksort($roles);

    return $roles;
	}

}
