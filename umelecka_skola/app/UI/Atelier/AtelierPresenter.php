<?php

declare(strict_types=1);

namespace App\UI\Atelier;

use App\Core\AtelierService;
use App\Core\RolesService;
use App\Core\UsersService;
use Nette\Application\UI\Form;
use Nette;

final class AtelierPresenter extends Nette\Application\UI\Presenter
{
	private $atelier;
	private $roles;
	private $users;

	public function __construct(AtelierService $atelier, RolesService $roles, UsersService $users)
	{
		$this->atelier = $atelier;
		$this->roles = $roles;
		$this->users = $users;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
		$this->template->addFunction('getAdminEmailById', function (int $id) {return $this->atelier->getAdminEmailByAtelierId(intval($id));});
		$this->template->addFunction('isCurrUserAdmin', function (int $id) {return $this->atelier->isCurrUserAdminOfAtelierWithId(intval($id), $this->getUser()->getId());});
		$this->template->addFunction('hasCurrUserRole', function (string $role_name) {return $this->roles->userWithIdHasRoleWithId($this->getUser()->getId(), $this->roles->getRoleIdWithName($role_name));});
	}

	public function renderAtelier() : void
	{
		$this->template->result = $this->atelier->showAllAteliers();
	}

	public function renderTable() : void
	{
		$this->template->result = $this->atelier->showAllAteliers();
	}

	public function renderEdit(): void
	{
		$this->template->user_items = $this->users->getUsersBelongingToAtelier(5);
		$this->template->user_items_not = $this->users->getUsersNotBelongingToAtelier(5);
	}

	protected function createComponentAddAtelierForm(): Form
	{
		$form = new Form;

		$form->addHidden('atelier_id');
		$form->addText('name', 'Name:')->setRequired();  
		$form->addText('admin_email', 'Email of atelier admin:')->setRequired();

		$form->addSubmit('submit', 'Add atelier');

		$form->onSuccess[] = [$this, 'processAddAtelierForm'];
		
		return $form;
	}

	protected function createComponentEditAtelierForm(): Form
	{
		$form = new Form;

		$form->addHidden('atelier_id');
		$form->addText('name', 'Name:')->setRequired();  
		$form->addText('admin_email', 'Email of atelier admin:')->setRequired();

		$form->addSubmit('submit', 'Confirm changes');

		$form->onSuccess[] = [$this, 'processEditAtelierForm'];

		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');
		
		return $form;
	}

	public function processAddAtelierForm(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->atelier->createAtelier($data->name, $data->admin_email);
		}
		catch (\Exception $e)
		{
			$form->addError('An error occured while atempting to ad an atelier');
		}
	}

	public function handleDelete(int $id) : void
	{
		$this->atelier->deleteAtelier($id);
		$this->forward('Atelier:table');
	}	

	public function actionEdit(string $atelierId) : void
	{
		$atelier = $this->atelier->getAtelierById(intval($atelierId));
		$form = $this->getComponent('editAtelierForm');
		$form->setDefaults(['atelier_id' => $atelier->atelier_id, 'name' => $atelier->name, 'admin_email' => $this->atelier->getAdminEmailByAtelierId($atelier->atelier_id)]);
		//$form->onSuccess[] = [$this, 'editFormSucceeded'];
	}

	public function processEditAtelierForm(Form $form, \stdClass $data) : void
	{
		$this->atelier->editAtelier(intval($data->atelier_id), $data->name, $data->admin_email);
		$this->redirect('Atelier:atelier');
	}

	public function handleCancelClicked() : void
	{
		$this->redirect('Atelier:atelier');
	}

	public function handleAdd(int $user_id) : void
	{
		$form = $this->getComponent('editAtelierForm');
		//bdump($form->);
		//$this->atelier->AddUserWithIdToAtelierWithId($user_id, $form->atelier_id);
		//$this->forward('Atelier:table');
	}	
}
