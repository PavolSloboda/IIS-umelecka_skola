<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Security\Passwords;

/**
 * Class MyProfileService
 * Služba pro správu uživatelského profilu.
 */
final class MyProfileService
{
    private Explorer $database;
    private $passwords;

    /**
     * Konstruktor služby MyProfileService.
     *
     * @param Explorer $database Instance pro práci s databází.
     * @param Passwords $passwords Instance pro správu a ověřování hesel.
     */
    public function __construct(Explorer $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    /**
     * Získá údaje o uživateli podle jeho ID.
     *
     * @param int $userId ID uživatele.
     * @return \Nette\Database\Table\ActiveRow|null Vrací aktivní řádek nebo null, pokud uživatel neexistuje.
     */
    public function getUserProfile(int $userId): ?\Nette\Database\Table\ActiveRow
    {
        $my_user = $this->database->table('users')->get($userId);
        return $my_user;
    }

    /**
     * Uloží změněné údaje o uživateli.
     *
     * @param int $userId ID uživatele.
     * @param array $data Data ke změně.
     */
    public function updateUserProfile(int $userId, array $data): void
    {
        $this->database->table('users')->where('user_id', $userId)->update($data);
    }

    /**
     * Získá aktuální výpůjčky uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s výpůjčkami.
     */
    public function getUserLoans(int $userId): array
    {
        return $this->database->table('loan')->where('user_id', $userId)->fetchAll();
    }

    /**
     * Získá aktuální a budoucí výpůjčky pro uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s aktuálními a budoucími výpůjčkami.
     */
    public function getCurrentAndFutureLoans(int $userId): array
    {
        $now = new \DateTime(); // Aktuální čas
        return $this->database->table('loan')
            ->where('user_id', $userId)
            ->where('loan_end > ?', $now) // Filtrujeme výpůjčky, které ještě neskončily
            ->fetchAll();
    }

    /**
     * Získá minulé výpůjčky pro uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s minulými výpůjčkami.
     */
    public function getPastLoans(int $userId): array
    {
        $now = new \DateTime(); // Aktuální čas
        return $this->database->table('loan')
            ->where('user_id', $userId)
            ->where('loan_end <= ?', $now) // Filtrujeme výpůjčky, které již skončily
            ->fetchAll();
    }

    /**
     * Kontroluje, zda je email jedinečný.
     *
     * @param string $email Email ke kontrole.
     * @param int $userId ID uživatele, který provádí kontrolu.
     * @return bool Vrací true, pokud email není v databázi, jinak false.
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
     * Uloží změněné údaje o uživateli.
     *
     * @param int $userId ID uživatele.
     * @param array $data Data ke změně.
     * @throws \Exception Pokud uživatel není nalezen.
     */
    public function updateUserProfileEditForm(int $userId, array $data): void
    {
        $user = $this->database->table('users')->get($userId);

        if ($user) {
            $user->update($data);
        } else {
            throw new \Exception('User not found.');
        }
    }

    /**
     * Změní heslo uživatele.
     *
     * @param int $userId ID uživatele.
     * @param string $oldPassword Staré heslo.
     * @param string $newPassword Nové heslo.
     * @throws \Exception Pokud je staré heslo nesprávné.
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        $user = $this->database->table('users')->get($userId);

        if (!$user || !$this->passwords->verify($oldPassword, $user->password)) {
            throw new \Exception('Current password is incorrect.');
        }

        $newPasswordHash = $this->passwords->hash($newPassword);
        $user->update(['password' => $newPasswordHash]);
    }

    /**
     * Získá role uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s názvy rolí uživatele.
     */
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

    /**
     * Získá ateliéry uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s ateliéry uživatele.
     */
    public function getUserAteliers_bak(int $userId): array
    {
    return $this->database->table('user_atelier')
        ->where('user_id', $userId)
        ->select('atelier.name')
        ->fetchPairs('atelier_id', 'atelier.name');
    }

    /**
     * Získá ateliéry uživatele.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s ateliéry uživatele.
     */
    public function getUserAteliers(int $userId): array
    {
        $sql = "
            SELECT a.atelier_id, a.name, a.admin_id
            FROM Ateliers a
            JOIN user_atelier ua ON a.atelier_id = ua.atelier_ID
            WHERE ua.user_id = ?
        ";
        $ateliers = $this->database->table('user_atelier')->where('user_id', $userId)->fetchAll();

        $out_ateliers = [];

        foreach ($ateliers as $atelier)
        {
            array_push($out_ateliers, $this->database->table('atelier')->where('atelier_id', $atelier->atelier_id)->fetch());
        }

        return $out_ateliers;//$this->database->query($sql, $userId)->fetchAll();
    }

    /**
     * Získá žádosti uživatele o zařízení.
     *
     * @param int $userId ID uživatele.
     * @return array Pole s žádostmi o zařízení.
     */
    public function getRequestsByUser(int $userId): array
    {
        // Načtení všech žádostí uživatele o ateliér
        return $this->database->table('wanted_devices')
            ->where('user_id', $userId)
            ->fetchAll();
    }

    /**
     * Vytvoří novou žádost o zařízení.
     *
     * @param int $userId ID uživatele.
     * @param string $name Název zařízení.
     * @param string $description Popis zařízení.
     */
    public function createDeviceRequest(int $userId, string $name, string $description): void
    {
    $this->database->table('wanted_devices')->insert([
        'user_id' => $userId,
        'name' => $name,
        'description' => $description,
    ]);
    }
    
    /**
     * Smaže žádost o zařízení podle ID.
     *
     * @param int $requestId ID žádosti o zařízení.
     */
    public function deleteRequest(int $requestId): void
    {
        // Delete a device request by ID
        $this->database->table('wanted_devices')->where('ID', $requestId)->delete();
    }

    /**
     * Získá všechny emaily kromě zadaného uživatele.
     *
     * @param int $user_id ID uživatele, kterého chceme vynechat.
     * @return array Pole s emaily ostatních uživatelů.
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
