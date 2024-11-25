<?php
declare(strict_types=1);

/**
 * Service class to handle user-related operations, including retrieval, creation, 
 * updating, deletion, and management of user roles.
 * 
 * @package App\Core
 */

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class UsersService
{
	/** @var Explorer Database explorer instance for handling database operations */
	private Explorer $database;

	/**
     * Constructor for UsersService.
     * 
     * @param Explorer $database Database explorer instance for accessing the database
     */
	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	/**
     * Retrieves all users.
     * 
     * @return array List of all users
     */
	public function getUsers(): array
	{
		return $this->database->table('users')->fetchAll();
	}

	/**
     * Retrieves a single user by ID.
     * 
     * @param int $id User ID
     * @return ActiveRow|null User data or null if user not found
     */
	public function getUserById(int $id): ?ActiveRow
	{
		return $this->database->table('users')->get($id);
	}

	/**
     * Updates a user's information.
     * 
     * @param int $userId User ID to update
     * @param array $data Associative array of data to update
     */	
	public function updateUser(int $userId, array $data): void
	{
		$user = $this->database->table('users')->get($userId);
		if ($user) {
			$user->update($data);
		}
	}

	/**
     * Deletes a user if they have no assigned roles or active/future loans.
     * 
     * @param int $userId User ID to delete
     */
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
    } //else {
        // Pokud má uživatel nějaké přiřazené role, neprovádíme smazání
        //throw new \Exception('User cannot be deleted because they have assigned roles.');
    //}
	}


	/**
     * Creates a new user.
     * 
     * @param array $data Associative array of user data
     */
	public function createUser(array $data): void
	{
		$this->database->table('users')->insert($data);
	}

	/**
     * Retrieves users belonging to a specified atelier.
     * 
     * @param int $id Atelier ID
     * @return \Nette\Database\Table\Selection Selection of users in the atelier
     */
	public function getUsersBelongingToAtelier(int $id): \Nette\Database\Table\Selection
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id', $ateliers->select('user_id'));
	}

	/**
     * Retrieves users not belonging to a specified atelier.
     * 
     * @param int $id Atelier ID
     * @return \Nette\Database\Table\Selection Selection of users not in the atelier
     */
	public function getUsersNotBelongingToAtelier(int $id): \Nette\Database\Table\Selection
	{
		$ateliers = $this->database->table('user_atelier')->where('atelier_id', $id);
		return $this->database->table('users')->where('user_id NOT', $ateliers->select('user_id'));
	}

	/**
     * Checks if an email is unique for a user, excluding the specified user ID.
     * 
     * @param string $email Email to check
     * @param int $userId User ID to exclude from check
     * @return bool True if email is unique, false otherwise
     */
	public function isEmailUnique(string $email, int $userId): bool
    {
        $existingUser = $this->database->table('users')
            ->where('email', $email)
            ->where('user_id != ?', $userId) // Zajistí, že nezkontrolujeme aktuálního uživatele
            ->fetch();

        return $existingUser === null; // Vrací true, pokud uživatel s tímto emailem neexistuje
    }

	/**
     * Updates the roles of a user with two roles.
     * 
     * @param int $userId User ID to update
     * @param int $roleId Primary role ID
     * @param int $secrolId Secondary role ID
     */
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

	/**
     * Updates a user's role to a single role.
     * 
     * @param int $userId User ID to update
     * @param int $roleId Role ID to assign
     */
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

	/**
     * Retrieves the role ID of a user.
     * 
     * @param int $userId User ID
     * @return int|null Role ID or null if not assigned
     */
	public function getUserRoleId(int $userId): ?int
	{
    $userRole = $this->database->table('user_role')->where('user_id', $userId)->fetch();
    return $userRole ? $userRole->role_id : null;
	}

	/**
     * Removes all roles assigned to a user.
     * 
     * @param int $userId User ID to update
     */
	public function removeUserRole(int $userId): void
	{
    // Odstranění všech přiřazených rolí pro uživatele
    $this->database->table('user_role')
        ->where('user_id', $userId)
        ->delete();
	}

	/**
     * Retrieves all email addresses except the specified user ID.
     * 
     * @param int $user_id User ID to exclude
     * @return array Array of emails
     */
	public function getAllEmails(int $user_id): array
    {
    // Výběr všech emailů z tabulky users kromě zadaného user_id
    return $this->database
        ->table('users')
        ->where('NOT user_id', $user_id)
        ->select('email')
        ->fetchPairs('email', 'email'); // Vrátí pole, kde klíče i hodnoty jsou emaily
    }

}
