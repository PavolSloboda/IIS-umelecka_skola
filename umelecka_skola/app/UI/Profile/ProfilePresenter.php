<?php

declare(strict_types=1);

namespace App\UI\Profile;

use Nette\Application\UI\Presenter;

class ProfilePresenter extends Presenter
{
    // Akce pro zobrazení profilu
    public function actionDefault(): void
    {
        // Získání profilu uživatele (příklad)
        $this->template->profile = $this->getProfile();
    }

    // Metoda pro načtení uživatelského profilu (příklad)
    private function getProfile()
    {
        // Tento příklad vrací fiktivní data, ale můžeš to napojit na reálnou databázi
        return (object)[
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ];
    }

    // Akce pro zpracování formuláře na úpravu profilu (volitelné)
    public function handleUpdateProfile($name, $email)
    {
        // Logika pro aktualizaci profilu
        // Například: Uložit do databáze
        $this->flashMessage('Profile updated successfully', 'success');
        $this->redirect('this');  // Reload aktuální stránky
    }
}
