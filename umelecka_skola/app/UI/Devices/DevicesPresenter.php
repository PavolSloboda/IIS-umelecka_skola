<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use App\Core\RolesService;
use Nette;
use Nette\Application\UI\Form;


final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	private $devices; 
	private DevicesService $DevicesService;
	private $roles;

	public function __construct(DevicesService $devices, RolesService $roles, DevicesService $DevicesService,DevicesService $loan)
	{
		$this->devices = $devices;
		$this->roles = $roles;
		$this->DevicesService = $DevicesService;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
		$this->template->addFunction('isNotDeviceReserve', function (int $id) {return $this->devices->isNotDeviceReserve(intval($id));});
		
		$this->template->addFunction('isGroupEmpty', function (int $id) {return $this->devices->isGroupEmpty(intval($id));});

		$this->template->addFunction('getGroupById', function (int $id) {return $this->devices->getGroupById(intval($id));});
		$this->template->addFunction('getStatusById', function (int $id) {return $this->devices->getStatusById(intval($id));});
		$this->template->addFunction('hasCurrUserRole', function (string $role_name) {return $this->roles->userWithIdHasRoleWithId($this->getUser()->getId(), $this->roles->getRoleIdWithName($role_name));});
		$this->devices->ChangeStateReservation();
		$this->devices->updateLoanStatus();
	}

	//vypis vsech dostupnych zarizeni
	public function renderDevices() : void
	{
		//$this->devices->ChangeStateReservation();
		$this->template->devices = $this->devices->showAllDevices();
		$this->template->loans = $this->devices->showAllAvailableLoans($this->getUser()->getId());
		$this->template->types = $this->devices->showAllAvailableTypes();
	}

	public function createComponentAddDeviceForm() : Form
	{
		$form = new Form;
		
		
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		$form->addText('max_loan_duration', 'Max loan duration:')->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onSuccess[] = [$this, 'processAddDeviceForm'];
		return $form;
		
	}

	public function processAddDeviceForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->addDevice($values->name, $values->description, intval($values->max_loan_duration), $values->group_id);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function createComponentAddGroupForm() : Form
	{
		$form = new Form;
		
		
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onSuccess[] = [$this, 'processAddGroupForm'];
		return $form;
		
	}

	public function processAddGroupForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->addGroup($values->name, $values->description);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	//formular na pujceni zarizeni
	public function createComponentAddDeviceLoanForm() : Form
	{
		$form = new Form;

		$form->addHidden('device_id');
		// Zadání začátku výpůjčky (datum a čas)
		$form->addDateTime('loan_start', 'Start Date and Time:')->setFormat('Y-m-d H:i:s')
        ->setRequired('Please enter the start date and time.');
	
		$form->addDateTime('loan_end', 'End Date and Time:')->setFormat('Y-m-d H:i:s')
        ->setRequired('Please enter the end date and time.');
		
		$form->addSubmit('submit', 'Borrow Device');
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onValidate[] = [$this, 'validateAddDeviceLoanForm'];

		$form->onSuccess[] = [$this, 'processAddDeviceLoanForm'];

		return $form;
		
	}

	public function validateAddDeviceLoanForm(Form $form, \stdClass $values): void
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);

		$interval = $loanStart->diff($loanEnd);
		$maxLoanDuration = $this->DevicesService->getDeviceById(intval($values->device_id))->max_loan_duration;

		if ($interval->days > $maxLoanDuration) {
			$form->addError('The loan duration cannot exceed ' . $maxLoanDuration . ' days.');
		}

		if ($loanEnd < $loanStart) {
			$form->addError('The end date must be after the start date.');
		}

		$today = new \DateTime();
		$formattoday = $today->format('d-m-Y H:i');
		if ($today > $loanStart) {
			$form->addError('The earliest possible reservation start date is ' . $formattoday . '.');
		}
		
	}

	public function processAddDeviceLoanForm(Form $form, \stdClass $values): void
	{
		if ($form->hasErrors()) {
			return; 
		}

		$userId = $this->getUser()->getId();
		$deviceId = $values->device_id;

		// Kombinace data a času do jednoho řetězce pro databázi
		$loanStart = $values->loan_start;
		$loanEnd = $values->loan_end;

		try {
			$this->DevicesService->borrowDevice($userId, intval($deviceId), $loanStart, $loanEnd);
			$this->flashMessage('Device has been successfully borrowed.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function actionReserve($deviceId)
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$this->template->deviceName = $device->name;
		$form = $this->getComponent('addDeviceLoanForm');
		$form->setDefaults(['device_id' => $device->device_id]);
	}

	public function createComponentEditDeviceForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('device_id');
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		$form->addText('max_loan_duration', 'Max loan duration:')->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addCheckbox('loan', 'Device can not be borrowed');
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onSuccess[] = [$this, 'processDeviceEditForm'];
		return $form;
	}

	public function processDeviceEditForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->editDevice(intval($values->device_id), $values->name, $values->description, intval($values->max_loan_duration), intval($values->group_id), $values->loan);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function actionEdit($deviceId)
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$form = $this->getComponent('editDeviceForm');
		$form->setDefaults(['device_id' => $device->device_id, 'name' => $device->name, 'description' => $device->description, 'max_loan_duration' => $device->max_loan_duration, 'group_id' => $device->group_id, 'loan' => $device->loan]);
	}

	public function createComponentEditGroupForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('group_id');
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onSuccess[] = [$this, 'processGroupEditForm'];
		return $form;
		
	}

	public function processGroupEditForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->editGroup(intval($values->group_id), $values->name, $values->description);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function actionEditGroup($groupId)
	{
		$group = $this->devices->getGroupById(intval($groupId));
		$form = $this->getComponent('editGroupForm');
		$form->setDefaults(['group_id' => $group->group_id, 'name' => $group->name, 'description' => $group->description]);
	}

	public function createComponentEditReservationForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('loan_id');
		$form->addSelect('status', 'Status:', $this->devices->getLoanStatus())->setRequired();
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');
	
		$form->onSuccess[] = [$this, 'processReservationEditForm'];
		return $form;
	}
	
	public function processReservationEditForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->editReservation(intval($values->loan_id), intval($values->status));
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError('An error occurred');
		}
	}
	
	public function actionEditReservation($reservationId)
	{
		$device = $this->devices->getLoanById(intval($reservationId));
		$form = $this->getComponent('editReservationForm');
		$form->setDefaults(['loan_id' => $device->loan_id, 'status' => $device->status_id]);
	}
	

	public function handleCancelClicked() : void
	{
		$this->redirect('Devices:devices');
	}

	public function handleDeleteDevice(int $id) : void
	{
		$this->devices->deleteDevice($id);
		$this->forward('Devices:devices');
	}

	public function handleDeleteGroup(int $id) : void
	{
		$this->devices->deleteGroup($id);
		$this->forward('Devices:devices');
	}

	public function handleDeleteReservation(int $id) : void
	{
		$this->devices->deleteReservation($id);
		$this->forward('Devices:devices');
	}

}
