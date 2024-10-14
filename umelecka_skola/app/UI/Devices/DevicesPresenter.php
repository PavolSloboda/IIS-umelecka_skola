<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use Nette;
use Nette\Application\UI\Form;


final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	private $devices; 
	private DevicesService $DevicesService;
	private $loan;

	public function __construct(DevicesService $devices,DevicesService $DevicesService,DevicesService $loan)
	{
		$this->devices = $devices;
		$this->DevicesService = $DevicesService;
		$this->loan = $loan;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
		$this->template->addFunction('isNotDeviceReserve', function (int $id) {return $this->devices->isNotDeviceReserve(intval($id));});
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


	//formular na pujceni zarizeni
	public function createComponentAddDeviceLoanForm() : Form
	{
		$form = new Form;

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

	public function createComponentEditDeviceForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('device_id');
		$form->addText('name', 'Name:')->setRequired();
		$form->addSubmit('submit', 'Submit changes');
		
		$form->addButton('cancel', 'Cancel')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('cancelClicked!').'"');

		$form->onSuccess[] = [$this, 'processDeviceEditForm'];
		return $form;
		
	}

	public function handleCancelClicked() : void
	{
		$this->redirect('Devices:devices');
	}

	public function processDeviceEditForm(Form $form, \stdClass $values): void
	{
		try {
			$this->DevicesService->editDevice(intval($values->device_id), $values->name);
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
		$form->setDefaults(['device_id' => $device->device_id, 'name' => $device->name]);
	}

	public function actionReserve($name)
	{
		$this->template->deviceName = $name;
	}

	public function validateAddDeviceLoanForm(Form $form): void
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);

		$interval = $loanStart->diff($loanEnd);
		$maxLoanDuration = $this->DevicesService->getDeviceById($form->getValues()->device_id)->max_loan_duration;

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
			$this->DevicesService->borrowDevice($userId, $deviceId, $loanStart, $loanEnd);
			$this->flashMessage('Device has been successfully borrowed.', 'success');
			
			$this->redirect('this');
		}catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured');
		}
	}

	public function handleDelete(int $id) : void
	{
		$this->devices->deleteDevice($id);
		$this->forward('Devices:devices');
	}

	public function renderGroup(string $id): void
	{
		
	}


}
