<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use App\Core\RolesService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\UsersService;


final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	private $devices; 
	private DevicesService $DevicesService;
	private $roles;
	private $users;
	private $curr_edit;
	private $wanted_devices;

	public function __construct(DevicesService $devices, RolesService $roles, DevicesService $DevicesService, UsersService $users)
	{
		$this->devices = $devices;
		$this->roles = $roles;
		$this->DevicesService = $DevicesService;
		$this->users = $users;
		$this->curr_edit = null;
		$this->curr_stat = null;
		//$this->wanted_devices = $wanted_devices;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
		$this->template->addFunction('isNotDeviceReserve', function (int $id) {return $this->devices->isNotDeviceReserve(intval($id));});
		$this->template->addFunction('isDeviceInMyAtelier', function (int $id) {return $this->devices->isDeviceInMyAtelier($this->getUser()->getId(),intval($id));});
		$this->template->addFunction('isGroupEmpty', function (int $id) {return $this->devices->isGroupEmpty(intval($id));});
		$this->template->addFunction('getCurrUser',  function () {return $this->getUser()->getId();});
		$this->template->addFunction('getGroupById', function (int $id) {return $this->devices->getGroupById(intval($id));});
		$this->template->addFunction('getStatusById', function (int $id) {return $this->devices->getStatusById(intval($id));});
		$this->template->addFunction('getAtelierById', function (int $id) {return $this->devices->getAtelierById(intval($id));});
		$this->template->addFunction('getDeviceById', function (int $id) {return $this->devices->getDeviceById(intval($id));});
		$this->template->addFunction('hasCurrUserRole', function (string $role_name) {return $this->roles->userWithIdHasRoleWithId($this->getUser()->getId(), $this->roles->getRoleIdWithName($role_name));});
		$this->template->addFunction('getDeviceLoans', function (int $id) {return $this->devices->showDeviceLoans(intval($id));});
		$this->devices->changeStateReservation();
	}

	//vypis vsech dostupnych zarizeni
	public function renderDevices() : void
	{
		$this->template->devices = $this->devices->showAllDevices();
		$this->template->loans = $this->devices->showAllAvailableLoans();
		$this->template->types = $this->devices->showAllAvailableTypes();

		$this->template->wanted_devices = $this->devices->getDeviceRequests();
	}

	public function renderEdit() : void
	{
		$this->template->forbidden_users = $this->devices->get_forbidden_users(intval($this->curr_edit));
		$this->template->not_forbidden_users = $this->devices->get_not_forbidden_users(intval($this->curr_edit));
	}

	public function renderRequests(): void
	{
    // Fetch all pending device requests for the teacher
    $this->template->wanted_devices = $this->devices->getDeviceRequests();
	}

	public function renderStats() : void
	{
		$this->template->loyal_customer = $this->devices->get_loyal_customer(intval($this->curr_stat));
		$this->template->last_loan = $this->devices->get_last_loan(intval($this->curr_stat));
		$this->template->number_of_device_loans = $this->devices->get_number_of_device_loans(intval($this->curr_stat));
		$this->template->number_of_loans = $this->devices->get_number_of_loans(intval($this->curr_stat));
		$this->template->avg_loan_time = $this->devices->get_avg_loan_time(intval($this->curr_stat));
		$this->template->longest_loan_time = $this->devices->get_longest_loan_time(intval($this->curr_stat));
		$this->template->shortest_loan_time = $this->devices->get_shortest_loan_time(intval($this->curr_stat));
	}
