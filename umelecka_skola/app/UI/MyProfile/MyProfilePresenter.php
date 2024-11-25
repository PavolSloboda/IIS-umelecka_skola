<?php

declare(strict_types=1);

namespace App\UI\MyProfile;

use App\Core\MyProfileService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\RolesService;

/**
 * Presenter responsible for handling user profile actions.
 */
final class MyProfilePresenter extends Nette\Application\UI\Presenter
{
    /** @var MyProfileService Service for profile-related operations */
    private MyProfileService $profileService;
    /** @var mixed Stores user profile data */
    private $profile;
    /** @var RolesService Service for role-related operations */
    private $roles;

    /**
     * Constructor.
     * @param MyProfileService $profileService Profile service for managing user profile data.
     * @param RolesService $roles Roles service for managing user roles.
     */
    public function __construct(MyProfileService $profileService, RolesService $roles)
    {
        $this->profileService = $profileService;
        $this->roles = $roles;
    }

    /**
     * Initializes the presenter and checks if the user is logged in.
     * Redirects to login if the user is not authenticated.
     */
    protected function startup(): void
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Login:login');
        }
        
        $userId = $this->getUser()->getId();
        $this->profile = $this->profileService->getUserProfile($userId);
        $this->template->userRequests = $this->profileService->getRequestsByUser($userId);
    }

    /**
     * Sets up the data for displaying the user profile.
     */
    public function actionMyProfile(): void
    {
        $this->template->profile = $this->profile;
        $this->template->loans = $this->profileService->getUserLoans($this->getUser()->getId());
        $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($this->getUser()->getId());
        $this->template->pastLoans = $this->profileService->getPastLoans($this->getUser()->getId());
    }

    /**
     * Prepares data for the profile edit page.
     */
    public function actionEdit(): void
    {
        $this->template->profile = $this->profile;
    }

    /**
     * Creates a form for editing user profile information.
     * @return Form The profile edit form.
     */
    protected function createComponentProfileForm(): Form
    {
        $form = new Form;

        $usersEmails = array();
        $userId = $this->getUser()->getId();

        $usersEmails = $this->profileService->getAllEmails($userId);

        $form->addText('name', 'Name:')
            ->setRequired()
            ->setDefaultValue($this->profile->name);
        $form->addText('email', 'Email:')
            ->setRequired()
            ->setDefaultValue($this->profile->email)
            ->addRule($form::IsNotIn, "Email already exist", $usersEmails);
        $form->addSubmit('submit', 'Save Changes');
        $form->onSuccess[] = [$this, 'processProfileForm'];
        return $form;
    }

    /**
     * Processes the profile form after submission.
     * @param Form $form Submitted form.
     * @param \stdClass $values Form values.
     */
    public function processProfileForm(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();

        $this->profileService->updateUserProfileEditForm($userId, [
            'name' => $values->name,
            'email' => $values->email,
        ]);
        $this->redirect('myprofile');
    }

    /**
     * Creates a form for changing the user password.
     * @return Form The password change form.
     */
    protected function createComponentChangePasswordForm(): Form
    {
        $form = new Form;
        $form->addPassword('old_password', 'Current Password:')
            ->setRequired();
        $form->addPassword('new_password', 'New Password:')
            ->setRequired()
            ->addRule($form::MinLength, 'Password must be at least %d characters long.', 8);
        $form->addPassword('confirm_password', 'Confirm New Password:')
            ->setRequired()
            ->addRule($form::Equal, 'Passwords do not match.', $form['new_password']);
        $form->addSubmit('submit', 'Change Password');
        $form->onSuccess[] = [$this, 'processChangePasswordForm'];
        return $form;
    }

    /**
     * Processes the password change form after submission.
     * @param Form $form Submitted form.
     * @param \stdClass $values Form values.
     */
    public function processChangePasswordForm(Form $form, \stdClass $values): void
    {
        $userId = $this->getUser()->getId();
        $oldPassword = $values->old_password;
        $newPassword = $values->new_password;
        $this->profileService->changePassword($userId, $oldPassword, $newPassword);
    }

    /**
     * Renders the past loans section.
     */
    public function renderPastLoans(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->pastLoans = $this->profileService->getPastLoans($userId);
    }

    /**
     * Renders the current loans section.
     */
    public function renderCurrentLoans(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->currentLoans = $this->profileService->getCurrentAndFutureLoans($userId);
    }

    /**
     * Renders the user's profile information.
     */
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

    /**
     * Renders the profile edit page.
     */
    public function renderEdit(): void
    {
        $this->template->profile = $this->profile;
    }

    /**
     * Renders the password change page.
     */
    public function renderPasswordChange(): void
    {
        $this->template->changePasswordForm = $this['changePasswordForm'];
    }

    /**
     * Renders the user's device requests.
     */
    public function renderRequests(): void
    {
        $userId = $this->getUser()->getId();
        $this->template->userDeviceRequests = $this->profileService->getUserDeviceRequests($userId);
    }

    /**
     * Deletes a device request.
     * @param int $requestId ID of the request to delete.
     */
    public function handleDeleteRequest(int $requestId): void
    {
        $this->profileService->deleteRequest($requestId);
        $this->redirect('this');
    }

    /**
     * Action for creating a new device request.
     */
    public function actionCreateRequest(): void
    {
    $userId = $this->getUser()->getId();
    $name = $this->getHttpRequest()->getPost('name');
    $description = $this->getHttpRequest()->getPost('description');

    $this->profileService->createRequest($userId, $name, $description);

    $this->redirect('myprofile-section');
    }

     /**
     * Creates a form for submitting a device request.
     * @return Form The device request form.
     */
    protected function createComponentDeviceRequestForm(): Form
    {
    $form = new Form;
    $form->addText('name', 'Device name: ')->setRequired();

    $form->addText('description', 'Device description: ');

    $form->addSubmit('send', 'Submit request');
    $form->onSuccess[] = [$this, 'processDeviceRequestForm'];

    return $form;
    }

    /**
     * Processes the device request form after submission.
     * @param Form $form Submitted form.
     * @param \stdClass $values Form values.
     */
    public function processDeviceRequestForm(Form $form, \stdClass $values): void
    {
    $userId = $this->getUser()->getId();
    $this->profileService->createDeviceRequest($userId, $values->name, $values->description);
    $this->redirect('this');
    }
}
