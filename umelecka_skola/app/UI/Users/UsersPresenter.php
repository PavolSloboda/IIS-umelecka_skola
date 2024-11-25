<?php

declare(strict_types=1);

/**
 * UsersPresenter handles user-related actions in the application, 
 * including user listing, editing, and deletion.
 * It also provides forms for editing users and updating roles.
 *
 * @package App\UI\Users
 */

namespace App\UI\Users;

use App\Core\UsersService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\RolesService;


final class UsersPresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var UsersService Service for handling user-related database operations.
     */
	private UsersService $usersService;
    /**
     * @var RolesService Service for handling role-related operations.
     */
	private $roles;
    /**
     * @var int The ID of the user being edited.
     */
    private int $userId_edit;

    /**
     * Constructor for UsersPresenter.
     *
     * @param UsersService $usersService The user service instance.
     * @param RolesService $roles The roles service instance.
     */
	public function __construct(UsersService $usersService, RolesService $roles)
	{
		$this->usersService = $usersService;
		$this->roles = $roles;
	}

    /**
     * Initializes the presenter and checks if the user is logged in.
     * If not, redirects to the login page.
     */
	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}

		$this->template->addFunction('hasCurrUserRole', function (string $role_name) {return $this->roles->userWithIdHasRoleWithId($this->getUser()->getId(), $this->roles->getRoleIdWithName($role_name));});
	}

    /**
     * Renders the list of users.
     */
	public function renderUsers(): void
	{
		$this->template->users = $this->usersService->getUsers();
	}

	/**
     * Creates the form for editing a user, allowing to edit name, email, and role.
     *
     * @return Form The form for editing a user.
     */
	protected function createComponentEditUserForm(): Form
	{
        $form = new Form;

        $form->addHidden('user_id');
        $form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();

        $usersEmails = array();
        $usersEmails = $this->usersService->getAllEmails($this->userId_edit);

        $form->addEmail('email', 'Email:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->addRule($form::IsNotIn, "Email already exist", $usersEmails)->setRequired();

        // Načtení seznamu rolí z RolesService
        $roles = $this->roles->getRoles();

        // Přidání možnosti "Žádná role"
        $roles[''] = 'No role'; // Případ pro "Žádnou roli" (prázdný klíč bude znamenat žádnou roli)

        $form->addSelect('role', 'Role:', $roles);
            //->setRequired('Please select a role.');
        $form->addSelect('role2', 'Role:', $roles);
            //->setRequired('Please select a role.');

        $form->addSubmit('submit', 'Save');
        $form->onSuccess[] = [$this, 'processEditUserForm'];

        return $form;
        }

        /**
        *  Processes the edit user form and updates the user data and roles.
        *
        * @param Form $form The form instance.
        * @param \stdClass $values The values submitted by the form.
        */
        public function processEditUserForm(Form $form, \stdClass $values): void
        {
            //$userId = $this->getUser()->getId();
            $userId = $this->template->userToEdit->user_id;

        // Aktualizace údajů o uživateli
        $this->usersService->updateUser($userId, [
            'name' => $values->name,
            'email' => $values->email,
        ]);

        // Aktualizace role
        $roleId = $values->role;
        $roleId2 = $values->role2;
        if(($roleId == 2 && $roleId2 == 3) || ($roleId == 3 && $roleId2 == 2)){
            $this->usersService->updateUserRoletwo($userId, $roleId, $roleId2);
        } else if(($roleId == 0 && $roleId2 != '') || ($roleId == 4 && $roleId2 != '') || ($roleId == 2 && ($roleId2 != 3 || $roleId2 != '')) || ($roleId == 3 && ($roleId2 != 2 || $roleId2 != ''))|| ($roleId == '' && $roleId2 == '')){
            $this->redirect('users');
        }else if(($roleId2 == 0 && $roleId != '') || ($roleId2 == 4 && $roleId != '') || ($roleId2 == 2 && ($roleId != 3 || $roleId != '')) || ($roleId2 == 3 && ($roleId != 2 || $roleId != ''))){
            $this->redirect('users');
        }
        else if ($roleId == 0 || $roleId2 == 0) {  // "0" znamená "Registrovaný uživatel" (žádná role)
            $this->usersService->removeUserRole($userId);
        }else if($roleId2 == ''){
        $this->usersService->updateUserRole($userId, $roleId);
        }else{
            $this->usersService->updateUserRole($userId, $roleId2);
        }
        $this->redirect('users');
	    }

    /**
     * Action for editing a specific user.
     *
     * @param int $userId The ID of the user to edit.
     */
	public function actionEdit(int $userId): void
	{
        $this->userId_edit = $userId;
        $user = $this->usersService->getUserById($userId);
        $roleId = $this->usersService->getUserRoleId($userId); // Získejte aktuální `role_id`

        if (!$user) {
            $this->error('User not found');
        }
        $this->template->userToEdit = $user;

        $this['editUserForm']->setDefaults([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleId, // Nastavení aktuální role
            'user_id' => $user->user_id,
        ]);
	}

    /**
     * Action for deleting a specific user.
     *
     * @param int $userId The ID of the user to delete.
     */
	public function actionDelete(int $userId): void
	{
		$this->usersService->deleteUser($userId);

        $this->usersService->removeUserRole($userId);
		$this->redirect('users');
	}
}
