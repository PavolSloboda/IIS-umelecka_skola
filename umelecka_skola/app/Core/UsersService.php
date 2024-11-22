<?php
declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class UsersService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	// Výpis všech uživatelů
	public function getUsers(): array
	{
		return $this->database->table('users')->fetchAll();
	}

	// Získání jednoho uživatele podle ID
	public function getUserById(int $id): ?ActiveRow
	{
		return $this->database->table('users')->get($id);
	}

	// Aktualizace uživatele
	public function updateUser(int $userId, array $data): void
	{
		$user = $this->database->table('users')->get($userId);
		if ($user) {
			$user->update($data);
		}
	}

	// Smazání uživatele
	public function deleteUser(int $userId): void
	{
		$this->database->table('users')->where('id', $userId)->delete();
	}

	// Přidání nového uživatele
	public function createUser(array $data): void
	{
		$this->database->table('users')->insert($data);
	}

	// Výpis uživatelů patřících do ateliéru
	public function getUsersBelongingToAtelier(int $id): \Nette\Database\Table\Selection
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id', $ateliers->select('user_id'));
	}

	// Výpis uživatelů nepatřících do ateliéru
	public function getUsersNotBelongingToAtelier(int $id): \Nette\Database\Table\Selection
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id NOT', $ateliers->select('user_id'));
	}

	public function isEmailUnique(string $email, int $userId): bool
    {
        $existingUser = $this->database->table('users')
            ->where('email', $email)
            ->where('user_id != ?', $userId) // Zajistí, že nezkontrolujeme aktuálního uživatele
            ->fetch();

        return $existingUser === null; // Vrací true, pokud uživatel s tímto emailem neexistuje
    }

	public function updateUserRole(int $userId, int $roleId): void
	{
    // Vymazání všech existujících rolí pro uživatele, pokud má mít pouze jednu
    $this->database->table('user_role')->where('user_id', $userId)->delete();

    // Přiřazení nové role
    $this->database->table('user_role')->insert([
        'user_id' => $userId,
        'role_id' => $roleId,
    ]);
	}

}
