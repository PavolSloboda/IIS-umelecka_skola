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
	}

	//vypis vsech dostupnych zarizeni
	public function renderDevices() : void
	{
		$this->template->devices = $this->devices->showAllAvailableDevices();
		$this->template->loans = $this->devices->showAllAvailableLoans($this->getUser()->getId());

	}
	

	//formular na pujceni zarizeni
	public function createComponentAddDeviceLoanForm() : Form
	{
		$form = new Form;

		$form->addSelect('device_id', 'Select Device:', $this->DevicesService->getAvailableDevices())->setPrompt('Choose a device')->setRequired('Please select a device.');

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

}
