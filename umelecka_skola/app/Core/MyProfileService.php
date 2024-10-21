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
        $this->database->table('users')->where('id', $userId)->update($data);
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
            ->where('id != ?', $userId) // Zajistí, že nezkontrolujeme aktuálního uživatele
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
}