//datum posledni vypujcky
//celkovy pocet vypujcek
//a taky jake procento to tvori ze vsech vypujcek
//prumerna doba vypujcky
//nejdelsi vypujcka
//nejkratsi vypujcka
//nejcasteji vypujceno od
	public function actionStats($deviceId): void
	{
		$this->curr_stat = $deviceId;
	}

	public function createComponentAddDeviceForm() : Form
	{
		$form = new Form;
		
		
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		$form->addInteger('max_loan_duration', 'Max loan duration:')->addRule($form::Range, 'Loan duration must be between %d and %d.', [1, 90])->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addSelect('atelier_id', 'Atelier:', $this->devices->getUserAtelier($this->getUser()->getId()))->setRequired();
		$form->addCheckbox('loan', 'Device can not be borrowed');
		$form->addSubmit('submit', 'Submit changes');
		

		$form->onSuccess[] = [$this, 'processAddDeviceForm'];
		return $form;
		
	}

	public function processAddDeviceForm(Form $form, \stdClass $values): void
	{
		try {
			$userId = $this->getUser()->getId();
			$this->DevicesService->addDevice($userId, $values->name, $values->description, intval($values->max_loan_duration), $values->group_id, $values->atelier_id, $values->loan);
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

		$form->onValidate[] = [$this, 'validateAddDeviceLoanForm'];

		$form->onSuccess[] = [$this, 'processAddDeviceLoanForm'];

		return $form;
		
	}

	public function validateAddDeviceLoanForm(Form $form, \stdClass $values): void
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);

		$retval = $this->DevicesService->validateDate($loanStart,$loanEnd,intval($values->device_id));
		if($retval !== null)
		{
			$form->addError($retval);
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

	public function actionReserve($deviceId): void
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$this->template->device = $device;
		$form = $this->getComponent('addDeviceLoanForm');
		$form->setDefaults(['device_id' => $device->device_id]);
	}

	public function createComponentEditDeviceForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('device_id');
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		$form->addInteger('max_loan_duration', 'Max loan duration:')->addRule($form::Range, 'Loan duration must be between %d and %d.', [1, 90])->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addSelect('atelier_id', 'Atelier:', $this->devices->getUserAtelier($this->getUser()->getId()))->setRequired();
		$form->addCheckbox('loan', 'Device can not be borrowed');
		$form->addSubmit('submit', 'Submit changes');

		$form->onSuccess[] = [$this, 'processDeviceEditForm'];
		return $form;
	}

	public function processDeviceEditForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->editDevice(intval($values->device_id), $values->name, $values->description, intval($values->max_loan_duration), intval($values->group_id),$values->atelier_id , $values->loan);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function actionEdit($deviceId): void
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$form = $this->getComponent('editDeviceForm');
		$form->setDefaults(['device_id' => $device->device_id, 'name' => $device->name, 'description' => $device->description, 'max_loan_duration' => $device->max_loan_duration, 'group_id' => $device->group_id,'atelier_id' => $device->atelier_id, 'loan' => $device->loan]);
		$this->curr_edit = $deviceId;
	}

	public function createComponentEditGroupForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('group_id');
		$form->addText('name', 'Name:')->setRequired();
		$form->addText('description', 'Description:')->setRequired();
		
		$form->addSubmit('submit', 'Submit changes');
		
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

	public function actionEditGroup($groupId): void
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
	
	public function actionEditReservation($reservationId): void
	{
		$device = $this->devices->getLoanById(intval($reservationId));
		$form = $this->getComponent('editReservationForm');
		$form->setDefaults(['loan_id' => $device->loan_id, 'status' => $device->status_id]);
		$this->template->loan = $device;
		$form = $this->getComponent('editLoanEndDateForm');
		$form->setDefaults(['loan_id' => $device->loan_id, 'loan_end' => $device->loan_end, 'device_id' => $device->device_id,]);
	}
	
	public function createComponentEditLoanEndDateForm() : Form
	{
		$form = new Form;

		$form->addHidden('loan_id');
		$form->addHidden('loan_start');
		$form->addHidden('device_id');
		
		$form->addDateTime('loan_end', 'End Date and Time:')->setFormat('Y-m-d H:i:s')
        ->setRequired('Please enter new end date and time.');
		
		$form->addSubmit('submit', 'Change end date');

		$form->onValidate[] = [$this, 'validateEditLoanEndDateForm'];
		$form->onSuccess[] = [$this, 'processEditLoanEndDateForm'];
		
		return $form;
		
	}

	public function validateEditLoanEndDateForm(Form $form, \stdClass $values): void
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);

		$retval = $this->DevicesService->validateEditDate(intval($values->loan_id),$loanStart,$loanEnd,intval($values->device_id));
		if($retval !== null)
		{
			$form->addError($retval);
		}
		
	}

	public function processEditLoanEndDateForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->EditLoanEndDate(intval($values->loan_id), $values->loan_end);
			$this->flashMessage('End date has been successfully changed.', 'success');
			
			$this->redirect('Devices:devices');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function handleDeleteDevice(int $id) : void
	{
		$this->devices->deleteDevice($id);
		$this->redirect('Devices:devices');
	}

	public function handleDeleteGroup(int $id) : void
	{
		$this->devices->deleteGroup($id);
		$this->redirect('Devices:devices');
	}

	public function handleDeleteReservation(int $id) : void
	{
		$this->devices->deleteReservation($id);
		$this->redirect('Devices:devices');
	}

	public function handleAdd(int $user_id) : void
	{
		$this->devices->UserWithIdCanBorrowDeviceWithId($user_id, intval($this->curr_edit));
	}	

	public function handleRemove(int $user_id) : void
	{
		$this->devices->UserWithIdCanNotBorrowDeviceWithId($user_id, intval($this->curr_edit));
	}

	//request


    public function actionFulfillRequest(int $requestId): void
    {
        $request = $this->devices->getRequestById($requestId);
        if ($request) {
            // Redirect to the add-device form with pre-filled data
            $this->redirect('addDevice', ['name' => $request->name, 'description' => $request->description]);
        } else {
            $this->flashMessage("Device request not found.", "error");
            $this->redirect('requests');
        }
    }

    public function handleDeleteRequest(int $requestId): void
    {
        $this->devices->deleteRequest($requestId);
        $this->flashMessage("Request deleted successfully.", "success");
        $this->redirect('requests');
    }


}


