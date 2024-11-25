<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use App\Core\AtelierService;
use App\Core\UsersService;

/**
 * Class DevicesService
 * 
 * This service handles operations related to devices, loans, and device types in the system.
 * It provides methods to view, add, edit, and remove devices, as well as manage device loans and reservations.
 * 
 * @package App\Core
 */
final class DevicesService
{
	private Explorer $database;
	private $users;


	/**
     * DevicesService constructor.
     * 
     * @param Explorer $database The database connection.
     * @param UsersService $users The user service instance.
     */
	public function __construct(Explorer $database, UsersService $users)
	{
		$this->database = $database;
		$this->users = $users;
	}
	

	/**
     * Retrieves all available devices that are not marked as deleted.
     * 
     * @return Nette\Database\table\ActiveRow[] List of available devices.
     */
	public function showAllDevices() : array
	{
		$result = $this->database->table('devices')->where('deleted', false)->fetchAll();
		return $result;
	}

	/**
     * Retrieves all loans that are either reserved or currently loaned.
     * 
     * @return Nette\Database\table\ActiveRow[] List of loans with status 'reserved' or 'loaned'.
     */
    public function showAllAvailableLoans(): array
    {
        
        $statusIds = [];
        $statusRows = $this->database->table('loan_status')
            ->where('name IN ?', ['reserved', 'loaned'])
            ->fetchAll();

        foreach ($statusRows as $status) {
            $statusIds[] = $status->status_id;
        }

        return $this->database->table('loan')
            ->where('status_id IN ?', $statusIds)
            ->fetchAll();
    }

	/**
     * Retrieves all loans for a specific device that are either reserved or currently loaned.
     * 
     * @param int $deviceId The device ID to check.
     * @return Nette\Database\table\ActiveRow[] List of loans for the specified device.
     */
	public function showDeviceLoans(int $deviceId): array
    {
        
        $statusIds = [];
        $statusRows = $this->database->table('loan_status')
            ->where('name IN ?', ['reserved', 'loaned'])
            ->fetchAll();

        foreach ($statusRows as $status) {
            $statusIds[] = $status->status_id;
        }

        return $this->database->table('loan')
            ->where('status_id IN ?', $statusIds)->where('device_id IN ?', $deviceId)
            ->fetchAll();
    }

	 /**
     * Validates if the loan start and end dates are valid for a specific device.
     * 
     * @param \DateTime $loanStart The start date of the loan.
     * @param \DateTime $loanEnd The end date of the loan.
     * @param int $deviceId The device ID being loaned.
     * 
     * @return string|null Error message if validation fails, null if validation is successful.
     */
	public function validateDate(\DateTime $loanStart, \DateTime $loanEnd, int $deviceId): ?string
    {
		$interval = $loanStart->diff($loanEnd);
		$deviceLoans = $this->showDeviceLoans($deviceId);
		date_default_timezone_set('Europe/Prague');

		$maxLoanDuration = $this->getDeviceById($deviceId)->max_loan_duration;

		foreach($deviceLoans as $loan)
		{
			if(!(($loan->loan_start > $loanStart && $loan->loan_start > $loanEnd) || ($loan->loan_end < $loanStart && $loan->loan_end < $loanEnd)))
			{
				return 'The device is already loaned at this time';
			}
		}

		if ($interval->days > $maxLoanDuration) {
			return 'The loan duration cannot exceed ' . $maxLoanDuration . ' days.';
		}

		if ($loanEnd < $loanStart) {
			return 'The end date must be after the start date.';
		}

		$today = new \DateTime();
		$formatToday = $today->format('d-m-Y H:i');
		if ($today > $loanStart) {
			return 'The earliest possible reservation start date is ' . $formatToday . '.';
		}
		return null;
	}

