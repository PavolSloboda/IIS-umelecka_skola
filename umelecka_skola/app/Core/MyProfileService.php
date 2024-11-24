<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Security\Passwords;

final class MyProfileService
{
    private Explorer $database;
    private $passwords;

    public function __construct(Explorer $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    // Získání údajů o uživateli podle jeho ID
    public function getUserProfile(int $userId): ?\Nette\Database\Table\ActiveRow
    {
        $my_user = $this->database->table('users')->get($userId);
        bdump($my_user);
        return $my_user;
    }

    // Uložení změněných údajů o uživateli
    public function updateUserProfile(int $userId, array $data): void
    {
        $this->database->table('users')->where('user_id', $userId)->update($data);
    }

    // Získání aktuálních výpůjček uživatele
    public function getUserLoans(int $userId): array
    {
        return $this->database->table('loan')->where('user_id', $userId)->fetchAll();
    }

    // Získání aktuálních a budoucích výpůjček pro uživatele
    public function getCurrentAndFutureLoans(int $userId): array
    {
        $now = new \DateTime(); // Aktuální čas
        return $this->database->table('loan')
            ->where('user_id', $userId)
            ->where('loan_end > ?', $now) // Filtrujeme výpůjčky, které ještě neskončily
            ->fetchAll();
    }

    // Získání minulých výpůjček pro uživatele
    public function getPastLoans(int $userId): array
    {
        $now = new \DateTime(); // Aktuální čas
        return $this->database->table('loan')
            ->where('user_id', $userId)
            ->where('loan_end <= ?', $now) // Filtrujeme výpůjčky, které již skončily
            ->fetchAll();
    }

    // Funkce na kontrolu, zda email již existuje v databázi
    public function isEmailUnique(string $email, int $userId): bool
    {
        $existingUser = $this->database->table('users')
            ->where('email', $email)
            ->where('user_id != ?', $userId) // Zajistí, že nezkontrolujeme aktuálního uživatele
            ->fetch();

        return $existingUser === null; // Vrací true, pokud uživatel s tímto emailem neexistuje
    }

    // Aktualizace uživatelského profilu
    public function updateUserProfileEditForm(int $userId, array $data): void
    {
        $user = $this->database->table('users')->get($userId);

        if ($user) {
            $user->update($data);
        } else {
            throw new \Exception('User not found.');
        }
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        $user = $this->database->table('users')->get($userId);

        if (!$user || !$this->passwords->verify($oldPassword, $user->password)) {
            throw new \Exception('Current password is incorrect.');
        }

        $newPasswordHash = $this->passwords->hash($newPassword);
        $user->update(['password' => $newPasswordHash]);
    }

    public function getMyProfileRoles(int $userId): array
    {
    // Načteme všechny role uživatele z tabulky 'user_role' a vrátíme je
    $roles = $this->database->table('user_role')
        ->where('user_id', $userId)
        ->fetchAll(); // Načte všechny záznamy přiřazené uživateli

    // Pokud existují nějaké role, vrátíme seznam názvů rolí
    $roleNames = [];
    foreach ($roles as $role) {
        $roleNames[] = $this->database->table('roles')->get($role->role_id)->name; // Přiřadíme název role podle role_id
    }

    return $roleNames;
    }

    public function getUserAteliers_bak(int $userId): array
    {
    return $this->database->table('user_atelier')
        ->where('user_id', $userId)
        ->select('atelier.name')
        ->fetchPairs('atelier_id', 'atelier.name');
    }

    public function getUserAteliers(int $userId): array
    {
        $sql = "
            SELECT a.atelier_id, a.name, a.admin_id
            FROM Ateliers a
            JOIN user_atelier ua ON a.atelier_id = ua.atelier_ID
            WHERE ua.user_id = ?
        ";

        return $this->database->query($sql, $userId)->fetchAll();
    }

    //request
    public function getRequestsByUser(int $userId): array
    {
        // Načtení všech žádostí uživatele o ateliér
        return $this->database->table('wanted_devices')
            ->where('user_id', $userId)
            ->fetchAll();
    }

    public function createDeviceRequest(int $userId, string $name, string $description): void
    {
    $this->database->table('wanted_devices')->insert([
        'user_id' => $userId,
        'name' => $name,
        'description' => $description,
    ]);
    }
    
    public function deleteRequest(int $requestId): void
    {
        // Delete a device request by ID
        $this->database->table('wanted_devices')->where('ID', $requestId)->delete();
    }

    public function getAllMyEmails(): array
    {
        // Výběr všech emailů z tabulky users
        return $this->database
            ->table('users')
            ->select('email')
            ->fetchPairs(null, 'email'); // Vrátí pole pouze s hodnotami sloupce email
    }
    
}
