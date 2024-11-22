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

		$form->addSelect('role', 'Role:', [
			'user' => 'User',
			'admin' => 'Admin',
		])->setRequired('Please select a role.');

		$form->addSubmit('submit', 'Save');
		$form->onSuccess[] = [$this, 'processEditUserForm'];

		return $form;
	}

	public function processEditUserForm(Form $form, \stdClass $values): void
	{
		//$userId = $this->getParameter('userId');
		$userId = $this->getUser()->getId();

		// Ověření unikátnosti emailu
		if (!$this->usersService->isEmailUnique($values->email, $userId)) {
			$form->addError('The email address is already in use.');
			return;
		}

		$this->usersService->updateUser($userId, [
			'name' => $values->name,
			'email' => $values->email,
			//'role' => $values->role,
		]);
		
		$this->usersService->updateUserRole($userId, $values->role);

		$this->flashMessage('User information updated successfully.', 'success');
		$this->redirect('users');
	}

	public function actionEdit(int $userId): void
	{
		$user = $this->usersService->getUserById($userId);

		if (!$user) {
			$this->error('User not found');
		}

		$this['editUserForm']->setDefaults([
			'name' => $user->name,
			'email' => $user->email,
			//'role' => $user->role,
		]);
	}

	public function actionDelete(int $userId): void
	{
		$this->usersService->deleteUser($userId);
		$this->flashMessage('User deleted successfully.', 'success');
		$this->redirect('users');
	}
}
