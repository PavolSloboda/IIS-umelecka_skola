<?php

declare(strict_types=1);

namespace App\UI\MyProfile;

use App\Core\MyProfileService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\RolesService;


final class MyProfilePresenter extends Nette\Application\UI\Presenter
{
    private MyProfileService $profileService;
    private $profile;
    private $roles;

    public function __construct(MyProfileService $profileService, RolesService $roles)
    {
        $this->profileService = $profileService;
        $this->roles = $roles;
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
        $form2 = new Form;
        $form2->addText('name', 'Name:')
                ->setHtmlAttribute('readonly', 'readonly')
            ->setDefaultValue($this->profile->name);
        $form2->addText('email', 'Email:')
            ->setHtmlAttribute('readonly', 'readonly')
            ->setDefaultValue($this->profile->email);
        //$form2->onSuccess[] = [$this, 'processInfoForm'];
            return $form2;
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
            $this->flashMessage('Password successfully changed.', 'success');
            $this->redrawControl('edit-profile-section'); // Zajistí obnovu pouze tohoto kontejneru
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
        $userId = $this->getUser()->getId();
        // Získání všech rolí uživatele
        $roles = $this->profileService->getMyProfileRoles($userId);

        // Předání rolí do šablony
        $this->template->ateliers = $this->profileService->getUserAteliers($userId);
        $this->template->roles = $roles;
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

    //request
    // Zobrazení žádostí o zařízení pro daného uživatele
    public function renderRequests(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->userDeviceRequests = $this->profileService->getUserDeviceRequests($userId);
    }

    public function handleDeleteRequest(int $requestId): void
    {
        $this->profileServicee->deleteRequest($requestId);
        $this->redirect('this');
    }

    public function actionCreateRequest(): void
    {
    $userId = $this->getUser()->getId();
    $name = $this->getHttpRequest()->getPost('name');
    $description = $this->getHttpRequest()->getPost('description');

    if ($name && $description) {
        $this->profileService->createRequest($userId, $name, $description);
        $this->flashMessage('Žádost byla úspěšně vytvořena.', 'success');
    } else {
        $this->flashMessage('Všechny údaje musí být vyplněny.', 'error');
    }

    $this->redirect('myprofile-section');
    }

    public function renderAtelierRequests(): void
    {
        // Načtení žádostí uživatele
        $userId = $this->getUser()->getId();
        $this->template->atelierRequests = $this->profileService->getUserAtelierRequests($userId);
    }

    public function handleCreateAtelierRequest(): void
    {
        // Zpracování žádosti o nový ateliér
        $userId = $this->getUser()->getId();
        $atelierName = $this->getParameter('atelierName'); // Získání jména z formuláře
        $this->profileService->createAtelierRequest($userId, $atelierName);
        $this->redirect('this');
    }

}
