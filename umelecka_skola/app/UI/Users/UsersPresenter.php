<?php

declare(strict_types=1);

namespace App\UI\Users;

use App\Core\UsersService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\RolesService;


final class UsersPresenter extends Nette\Application\UI\Presenter
{
	private UsersService $usersService;
	private $roles;

	public function __construct(UsersService $usersService, RolesService $roles)
	{
		$this->usersService = $usersService;
		$this->roles = $roles;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}

		$this->template->addFunction('hasCurrUserRole', function (string $role_name) {return $this->roles->userWithIdHasRoleWithId($this->getUser()->getId(), $this->roles->getRoleIdWithName($role_name));});
	}

	public function renderUsers(): void
	{
		$this->template->users = $this->usersService->getUsers();
	}

	// Formulář pro editaci uživatele
	protected function createComponentEditUserForm(): Form
	{
    $form = new Form;
    $form->addText('name', 'Name:')
        ->setRequired('Please enter the user\'s name.');

    $form->addText('email', 'Email:')
        ->setRequired('Please enter the user\'s email.')
        ->addRule($form::EMAIL, 'Please enter a valid email address.');

    // Načtení seznamu rolí z RolesService
    $roles = $this->roles->getRoles();

    // Přidání možnosti "Žádná role"
    $roles[''] = 'No role'; // Případ pro "Žádnou roli" (prázdný klíč bude znamenat žádnou roli)

    $form->addSelect('role', 'Role:', $roles)
        ->setRequired('Please select a role.');
    $form->addSelect('role2', 'Role:', $roles)
        ->setRequired('Please select a role.');

    $form->addSubmit('submit', 'Save');
    $form->onSuccess[] = [$this, 'processEditUserForm'];

    return $form;
	}

	public function processEditUserForm(Form $form, \stdClass $values): void
	{
		$userId = $this->getUser()->getId();

    // Ověření unikátnosti emailu
    if (!$this->usersService->isEmailUnique($values->email, $userId)) {
        $form->addError('The email address is already in use.');
        return;
    }

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
    } else if(($roleId == 0 && $roleId2 != '') || ($roleId == 4 && $roleId2 != '') || ($roleId == 2 && ($roleId2 != 3 || $roleId2 != '')) || ($roleId == 3 && ($roleId2 != 2 || $roleId2 != ''))){
        $form->addError('Wrong role input.');
        return;
    }else if(($roleId2 == 0 && $roleId != '') || ($roleId2 == 4 && $roleId != '') || ($roleId2 == 2 && ($roleId != 3 || $roleId != '')) || ($roleId2 == 3 && ($roleId != 2 || $roleId != ''))){
        $form->addError('Wrong role input.');
        return;
    }
    else if ($roleId == 0 || $roleId2 == 0) {  // "0" znamená "Registrovaný uživatel" (žádná role)
        $this->usersService->removeUserRole($userId);
    }else if($roleId2 == ''){
    $this->usersService->updateUserRole($userId, $roleId);
    }else{
        $this->usersService->updateUserRole($userId, $roleId2);
    }
    $this->flashMessage('User information updated successfully.', 'success');
    $this->redirect('users');
	}

	public function actionEdit(int $userId): void
	{
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
    ]);
	}

	public function actionDelete(int $userId): void
	{
        try{
		$this->usersService->deleteUser($userId);
        }
        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }
        $this->usersService->removeUserRole($userId);
		$this->flashMessage('User deleted successfully.', 'success');
		$this->redirect('users');
	}
}