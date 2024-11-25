<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use App\Core\RolesService;
use Nette;
use Nette\Application\UI\Form;
use App\Core\UsersService;


/**
 * Presenter for managing device-related actions and views.
 */
final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	/** @var DevicesService Device service instance */
    private $devices;
    
    /** @var DevicesService Second device service instance */
    private DevicesService $DevicesService;
    
    /** @var RolesService Role service instance */
    private $roles;
    
    /** @var UsersService User service instance */
    private $users;
    
    /** @var int|null ID of the device currently being edited */
    private $curr_edit;
    
    /** @var int|null ID of the device for which statistics are displayed */
    private $curr_stat;
    
    /** @var int|null ID of the device being reserved */
    private $curr_reserve;
    
    /** @var mixed Array of device requests */
    private $wanted_devices;

	/**
     * Constructor for initializing services and setting default values.
     *
     * @param DevicesService $devices Service for managing devices
     * @param RolesService $roles Service for managing roles
     * @param DevicesService $DevicesService Another instance of device service
     * @param UsersService $users Service for managing users
     */
	public function __construct(DevicesService $devices, RolesService $roles, DevicesService $DevicesService, UsersService $users)
	{
		$this->devices = $devices;
		$this->roles = $roles;
		$this->DevicesService = $DevicesService;
		$this->users = $users;
		$this->curr_edit = null;
		$this->curr_stat = null;
		$this->curr_reserve = null;
		//$this->wanted_devices = $wanted_devices;
	}

	/**
     * Startup method called when the presenter is initialized.
     * Ensures the user is logged in and sets template functions.
     */
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

	/**
     * Renders the list of all available devices.
     */
	public function renderDevices() : void
	{
		$this->template->devices = $this->devices->showAllDevices();
		$this->template->loans = $this->devices->showAllAvailableLoans();
		$this->template->types = $this->devices->showAllAvailableTypes();

		$this->template->wanted_devices = $this->devices->getDeviceRequests();
	}

	/**
     * Renders the edit page with forbidden and non-forbidden users for the current device.
     */
	public function renderEdit() : void
	{
		$this->template->forbidden_users = $this->devices->get_forbidden_users(intval($this->curr_edit));
		$this->template->not_forbidden_users = $this->devices->get_not_forbidden_users(intval($this->curr_edit));
	}

	/**
     * Renders device requests for teachers to review.
     */
	public function renderRequests(): void
	{
    // Fetch all pending device requests for the teacher
    $this->template->wanted_devices = $this->devices->getDeviceRequests();
	}

	/**
     * Renders statistics related to device usage.
     */
	public function renderStats() : void
	{
		//nejcasteji vypujceno od
		$this->template->loyal_customer = $this->devices->get_loyal_customer(intval($this->curr_stat));
		//datum posledni vypujcky
		$this->template->number_of_device_loans = $this->devices->get_number_of_device_loans(intval($this->curr_stat));
		//celkovy pocet vypujcek
		$this->template->number_of_loans = $this->devices->get_number_of_loans();
		//prumerna doba vypujcky
		$this->template->avg_loan_time = $this->devices->get_avg_loan_time(intval($this->curr_stat));
		//nejdelsi vypujcka
		$this->template->longest_loan_time = $this->devices->get_longest_loan_time(intval($this->curr_stat));
		//nejkratsi vypujcka
		$this->template->shortest_loan_time = $this->devices->get_shortest_loan_time(intval($this->curr_stat));
	}

	 /**
     * Sets the device ID for the statistics view.
     *
     * @param int $deviceId ID of the device
     */
	public function actionStats($deviceId): void
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$this->template->device = $device;
		$this->curr_stat = $deviceId;
	}

	/**
     * Creates the form for adding a new device.
     *
     * @return Form The form for adding a new device
     */
	public function createComponentAddDeviceForm() : Form
	{
		$form = new Form;

		// Získání předvyplněných hodnot z parametrů
		$name = $this->getParameter('name');
		$description = $this->getParameter('description');
		
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired()->setDefaultValue($name);
		$form->addText('description', 'Description:')->addRule($form::MaxLength, 'Description is limited to a maximum of 50 characters.', 50)->setDefaultValue($description);
		$form->addInteger('manufactured','Year of manufacture:')->addRule($form::Max,'Year of manufacture  isn not valid.',date('Y'));
		$form->addInteger('price','Purchase price (kč):')->addRule($form::Min,'Price must be positive',0);
		$form->addInteger('max_loan_duration', 'Max loan duration:')->addRule($form::Range, 'Loan duration must be between %d and %d.', [1, 90])->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addSelect('atelier_id', 'Atelier:', $this->devices->getUserAtelier($this->getUser()->getId()))->setRequired();
		$form->addCheckbox('loan', 'Device can not be borrowed');
		$form->addSubmit('submit', 'Submit changes');

		$form->onSuccess[] = [$this, 'processAddDeviceForm'];
		return $form;
		
	}

	/**
     * Processes the form for adding a new device.
     *
     * @param Form $form The submitted form
     * @param \stdClass $values Form values
     */
	public function processAddDeviceForm(Form $form, \stdClass $values): void
	{
		$userId = $this->getUser()->getId();
		$this->DevicesService->addDevice($userId, $values->name, $values->description, intval($values->max_loan_duration), $values->group_id, $values->atelier_id, $values->loan,$values->price, $values->manufactured);
		$this->flashMessage('Device has been successfully edited.', 'success');
		$this->redirect('Devices:devices');
	}

	/**
     * Creates the form for adding a new device group.
     *
     * @return Form The form for adding a new device group
     */
	public function createComponentAddGroupForm() : Form
	{
		$form = new Form;
		
		
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();
		$form->addText('description', 'Description:')->addRule($form::MaxLength, 'Description is limited to a maximum of 50 characters.', 50);
		$form->addSubmit('submit', 'Submit changes');
		
		$form->onSuccess[] = [$this, 'processAddGroupForm'];
		return $form;
		
	}

	/**
     * Processes the form for adding a new device group.
     *
     * @param Form $form The submitted form
     * @param \stdClass $values Form values
     */
	public function processAddGroupForm(Form $form, \stdClass $values): void
	{
		$this->DevicesService->addGroup($values->name, $values->description);
		$this->flashMessage('Device has been successfully edited.', 'success');
		
		$this->redirect('Devices:devices');
	}
	


	/**
 	* Creates a form for borrowing a device.
 	*
 	* This method creates a form that allows users to request a device loan by selecting the start and end dates for the loan.
 	* It includes validation rules for the start date and time, ensuring the reservation does not start in the past.
 	* 
 	* @return Form The form for borrowing a device.
 	*/
	public function createComponentAddDeviceLoanForm() : Form
	{
		$form = new Form;
		$form->getElementPrototype()->class('ajax');

		$form->addHidden('device_id');
		
		
		$form->addDateTime('loan_start', 'Start Date and Time:')->setFormat('Y-m-d H:i:s')->setDefaultValue((new \DateTime())->format('Y-m-d H:i:s'))->setRequired('Please enter the start date and time.')
		->addRule(Form::Min,'The earliest possible reservation start date is today.',(new \DateTime())->format('Y-m-d H:i:s'));

		$form->addDateTime('loan_end', 'End Date and Time:')->setFormat('Y-m-d H:i:s')->setDefaultValue((new \DateTime())->format('Y-m-d H:i:s'))->setRequired('Please enter the end date and time.');

		$form->addSubmit('submit', 'Borrow Device');

		$form->onValidate[] = [$this, 'validateAddDeviceLoanForm'];
		$form->onSuccess[] = [$this, 'processAddDeviceLoanForm'];

		return $form;
	
	}
	
	/**
     * Validates the form for adding a device loan.
     *
     * This function validates the loan start and end dates in the form, ensuring
     * that the dates are valid and the device is available for the specified period.
     * If the validation fails, an error message is added to the form, and the form is
     * either redrawn via AJAX or the page is redirected to the current one, depending on the request type.
     *
     * @param Form $form The form instance being validated.
     * @param \stdClass $values The form values, containing information about the loan.
     * @return bool Returns true if the form is valid, false if validation fails and an error is added.
     */
	public function validateAddDeviceLoanForm(Form $form, \stdClass $values): bool
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);
	
		$retval = $this->DevicesService->validateDate($loanStart, $loanEnd, intval($values->device_id));
	
		if ($retval !== null) {
			// Přidání chyby do formuláře
			$form->addError($retval);
			if($this->presenter->isAjax()) {
				$this->presenter->redrawControl('form');
			} else {
				$this->presenter->redirect('this');
			}
			return false;
		}
		return true;
	}
	

	/**
	 * Processes the form data when the add device form is submitted.
	 *
	 * This method processes the form data when a new device is added, storing the details of the new device in the database.
	 *
	 * @param Form $form The submitted form.
	 * @return void
	 */
	public function processAddDeviceLoanForm(Form $form, \stdClass $values): void
	{
		$userId = $this->getUser()->getId();
		$deviceId = $values->device_id;

		// Kombinace data a času do jednoho řetězce pro databázi
		$loanStart = $values->loan_start;
		$loanEnd = $values->loan_end;

		$this->DevicesService->borrowDevice($userId, intval($deviceId), $loanStart, $loanEnd);
		$this->flashMessage('Device has been successfully borrowed.', 'success');
		
		$this->redirect('Devices:devices');
		
	}

	/**
	 * Reserves a device by setting the current device ID for reservation and pre-filling the reservation form with device data.
	 * 
	 * @param int $deviceId The ID of the device being reserved.
	 * 
	 * @return void
	 */
	public function actionReserve($deviceId): void
	{
		$this->curr_reserve = $deviceId;
		$device = $this->devices->getDeviceById(intval($deviceId));
		$this->template->device = $device;
		$form = $this->getComponent('addDeviceLoanForm');
		$form->setDefaults(['device_id' => $device->device_id]);
		
		
	}

	/**
	 * Creates a form for editing a device's details.
	 * The form allows users to edit fields such as the device's name, description, manufacture year, price, and group.
	 * 
	 * @return Form The form for editing a device.
	 */
	public function createComponentEditDeviceForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('device_id');
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();
		$form->addText('description', 'Description:')->addRule($form::MaxLength, 'Description is limited to a maximum of 50 characters.', 50);
		$form->addInteger('manufactured','Year of manufacture:')->addRule($form::Max,'Year of manufacture  isn not valid.',date('Y'));
		$form->addInteger('price','Purchase price (kč):')->addRule($form::Min,'Price must be positive',0);
		$form->addInteger('max_loan_duration', 'Max loan duration:')->addRule($form::Range, 'Loan duration must be between %d and %d.', [1, 90])->setRequired();
		$form->addSelect('group_id', 'Group device:', $this->devices->getDeviceTypes())->setRequired();
		$form->addSelect('atelier_id', 'Atelier:', $this->devices->getUserAtelier($this->getUser()->getId()))->setRequired();
		$form->addCheckbox('loan', 'Device can not be borrowed');
		$form->addSubmit('submit', 'Submit changes');

		$form->onSuccess[] = [$this, 'processDeviceEditForm'];
		return $form;
	}

	/**
	 * Processes the device edit form and updates the device details in the database.
	 * 
	 * @param Form $form The form being processed.
	 * @param \stdClass $values The form values containing the edited device data.
	 * 
	 * @return void
	 */
	public function processDeviceEditForm(Form $form, \stdClass $values): void
	{
			$this->DevicesService->editDevice(intval($values->device_id), $values->name, $values->description, intval($values->max_loan_duration), intval($values->group_id),$values->atelier_id , $values->loan, $values->price, $values->manufactured);
			$this->flashMessage('Device has been successfully edited.', 'success');
			
			$this->redirect('Devices:devices');
	}

	/**
	 * Pre-fills the device edit form with the current details of the specified device for editing.
	 * 
	 * @param int $deviceId The ID of the device being edited.
	 * 
	 * @return void
	 */
	public function actionEdit($deviceId): void
	{
		$device = $this->devices->getDeviceById(intval($deviceId));
		$this->template->device = $device;
		$form = $this->getComponent('editDeviceForm');
		$form->setDefaults(['price' => $device->price,'manufactured' => $device->manufactured, 'device_id' => $device->device_id, 'name' => $device->name, 'description' => $device->description, 'max_loan_duration' => $device->max_loan_duration, 'group_id' => $device->group_id,'atelier_id' => $device->atelier_id, 'loan' => $device->loan]);
		$this->curr_edit = $deviceId;
	}

	/**
	 * Creates a form for editing a device group, allowing the user to modify its name and description.
	 * 
	 * @return Form The form for editing a device group.
	 */
	public function createComponentEditGroupForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('group_id');
		$form->addText('name', 'Name:')->addRule($form::MaxLength, 'Name is limited to a maximum of 50 characters.', 50)->setRequired();
		$form->addText('description', 'Description:')->addRule($form::MaxLength, 'Description is limited to a maximum of 50 characters.', 50);
		
		$form->addSubmit('submit', 'Submit changes');
		
		$form->onSuccess[] = [$this, 'processGroupEditForm'];
		return $form;
		
	}

	/**
	 * Processes the group edit form and updates the group details in the database.
	 * 
	 * @param Form $form The form being processed.
	 * @param \stdClass $values The form values containing the edited group data.
	 * 
	 * @return void
	 */
	public function processGroupEditForm(Form $form, \stdClass $values): void
	{
		$this->DevicesService->editGroup(intval($values->group_id), $values->name, $values->description);
		$this->flashMessage('Device has been successfully edited.', 'success');
		
		$this->redirect('Devices:devices');
	}

	/**
	 * Processes the group edit form and updates the group details in the database.
	 * 
	 * @param Form $form The form being processed.
	 * @param \stdClass $values The form values containing the edited group data.
	 * 
	 * @return void
	 */
	public function actionEditGroup($groupId): void
	{
		$group = $this->devices->getGroupById(intval($groupId));
		$form = $this->getComponent('editGroupForm');
		$form->setDefaults(['group_id' => $group->group_id, 'name' => $group->name, 'description' => $group->description]);
	}

	/**
	 * Creates the form for editing a reservation.
	 * 
	 * This form allows the user to modify the status of a device reservation.
	 * The form includes a hidden field for the loan ID, a select input for the status,
	 * and a submit button to submit the changes.
	 *
	 * @return Form The form for editing the reservation.
	 */
	public function createComponentEditReservationForm() : Form
	{
		$form = new Form;
		
		$form->addHidden('loan_id');
		$form->addSelect('status', 'Status:', $this->devices->getLoanStatus())->setRequired();
		$form->addSubmit('submit', 'Submit changes');	
		$form->onSuccess[] = [$this, 'processReservationEditForm'];
		return $form;
	}
	
	/**
	 * Processes the form submission for editing a reservation.
	 *
	 * This method handles the form submission for editing a reservation, 
	 * updates the reservation status in the database, and redirects to 
	 * the devices list page with a success message.
	 *
	 * @param Form $form The form being submitted.
	 * @param \stdClass $values The form values.
	 */
	public function processReservationEditForm(Form $form, \stdClass $values): void
	{
		$this->DevicesService->editReservation(intval($values->loan_id), intval($values->status));
		$this->flashMessage('Device has been successfully edited.', 'success');
		
		$this->redirect('Devices:devices');
	}
	
	/**
	 * Loads the reservation data into the edit reservation form.
	 *
	 * This method retrieves the reservation details from the database and 
	 * populates the edit reservation form with the existing data.
	 *
	 * @param int $reservationId The ID of the reservation to be edited.
	 */
	public function actionEditReservation($reservationId): void
	{
		$device = $this->devices->getLoanById(intval($reservationId));
		$form = $this->getComponent('editReservationForm');
		$form->setDefaults(['loan_id' => $device->loan_id, 'status' => $device->status_id]);
		$this->template->loan = $device;
		$form = $this->getComponent('editLoanEndDateForm');
		$form->setDefaults(['loan_id' => $device->loan_id, 'loan_end' => $device->loan_end, 'device_id' => $device->device_id,]);
	}
	
	/**
	 * Creates the form for editing the loan end date.
	 *
	 * This form allows the user to modify the end date and time of the loan.
	 * The form includes hidden fields for the loan ID, loan start date, and device ID,
	 * a date-time picker for the loan end date, and a submit button to apply the changes.
	 *
	 * @return Form The form for editing the loan end date.
	 */
	public function createComponentEditLoanEndDateForm() : Form
	{
		$form = new Form;
		$form->getElementPrototype()->class('ajax');

		$form->addHidden('loan_id');
		$form->addHidden('loan_start');
		$form->addHidden('device_id');
		
		$form->addDateTime('loan_end', 'End Date and Time:')->setFormat('Y-m-d H:i:s')->setDefaultValue((new \DateTime())->format('Y-m-d H:i:s'))->setRequired('Please enter new end date and time.');

		
		$form->addSubmit('submit', 'Change end date');
		
		$form->onValidate[] = [$this, 'validateEditLoanEndDateForm'];
		$form->onSuccess[] = [$this, 'processEditLoanEndDateForm'];
		
		return $form;
		
	}


	/**
     * Validates the form for editing the loan end date.
     *
     * This function validates the start and end dates of a loan when editing an existing loan.
     * It checks if the dates are valid and if the device is available for the specified period.
     * If the validation fails, an error message is added to the form, and the form is either
     * redrawn via AJAX or the page is redirected to the current one, depending on the request type.
     *
     * @param Form $form The form instance being validated.
     * @param \stdClass $values The form values, containing information about the loan.
     * @return bool Returns true if the form is valid, false if validation fails and an error is added.
     */
	public function validateEditLoanEndDateForm(Form $form, \stdClass $values): bool
	{
		date_default_timezone_set('Europe/Prague');
		$loanStart = new \DateTime($form->getValues()->loan_start);
		$loanEnd = new \DateTime($form->getValues()->loan_end);
		$retval = $this->DevicesService->validateEditDate( intval($values->loan_id),$loanStart, $loanEnd, intval($values->device_id));
		bdump($retval);
		if ($retval !== null) {
			// Přidání chyby do formuláře
			$form->addError($retval);
			if($this->presenter->isAjax()) {
				$this->presenter->redrawControl('form');
			} else {
				$this->presenter->redirect('this');
			}
			return false;
		}
		return true;

	}

	/**
	 * Processes the form submission for editing the loan end date.
	 *
	 * This method handles the form submission for editing the loan's end date, 
	 * updates the loan end date in the database, and redirects to the devices 
	 * list page with a success message.
	 *
	 * @param Form $form The form being submitted.
	 * @param \stdClass $values The form values.
	 */
	public function processEditLoanEndDateForm(Form $form, \stdClass $values): void
	{
		$this->DevicesService->EditLoanEndDate(intval($values->loan_id), $values->loan_end);
		$this->redirect('Devices:devices');
	}

	/**
	 * Deletes a device from the database.
	 *
	 * This method removes a device from the database by its ID.
	 *
	 * @param int $id The ID of the device to be deleted.
	 */
	public function handleDeleteDevice(int $id) : void
	{
		$this->devices->deleteDevice($id);
	}

	/**
	 * Deletes a device group from the database.
	 *
	 * This method removes a device group from the database by its ID.
	 *
	 * @param int $id The ID of the device group to be deleted.
	 */
	public function handleDeleteGroup(int $id) : void
	{
		$this->devices->deleteGroup($id);
	}

	/**
	 * Deletes a reservation from the database.
	 *
	 * This method removes a reservation from the database by its ID.
	 *
	 * @param int $id The ID of the reservation to be deleted.
	 */
	public function handleDeleteReservation(int $id) : void
	{
		$this->devices->deleteReservation($id);
	}

	/**
	 * Allows a user to borrow a device.
	 *
	 * This method allows a user, identified by their user ID, to borrow a device.
	 * The device ID is determined by the current edit context.
	 *
	 * @param int $user_id The ID of the user who wants to borrow the device.
	 */
	public function handleAdd(int $user_id) : void
	{
		$this->devices->UserWithIdCanBorrowDeviceWithId($user_id, intval($this->curr_edit));
	}	

	/**
	 * Prevents a user from borrowing a device.
	 *
	 * This method prevents a user, identified by their user ID, from borrowing
	 * a device. The device ID is determined by the current edit context.
	 *
	 * @param int $user_id The ID of the user who should no longer be allowed to borrow the device.
	 */
	public function handleRemove(int $user_id) : void
	{
		$this->devices->UserWithIdCanNotBorrowDeviceWithId($user_id, intval($this->curr_edit));
	}

	/**
	 * Redirects to the add device form with pre-filled data from a request.
	 *
	 * This method fetches a device request by its ID and redirects to the add device form
	 * with the request's name and description pre-filled.
	 *
	 * @param int $requestId The ID of the device request.
	 */
    public function actionFulfillRequest(int $requestId): void
    {
        $request = $this->devices->getRequestById($requestId);

        $this->redirect('add', ['name' => $request->name, 'description' => $request->description]);
	}

	/**
	 * Deletes a device request from the database.
	 *
	 * This method removes a device request from the database by its ID.
	 *
	 * @param int $requestId The ID of the device request to be deleted.
	 */
    public function handleDeleteRequest(int $requestId): void
    {
        $this->devices->deleteRequest($requestId);
        $this->redirect('Devices:devices');
    }


}

//v prohlížečích Chrome a Firefox 
//ruzne atributy devices
//pridat rok vyroby a nakupni cena



