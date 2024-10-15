<?php

declare(strict_types=1);

namespace App\UI\MyProfile;

use App\Core\MyProfileService;
use Nette;
use Nette\Application\UI\Form;

final class MyProfilePresenter extends Nette\Application\UI\Presenter
{
    private MyProfileService $profileService;

    public function __construct(MyProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    protected function startup(): void
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Login:login');
        }
    }

    // Zobrazení údajů o profilu
    public function renderMyProfile(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->profile = $this->profileService->getUserProfile($userId);
        $this->template->devices = $this->profileService->getAvailableDevices();
        $this->template->loans = $this->profileService->getUserLoans($userId);
         // Získání aktuálních a budoucích výpůjček
         $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($userId);

         // Získání minulých výpůjček
         $this->template->pastLoans = $this->profileService->getPastLoans($userId);
    }

    // Formulář pro úpravu údajů o profilu
    public function createComponentProfileForm(): Form
    {
        $userId = $this->getUser()->getId();
        $profile = $this->profileService->getUserProfile($userId);

        $form = new Form;
        $form->addText('name', 'Name:')
            ->setRequired('Please enter your name.')
            ->setDefaultValue($profile->name);
        $form->addText('email', 'Email:')
            ->setRequired('Please enter your email.')
            ->setDefaultValue($profile->email);
        $form->addSubmit('submit', 'Save Changes');
        $form->onSuccess[] = [$this, 'processProfileForm'];

        return $form;
    }

    public function processProfileForm(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();

        // Kontrola, zda je e-mail unikátní
        if (!$this->profileService->isEmailUnique($values->email, $userId)) {
            $form->addError('The email address is already in use by another account.');
            return;
        }

        // Pokud je e-mail unikátní, pokračujeme s aktualizací profilu
        $this->profileService->updateUserProfileEditForm($userId, [
            'name' => $values->name,
            'email' => $values->email,
        ]);

        $this->flashMessage('Profile updated successfully.', 'success');
        $this->redirect('this');
    }

    // Formulář pro rezervaci zařízení
    public function createComponentReserveDeviceForm(): Form
    {
        $form = new Form;
        $form->addSelect('device_id', 'Select Device:', $this->profileService->getAvailableDevices())
            ->setPrompt('Choose a device')
            ->setRequired('Please select a device.');
        $form->addDateTime('loan_start', 'Start Date and Time:')
            ->setRequired('Please enter the start date and time.');
        $form->addDateTime('loan_end', 'End Date and Time:')
            ->setRequired('Please enter the end date and time.');
        $form->addSubmit('submit', 'Reserve Device');
        $form->onValidate[] = [$this, 'validateReserveDeviceForm'];
        $form->onSuccess[] = [$this, 'processReserveDeviceForm'];

        return $form;
    }

    public function processReserveDeviceForm(Form $form, \stdClass $values): void
    {
        if ($form->hasErrors()) {
            return;
        }

        $userId = $this->getUser()->getId();
        $this->profileService->borrowDevice($userId, $values->device_id, $values->loan_start, $values->loan_end);

        $this->flashMessage('Device reserved successfully.', 'success');
        $this->redirect('this');
    }

    protected function createComponentChangePasswordForm(): Form
    {
        $form = new Form;

        // Původní heslo
        $form->addPassword('old_password', 'Current Password:')
            ->setRequired('Please enter your current password.');

        // Nové heslo
        $form->addPassword('new_password', 'New Password:')
            ->setRequired('Please enter a new password.')
            ->addRule($form::MIN_LENGTH, 'Password must be at least %d characters long.', 8);

        // Potvrzení nového hesla
        $form->addPassword('confirm_password', 'Confirm New Password:')
            ->setRequired('Please confirm your new password.')
            ->addRule($form::EQUAL, 'Passwords do not match.', $form['new_password']);

        // Tlačítko pro změnu hesla
        $form->addSubmit('submit', 'Change Password');

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
            $this->redirect('this');
        } catch (\Exception $e) {
            $form->addError('Failed to change password. ' . $e->getMessage());
        }
    }
    
}
