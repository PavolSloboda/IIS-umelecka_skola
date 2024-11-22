<?php

declare(strict_types=1);

namespace App\UI\MyProfile;

use App\Core\MyProfileService;
use Nette;
use Nette\Application\UI\Form;

final class MyProfilePresenter extends Nette\Application\UI\Presenter
{
    private MyProfileService $profileService;
    private $profile;

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
        
        $userId = $this->getUser()->getId();
        $this->profile = $this->profileService->getUserProfile($userId);
    }

    public function actionMyProfile(): void
    {
        $this->template->profile = $this->profile;
        $this->template->loans = $this->profileService->getUserLoans($this->getUser()->getId());
        $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($this->getUser()->getId());
        $this->template->pastLoans = $this->profileService->getPastLoans($this->getUser()->getId());
    }

    public function actionEdit(): void
    {
        $this->template->profile = $this->profile;
    }

    

    protected function createComponentInfoForm(): Form
    {
        //echo '<h2>Profile Information</h2>';
        $form2 = new Form;
        $form2->addText('name', 'Name:')
                ->setHtmlAttribute('readonly', 'readonly')
            ->setDefaultValue($this->profile->name);
        $form2->addText('email', 'Email:')
            ->setHtmlAttribute('readonly', 'readonly')
            ->setDefaultValue($this->profile->email);
            return $form2;
    }

    public function processInfoForm(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();
        $this->profileService->updateUserProfileEditForm($userId, [
            'name' => $values->name,
            'email' => $values->email,
        ]);
    }

    protected function createComponentProfileForm(): Form
    {
        //echo '<h2>Edit Profile</h2>';
        $form = new Form;
        $form->addText('name', 'Name:')
            ->setRequired()
            ->setDefaultValue($this->profile->name);
        $form->addText('email', 'Email:')
            ->setRequired()
            ->setDefaultValue($this->profile->email);
        $form->addSubmit('submit', 'Save Changes');
        $form->onSuccess[] = [$this, 'processProfileForm'];
        return $form;
    }

    public function processProfileForm(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();
        if (!$this->profileService->isEmailUnique($values->email, $userId)) {
            $form->addError('The email address is already in use by another account.');
            return;
        }
        $this->profileService->updateUserProfileEditForm($userId, [
            'name' => $values->name,
            'email' => $values->email,
        ]);
        $this->flashMessage('Profile updated successfully.', 'success');
        //$this->redrawControl('profile-section');
        $this->redrawControl('edit-profile-section');
        
    }

    protected function createComponentChangePasswordForm(): Form
    {
        $form = new Form;
        $form->addPassword('old_password', 'Current Password:')
            ->setRequired();
        $form->addPassword('new_password', 'New Password:')
            ->setRequired()
            ->addRule($form::MIN_LENGTH, 'Password must be at least %d characters long.', 8);
        $form->addPassword('confirm_password', 'Confirm New Password:')
            ->setRequired()
            ->addRule($form::EQUAL, 'Passwords do not match.', $form['new_password']);
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
            $this->redrawControl('edit-profile-section'); // Zajistí obnovu pouze tohoto kontejneru
            $this->flashMessage('Password successfully changed.', 'success');
            //$this->redirect('MyProfile:myProfile');
        } catch (\Exception $e) {
            $form->addError('Failed to change password. ' . $e->getMessage());
            //$this->redrawControl('password-change-section'); // Zajistí obnovu pouze tohoto kontejneru
            $this->redrawControl('edit-profile-section');
        }
    }

    public function renderPastLoans(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->pastLoans = $this->profileService->getPastLoans($userId);
    }

    public function renderCurrentLoans(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($userId);
    }

    public function renderMyProfile(): void
    {
        $this->template->profile = $this->profile;
        $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($this->getUser()->getId());
        $this->template->pastLoans = $this->profileService->getPastLoans($this->getUser()->getId());
    }

    public function renderEdit(): void
    {
        $this->template->profile = $this->profile;
    }

    public function renderPasswordChange(): void
    {
        $this->template->changePasswordForm = $this['changePasswordForm'];
    }
}
