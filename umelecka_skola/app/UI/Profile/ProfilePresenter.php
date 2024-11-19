<?php

declare(strict_types=1);

namespace App\UI\Profile;

use Nette\Application\UI\Presenter;

class ProfilePresenter extends Presenter
{
    public function actionInfo(): void
    {
        // Příklad získání dat profilu - zde můžeš napojit skutečnou databázi
        $this->template->profile = $this->getProfile();
    }

    private function getProfile()
    {
        // Zde vracíme fiktivní data o profilu
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
