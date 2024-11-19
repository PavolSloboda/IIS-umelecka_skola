<?php

declare(strict_types=1);

namespace App\UI\Profile;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;

class ProfilePresenter extends Presenter
{
    public function actionInfo(): void
    {
        // Příklad získání dat profilu - zde můžeš napojit skutečnou databázi
        $this->template->profile = $this->getProfile();
    }



    protected function createComponentProfileForm(): \Nette\Application\UI\Form
    {
        $form = new \Nette\Application\UI\Form;
        $profile = $this->getProfile();  // Získáme aktuální data o profilu

        $form->addText('name', 'Name:')
            ->setDefaultValue($profile->name)
            ->setRequired();

        $form->addText('email', 'Email:')
            ->setDefaultValue($profile->email)
            ->setRequired();

        $form->addSubmit('submit', 'Save');
        $form->onSuccess[] = [$this, 'profileFormSucceeded'];

        return $form;
    }

    public function profileFormSucceeded(\Nette\Application\UI\Form $form, $values): void
    {
        // Zde proveď uložení upravených údajů (například do databáze)
        $this->flashMessage('Profile updated successfully.', 'success');
        $this->redirect('info');
    }

    private function getProfile()
    {
        return (object)[
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ];
    }

    protected function createComponentChangePasswordForm(): Form
    {
    $form = new Form;

    // Pole pro aktuální heslo
    $form->addPassword('old_password', 'Current Password:')
        ->setRequired('Please enter your current password.');

    // Pole pro nové heslo
    $form->addPassword('new_password', 'New Password:')
        ->setRequired('Please enter a new password.')
        ->addRule($form::MIN_LENGTH, 'Password must be at least %d characters long.', 8);

    // Pole pro potvrzení nového hesla
    $form->addPassword('confirm_password', 'Confirm New Password:')
        ->setRequired('Please confirm your new password.')
        ->addRule($form::EQUAL, 'Passwords do not match.', $form['new_password']);

    // Tlačítko pro odeslání formuláře
    $form->addSubmit('submit', 'Change Password');

    // Zpracování formuláře po úspěšném odeslání
    $form->onSuccess[] = [$this, 'processChangePasswordForm'];

    return $form;
    }

    public function processChangePasswordForm(Form $form, \stdClass $values): void
    {
    $userId = $this->getUser()->getId();
    $oldPassword = $values->old_password;
    $newPassword = $values->new_password;

    try {
        // Ověření a změna hesla pomocí služby
        $this->profileService->changePassword($userId, $oldPassword, $newPassword);
        $this->flashMessage('Password successfully changed.', 'success');
        $this->redirect('this'); // Při úspěchu přesměrujeme na aktuální stránku
    } catch (\Exception $e) {
        $form->addError('Failed to change password. ' . $e->getMessage());
    }
    }
}
