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

    protected function createComponentProfileForm(): Form
    {
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
        $this->redirect('this');
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
        try {
            $this->profileService->changePassword($userId, $values->old_password, $values->new_password);
            $this->flashMessage('Password successfully changed.', 'success');
            $this->redirect('this');
        } catch (\Exception $e) {
            $form->addError('Failed to change password. ' . $e->getMessage());
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
        // Zajistí, že formulář pro změnu hesla je připraven k zobrazení
        $this->template->changePasswordForm = $this['changePasswordForm'];
    }
}