	 /**
     * Validates if the loan start and end dates are valid when editing an existing reservation.
     * 
     * @param int $loan_id The ID of the existing loan.
     * @param \DateTime $loanStart The new start date of the loan.
     * @param \DateTime $loanEnd The new end date of the loan.
     * @param int $deviceId The device ID being loaned.
     * 
     * @return string|null Error message if validation fails, null if validation is successful.
     */
	public function validateEditDate(int $loan_id, \DateTime $loanStart, \DateTime $loanEnd, int $deviceId): ?string
    {
		$interval = $loanStart->diff($loanEnd);
		$deviceLoans = $this->showDeviceLoans($deviceId);
		date_default_timezone_set('Europe/Prague');

		$maxLoanDuration = $this->getDeviceById($deviceId)->max_loan_duration;

		foreach($deviceLoans as $loan)
		{
			if($loan_id != $loan->loan_id)
			{
				if(!(($loan->loan_start > $loanStart && $loan->loan_start > $loanEnd) || ($loan->loan_end < $loanStart && $loan->loan_end < $loanEnd)))
				{
					return 'The device is already loaned at this time';
				}
			}
		}

		if ($interval->days > $maxLoanDuration) {
			return 'The loan duration cannot exceed ' . $maxLoanDuration . ' days.';
		}

		if ($loanEnd < $loanStart) {
			return 'The end date must be after the start date.';
		}
		return null;
	}

	 /**
     * Retrieves all available device types from the system.
     * 
     * @return Nette\Database\table\ActiveRow[] List of device types.
     */
	public function showAllAvailableTypes() : array
	{
		$result = $this->database->table('device_groups')->fetchAll();
	
		return $result;
	}

	/**
     * Retrieves a list of all device types, returning an associative array of group IDs and their names.
     * 
     * @return array Associative array of device group IDs and names.
     */
	public function getDeviceTypes() : array
	{
		$result = $this->database->table('device_groups')->fetchPairs('group_id', 'name');
	
		return $result;
	}

	/**
     * Retrieves a list of ateliers managed by a specific user.
     * 
     * @param int $userId The user ID to check.
     * @return array List of ateliers the user manages.
     */
	public function getUserAtelier(int $userId) : array
	{
		$user_ateliers = $this->database->table('user_atelier')->where('user_id',$userId)->fetchAll();
		$result = array();
		foreach ($user_ateliers as $user_atelier)
		{
 			$tmp_ateliers = $this->database->table('ateliers')->where('atelier_id', $user_atelier->atelier_id)->fetchPairs('atelier_id', 'name');
			foreach ($tmp_ateliers as $curr_atelier)
			{
				$result[] = $curr_atelier;
			}
		}

		return $result;
	}

	/**
     * Changes the reservation status of loans that are reserved or completed based on the current time.
     */
	public function changeStateReservation(): void
	{
		date_default_timezone_set('Europe/Prague');
		$currentTime = new \DateTime();

		$reservationStatusId = $this->database->table('loan_status')->where('name', 'reserved')->fetch()->status_id;
		$loanStatusId = $this->database->table('loan_status')->where('name', 'loaned')->fetch()->status_id;
		$completedStatusId = $this->database->table('loan_status')->where('name', 'completed')->fetch()->status_id;

		if(!$reservationStatusId || !$completedStatusId || !$loanStatusId)
		{
			throw new \Exception("Reservation status is not defined");
		}
		$this->database->table('loan')->where('loan_start < ?', $currentTime->format('Y-m-d H:i:s'))->where('status_id', $reservationStatusId)->update(['status_id' => $loanStatusId]);
		$this->database->table('loan')->where('loan_end < ?', $currentTime->format('Y-m-d H:i:s'))->where('status_id', $loanStatusId)->update(['status_id' => $completedStatusId]);
	}

	
	/**
     * Retrieves the list of loan statuses in the system.
     * 
     * @return array Associative array of loan status IDs and names.
     */
	public function getLoanStatus(): array
    {
        return $this->database->table('loan_status')->fetchPairs('status_id', 'name');
    }
	
	/**
	 * Borrow a device for a user by creating a loan reservation.
	 *
	 * This method checks if the device is available for borrowing (i.e., not already loaned),
	 * and if so, creates a loan record with the provided user ID, device ID, loan start date,
	 * and loan end date. The loan status is set to "reserved".
	 *
	 * @param int $userId The ID of the user borrowing the device.
	 * @param int $deviceId The ID of the device being borrowed.
	 * @param string $loanStart The start date of the loan (format: YYYY-MM-DD).
	 * @param string $loanEnd The end date of the loan (format: YYYY-MM-DD).
	 * 
	 * @return void
	 */    
	public function borrowDevice(int $userId, int $deviceId, string $loanStart, string $loanEnd): void
    {
        $device = $this->database->table('devices')->get($deviceId);
		$reservationStatusId = $this->database->table('loan_status')->where('name', 'reserved')->fetch()->status_id;

        if ($device && !$device->loan) {
            $this->database->table('loan')->insert(['user_id' => $userId,'device_id' => $deviceId,'loan_start' => $loanStart,'loan_end' => $loanEnd,'status_id' => $reservationStatusId,]);
        }
    }

