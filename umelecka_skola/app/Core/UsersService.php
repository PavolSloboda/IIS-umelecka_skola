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

	public function deleteUser(int $userId): void
	{
    // Kontrola, zda má uživatel přiřazené role
    $rolesAssigned = $this->database->table('user_atelier')
        ->where('user_id', $userId)
        ->count(); // Počet přiřazených rolí pro tohoto uživatele
	$currentDate = new \DateTime();
	$rolesAssigned += $this->database->table('loan')
	->where('user_id', $userId)
	->where(
		'loan_start >= ? OR loan_end >= ?', 
		$currentDate->format('Y-m-d'), 
		$currentDate->format('Y-m-d')
	) // Nadcházející nebo probíhající výpůjčky
	->count(); // Počet probíhajících nebo nadcházejících výpůjček

    // Pokud uživatel nemá žádnou roli, pokračujeme ve smazání
    if ($rolesAssigned === 0) {        
        // Smazání uživatele, pokud není přiřazen k žádné jiné tabulce
        $this->database->table('users')->where('user_id', $userId)->delete();
    } else {
        // Pokud má uživatel nějaké přiřazené role, neprovádíme smazání
        throw new \Exception('User cannot be deleted because they have assigned roles.');
    }
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

	public function updateUserRoletwo(int $userId, int $roleId, int $secrolId): void
	{
		// Vymazání všech existujících rolí pro uživatele, pokud má mít pouze jednu
	$this->database->table('user_role')->where('user_id', $userId)->delete();
    // Přiřazení nové role
    $this->database->table('user_role')->insert([
        'user_id' => $userId,
        'role_id' => $roleId,
    ]);
	$this->database->table('user_role')->insert([
        'user_id' => $userId,
        'role_id' => $secrolId,
    ]);
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

	public function getUserRoleId(int $userId): ?int
	{
    $userRole = $this->database->table('user_role')->where('user_id', $userId)->fetch();
    return $userRole ? $userRole->role_id : null;
	}

	public function removeUserRole(int $userId): void
	{
    // Odstranění všech přiřazených rolí pro uživatele
    $this->database->table('user_role')
        ->where('user_id', $userId)
        ->delete();
	}

}
