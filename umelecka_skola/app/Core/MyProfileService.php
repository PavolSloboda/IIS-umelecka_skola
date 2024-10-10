<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class MyProfileService
{
    private Explorer $database;

    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }

    // Získání údajů o uživateli podle jeho ID
    public function getUserProfile(int $userId): ?\Nette\Database\Table\ActiveRow
    {
        return $this->database->table('users')->get($userId);
    }

    // Uložení změněných údajů o uživateli
    public function updateUserProfile(int $userId, array $data): void
    {
        $this->database->table('users')->where('id', $userId)->update($data);
    }

    // Získání všech dostupných zařízení, která si uživatel může vypůjčit
    public function getAvailableDevices(): array
    {
        return $this->database->table('devices')->where('available', false)->fetchAll();
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