	/**
	 * Edit device details.
	 *
	 * This method allows updating the device's attributes such as name, description, 
	 * maximum loan duration, group, atelier, loan status, price, and manufacturing year.
	 *
	 * @param int $deviceId The ID of the device to be updated.
	 * @param string $name The new name of the device.
	 * @param string $description The new description of the device.
	 * @param int $max_loan_duration The new maximum loan duration for the device.
	 * @param int $group_id The ID of the device group.
	 * @param int $atelier_id The ID of the atelier to which the device belongs.
	 * @param bool $loan The loan status of the device.
	 * @param int $price The new price of the device.
	 * @param int $manufactured The new manufacturing year of the device.
	 * 
	 * @return void
	 */
	public function editDevice( int $deviceId, string $name, string $description, int $max_loan_duration, int $group_id, int $atelier_id, bool $loan, ?int $price, ?int $manufactured): void
    {
        $device = $this->database->table('devices')->get($deviceId);

        if ($device) {
            $device->update(['price' => $price, 'manufactured' => $manufactured, 'name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id,'atelier_id' => $atelier_id, 'loan' => $loan]);
        }
    }

	/**
	 * Edit device group details.
	 *
	 * This method allows updating the name and description of a device group.
	 *
	 * @param int $group_id The ID of the group to be updated.
	 * @param string $name The new name of the device group.
	 * @param string $description The new description of the device group.
	 * 
	 * @return void
	 */
	public function editGroup( int $group_id, string $name, string $description): void
    {
        $group = $this->database->table('device_groups')->get($group_id);

        if ($group) {
            $group->update(['name' => $name, 'description' => $description]);
        }
    }
	
	/**
	 * Edit the status of a loan reservation.
	 *
	 * This method updates the status of a loan reservation (e.g., approved, pending, etc.).
	 *
	 * @param int $group_id The ID of the loan group to be updated.
	 * @param int $status_id The new status ID to be assigned to the loan.
	 * 
	 * @return void
	 */
	public function editReservation( int $group_id, int $status_id): void
    {
        $loan = $this->database->table('loan')->get($group_id);

        $loan->update(['status_id' => $status_id]);
	}
	
	/**
	 * Edit the end date of a loan.
	 *
	 * This method updates the end date of a loan for the specified loan ID.
	 *
	 * @param int $loan_id The ID of the loan to be updated.
	 * @param string $loan_end The new end date of the loan (format: YYYY-MM-DD).
	 * 
	 * @return void
	 */
	public function editLoanEndDate( int $loan_id, string $loan_end): void
    {
		$loan = $this->database->table('loan')->get($loan_id);
        $loan->update(['loan_end' => $loan_end]);
	}

	/**
	 * Get a device by its ID.
	 *
	 * This method retrieves the device record with the specified device ID.
	 *
	 * @param int $device_id The ID of the device to retrieve.
	 * 
	 * @return \Nette\Database\Table\ActiveRow The device record.
	 */
	public function getDeviceById(int $device_id): \Nette\Database\Table\ActiveRow
    {
        return $this->database->table('devices')->get($device_id);
    }

	/**
	 * Get a loan by its ID.
	 *
	 * This method retrieves the loan record with the specified loan ID.
	 *
	 * @param int $loan_id The ID of the loan to retrieve.
	 * 
	 * @return \Nette\Database\Table\ActiveRow The loan record.
	 */
	public function getLoanById(int $loan_id): \Nette\Database\Table\ActiveRow
    {
        return $this->database->table('loan')->get($loan_id);
    }

	/**
	 * Delete a device by its ID.
	 *
	 * This method marks the device as deleted and clears its group association.
	 *
	 * @param int $id The ID of the device to delete.
	 * 
	 * @return void
	 */
	public function deleteDevice(int $id) : void
	{
		$this->database->table('devices')->where('device_id', $id)->update(['deleted' => true,'group_id' => null, 'atelier_id' => null]);
	}
	
	/**
	 * Delete a device group by its ID.
	 *
	 * This method deletes the specified device group.
	 *
	 * @param int $id The ID of the device group to delete.
	 * 
	 * @return void
	 */
	public function deleteGroup(int $id) : void
	{
		$this->database->table('device_groups')->where('group_id', $id)->delete();
	}
	
	/**
	 * Delete a reservation (loan) by its ID.
	 *
	 * This method deletes the specified loan reservation and updates the associated device to not be on loan.
	 *
	 * @param int $id The ID of the reservation (loan) to delete.
	 * 
	 * @return void
	 */
	public function deleteReservation(int $id) : void
	{
		$device_id = $this->database->table('loan')->get($id)->device_id;
		$this->database->table('loan')->where('loan_id', $id)->delete();
		$this->database->table('devices')->where('device_id', $device_id)->update(['loan' => false]);
	}

	/**
	 * Get a device group by its ID.
	 *
	 * This method retrieves the device group record with the specified group ID.
	 *
	 * @param int $group_id The ID of the device group to retrieve.
	 * 
	 * @return \Nette\Database\Table\ActiveRow The device group record.
	 */
	public function getGroupById(int $group_id): \Nette\Database\Table\ActiveRow
    {
        return $this->database->table('device_groups')->get($group_id);
    }
	
	/**
	 * Get loan status by its ID.
	 *
	 * This method retrieves the loan status record with the specified status ID.
	 *
	 * @param int $status_id The ID of the loan status to retrieve.
	 * 
	 * @return \Nette\Database\Table\ActiveRow The loan status record.
	 */
	public function getStatusById(int $status_id): \Nette\Database\Table\ActiveRow
    {
        return $this->database->table('loan_status')->get($status_id);
    }

	/**
	 * Get an atelier by its ID.
	 *
	 * This method retrieves the atelier record with the specified atelier ID.
	 *
	 * @param int $atelier_id The ID of the atelier to retrieve.
	 * 
	 * @return \Nette\Database\Table\ActiveRow The atelier record.
	 */
	public function getAtelierById(int $atelier_id): \Nette\Database\Table\ActiveRow
    {
        return $this->database->table('ateliers')->get($atelier_id);
    }

	/**
	 * Check if a device is not reserved for loan.
	 *
	 * This method checks if the device is available for loan (not already loaned or reserved).
	 *
	 * @param int $device_id The ID of the device to check.
	 * 
	 * @return bool `true` if the device is not reserved, `false` otherwise.
	 */
	public function isNotDeviceReserve(int $device_id): bool
	{
		$device = $this->database->table('devices')->where('device_id', $device_id)->where('loaned', false)->fetch();

    	return $device !== null;
	}
	
	 /**
     * Checks if a device group is empty.
     *
     * @param int $group_id The ID of the device group.
     * @return bool Returns true if the group is empty, false otherwise.
     */
	public function isGroupEmpty(int $group_id): bool
	{
		$device = $this->database->table('devices')->where('group_id', $group_id)->fetch();

    	return $device == null;
	}
	
	/**
     * Checks if a device is in one of the user's ateliers.
     *
     * @param int $user_id The ID of the user.
     * @param int $device_id The ID of the device.
     * @return bool Returns true if the device is in one of the user's ateliers, false otherwise.
     */
	public function isDeviceInMyAtelier(int $user_id, int $device_id): bool
	{
		$userAteliers = $this->database->table('user_atelier')
		->where('user_id', $user_id)
		->fetchPairs('atelier_id', 'atelier_id');
		
		if (empty($userAteliers)) {

			return false;
		}
	
		$device = $this->database->table('devices')->where('device_id', $device_id)->fetch();
		foreach ($userAteliers as $userAtelier) 
		{
			if ($userAtelier->atelier_id === $device->atelier_id) 
			{
				return true;
			}
		}
	
		return false;
	}

	/**
     * Adds a new device to the database.
     *
     * @param int $user_id The ID of the user adding the device.
     * @param string $name The name of the device.
     * @param string $description A description of the device.
     * @param int $max_loan_duration The maximum loan duration for the device.
     * @param int $group_id The group ID the device belongs to.
     * @param int $atelier_id The atelier ID where the device is located.
     * @param bool $loan Whether the device is available for loan.
     * @param int $price The price of the device.
     * @param int $manufactured The year the device was manufactured.
     */
	public function addDevice(int $user_id, string $name,string $description, int $max_loan_duration,int $group_id, int $atelier_id,  bool $loan, ?int $price, ?int $manufactured) : void
    {
        $this->database->table('devices')->insert(['price' => $price, 'manufactured' => $manufactured, 'name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id, 'atelier_id' => $atelier_id, 'loan' => $loan]);
    }
	
	/**
     * Adds a new device group to the database.
     *
     * @param string $name The name of the group.
     * @param string $description The description of the group.
     */
	public function addGroup(string $name,string $description) : void
    {
        $this->database->table('device_groups')->insert(['name' => $name, 'description' => $description]);
    }

	/**
     * Allows a user to borrow a device by removing any restrictions on borrowing.
     *
     * @param int $user_id The ID of the user.
     * @param int $device_id The ID of the device.
     */
	public function UserWithIdCanBorrowDeviceWithId(int $user_id, int $device_id): void //add
	{
		$this->database->table('forbidden_user_devices')->where('user_id', $user_id)->where('device_id', $device_id)->delete();
	}

	/**
     * Prevents a user from borrowing a specific device.
     *
     * @param int $user_id The ID of the user.
     * @param int $device_id The ID of the device.
     */
	public function UserWithIdCanNotBorrowDeviceWithId(int $user_id, int $device_id) : void
	{
		$this->database->table('forbidden_user_devices')->insert(['user_id' => $user_id, 'device_id' => $device_id]);
	}

	/**
     * Gets a list of users who are forbidden from borrowing a specific device.
     *
     * @param int $device_id The ID of the device.
     * @return array An array of forbidden users.
     */
	public function get_forbidden_users(int $device_id): array
	{
		$atelier_id = $this->database->table('devices')->where('device_id', $device_id)->fetch()->atelier_id;
		$users_atelier = $this->users->getUsersBelongingToAtelier(intval($atelier_id));
		$forbidden_users = [];
		foreach ($users_atelier as $user_atelier)
		{
			$curr_user = $this->database->table('forbidden_user_devices')->where('user_id', $user_atelier->user_id)->fetch();
			if($curr_user)
			{
				$forbidden_users[] = $user_atelier;
			}
		}
		return $forbidden_users;
	}

	/**
     * Gets a list of users who are not forbidden from borrowing a specific device.
     *
     * @param int $device_id The ID of the device.
     * @return array An array of users not forbidden from borrowing the device.
     */
	public function get_not_forbidden_users(int $device_id) : array 
	{
		$atelier_id = $this->database->table('devices')->where('device_id', $device_id)->fetch()->atelier_id;
		$users_atelier = $this->users->getUsersBelongingToAtelier(intval($atelier_id));
		$not_forbidden_users = [];
		foreach ($users_atelier as $user_atelier)
		{
			$curr_user = $this->database->table('forbidden_user_devices')->where('user_id', $user_atelier->user_id)->fetch();
			if(!$curr_user)
			{
				$not_forbidden_users[] = $user_atelier;
			}
		}
		return $not_forbidden_users;
	}

	/**
     * Fetches all device requests from the database.
     *
     * @return array A list of all device requests.
     */
	public function getDeviceRequests(): array
    {
        // Fetch device requests from the database
        return $this->database->table('wanted_devices')->fetchAll();
    }

	/**
     * Retrieves a specific device request by its ID.
     *
     * @param int $requestId The ID of the device request.
     * @return \Nette\Database\Table\ActiveRow The device request.
     */
    public function getRequestById(int $requestId)
    {
        // Retrieve a specific device request by ID
        return $this->database->table('wanted_devices')->get($requestId);
    }

	/**
     * Retrieves a specific device request by its ID.
     *
     * @param int $requestId The ID of the device request.
     * @return \Nette\Database\Table\ActiveRow The device request.
     */
    public function deleteRequest(int $requestId): void
    {
        // Delete a device request by ID
        $this->database->table('wanted_devices')->where('ID', $requestId)->delete();
    }

	/**
     * Finds the most loyal customer for a specific device based on the number of loans.
     *
     * @param int $device_id The ID of the device.
     * @return array A list of users who have borrowed the device the most, with their loan counts.
     */
	public function get_loyal_customer(int $device_id): array
	{
		// Nejprve zjistíme maximální počet výpůjček
		$maxLoans = $this->database->table('loan')
			->where('device_id', $device_id)
			->group('user_id')
			->select('COUNT(*) AS loan_count')
			->order('loan_count DESC')
			->limit(1)
			->fetch();
	
		// Pokud není žádná výpůjčka pro dané zařízení, vrátí prázdné pole
		if (!$maxLoans) {
			return [];
		}
	
		// Získáme ID uživatelů s maximálním počtem výpůjček
		$userIds = $this->database->table('loan')
			->where('device_id', $device_id)
			->group('loan.user_id') // Skupina podle user_id
			->select('loan.user_id, COUNT(*) AS loan_count')
			->having('loan_count = ?', $maxLoans->loan_count)
			->fetchAll();
	
		// Nyní získáme emaily těchto uživatelů podle jejich ID
		$users = [];
		foreach ($userIds as $user) {
			$userEmail = $this->database->table('users') // Tabulka uživatelů
				->select('email')
				->where('user_id', $user->user_id) // Najdeme email podle user_id
				->fetch();
	
			// Pokud existuje email pro uživatele, přidáme ho do pole
			if ($userEmail) {
				$users[$userEmail->email] = $user->loan_count;
			}
		}
	
		return $users;
	}
	
	/**
     * Returns the number of times a specific device has been loaned out.
     *
     * @param int $device_id The ID of the device.
     * @return int The number of loans for the device.
     */
	public function get_number_of_device_loans(int $device_id): int
	{
		$count = $this->database->table('loan')->where('device_id', $device_id)->count('*');
	
		// Pokud není žádná výpůjčka, vrátí 0
		return $count;
	}
	
	/**
     * Returns the total number of loans across all devices.
     *
     * @return int The total number of loans.
     */
	public function get_number_of_loans(): int
	{
		$count = $this->database->table('loan')->count('*');
	
		// Pokud není žádná výpůjčka, vrátí 0
		return $count;
	}
	
	/**
     * Calculates the average loan time for a specific device.
     *
     * @param int $device_id The ID of the device.
     * @return float The average loan time for the device, or 0 if no loans exist.
     */
	public function get_avg_loan_time(int $device_id): ?float
	{
		$avg = $this->database->table('loan')->where('device_id', $device_id)->where('loan_end IS NOT NULL')->aggregation('AVG(DATEDIFF(loan_end, loan_start))');
		if($avg)
		{
			return $avg;
		}
		return 0;
	}
	
	/**
     * Retrieves the longest loan time for a specific device.
     *
     * This function finds the loan with the longest duration for a given device,
     * based on the difference between the `loan_start` and `loan_end` dates.
     *
     * @param int $device_id The ID of the device.
     * @return float The longest loan duration in days, or 0 if no loan exists for the device.
     */
	public function get_longest_loan_time(int $device_id): float
	{
		$loan = $this->database->table('loan')
			->where('device_id', $device_id)
			->where('loan_end IS NOT NULL')
			->order('DATEDIFF(loan_end, loan_start) DESC') 
			->fetch();
	
		if ($loan) {
			$duration = $loan->loan_start->diff($loan->loan_end)->days; 
			return $duration;
		}
	
		return 0;	
	}
	
	/**
     * Retrieves the shortest loan time for a specific device.
     *
     * This function finds the loan with the shortest duration for a given device,
     * based on the difference between the `loan_start` and `loan_end` dates.
     *
     * @param int $device_id The ID of the device.
     * @return float The shortest loan duration in days, or 0 if no loan exists for the device.
     */
	public function get_shortest_loan_time(int $device_id): float
	{
		$loan = $this->database->table('loan')
			->where('device_id', $device_id)
			->where('loan_end IS NOT NULL')
			->order('DATEDIFF(loan_end, loan_start) ASC') 
			->fetch();
	
		if ($loan) {
			$duration = $loan->loan_start->diff($loan->loan_end)->days; 
			return $duration;
		}
	
		return 0;
	}	
}

//osetrit všechno co se muze stat