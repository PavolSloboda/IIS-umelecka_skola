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
	private	$curr_edit; 

	public function __construct(AtelierService $atelier, RolesService $roles, UsersService $users)
	{
		$this->atelier = $atelier;
		$this->roles = $roles;
		$this->users = $users;
		$this->curr_edit = null;
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
		$this->template->addFunction('atelier_is_empty', function (int $id) {return $this->atelier->isAtelierEmpty($id);});
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
		$this->template->user_items = $this->users->getUsersBelongingToAtelier(intval($this->curr_edit));
		$this->template->user_items_not = $this->users->getUsersNotBelongingToAtelier(intval($this->curr_edit));
	}

	protected function createComponentEditAtelierForm(): Form
	{
		$form = new Form;
		///otestovat a prespsat mna to druhe misto
	
		$adminAtelierEmails = $this->atelier->getAdminAtelierEmails();

		$form->addHidden('atelier_id');
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();  
		$form->addEmail('admin_email', 'Email of atelier admin:')->addRule($form::IsIn, "User is not admin", $adminAtelierEmails)->setRequired();

		$form->addSubmit('submit', 'Confirm changes');

		$form->onSuccess[] = [$this, 'processEditAtelierForm'];
		
		return $form;
	}

	protected function createComponentAddAtelierForm(): Form
	{
		$form = new Form;

		$adminAtelierEmails = $this->atelier->getAdminAtelierEmails();

		$form->addHidden('atelier_id');
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();  
		$form->addEmail('admin_email', 'Email of atelier admin:')->addRule($form::IsIn, "User is not admin", $adminAtelierEmails)->setRequired();

		$form->addSubmit('submit', 'Add atelier');

		$form->onSuccess[] = [$this, 'processAddAtelierForm'];
		
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

		}
		$this->forward('Atelier:atelier');
	}

	public function processEditAtelierForm(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->atelier->editAtelier(intval($data->atelier_id), $data->name, $data->admin_email);
		}
		catch (\Exception $e)
		{

		}
		$this->redirect('Atelier:atelier');
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
		$this->curr_edit = $atelierId;
		//$form->onSuccess[] = [$this, 'editFormSucceeded'];
	}



	public function handleAdd(int $user_id) : void
	{
		$this->atelier->addUserWithIdToAtelierWithId($user_id, intval($this->curr_edit));
	}	

	public function handleRemove(int $user_id) : void
	{
		$this->atelier->removeUserWithIdFromAtelierWithId($user_id, intval($this->curr_edit));
	}
}
