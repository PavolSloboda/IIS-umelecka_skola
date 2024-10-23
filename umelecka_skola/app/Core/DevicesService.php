<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use App\Core\AtelierService;
use App\Core\UsersService;

final class DevicesService
{
	private Explorer $database;
	private $users;


	public function __construct(Explorer $database, UsersService $users)
	{
		$this->database = $database;
		$this->users = $users;
	}
	

	/*
	* @return Nette\Database\table\ActiveRow[]
	*///vypis vsech dostupnych zarizeni
	public function showAllDevices() : array
	{
		$result = $this->database->table('devices')->where('deleted', false)->fetchAll();
		return $result;
	}

    public function showAllAvailableLoans(): array
    {
        
        $statusIds = [];
        $statusRows = $this->database->table('loan_status')
            ->where('name IN ?', ['reservation', 'loan'])
            ->fetchAll();

        foreach ($statusRows as $status) {
            $statusIds[] = $status->status_id;
        }

        return $this->database->table('loan')
            ->where('status_id IN ?', $statusIds)
            ->fetchAll();
    }

	public function showAllAvailableTypes() : array
	{
		$result = $this->database->table('device_groups')->fetchAll();
	
		return $result;
	}

	public function getDeviceTypes() : array
	{
		$result = $this->database->table('device_groups')->fetchPairs('group_id', 'name');
	
		return $result;
	}

	public function getUserAtelier(int $userId) : array
	{
		$result = $this->database->table('ateliers')->where('admin_id', $userId)->fetchPairs('atelier_id', 'name');
		return $result;
	}

	public function ChangeStateReservation(): void
	{
		$currentTime = new \DateTime();

		$reservationStatusId = $this->database->table('loan_status')->where('name', 'reservation')->fetch()->status_id;
		$cancelledStatusId = $this->database->table('loan_status')->where('name', 'cancelled')->fetch()->status_id;

		$this->database->table('loan')->where('loan_start < ?', $currentTime->format('Y-m-d H:i:s'))->where('status_id', $reservationStatusId)->update(['status_id' => $cancelledStatusId]);
	}


	public function updateLoanStatus(): void
	{
		$completedStatusId = $this->database->table('loan_status')->where('name', 'completed')->fetch()->status_id;
		$cancelledStatusId = $this->database->table('loan_status')->where('name', 'cancelled')->fetch()->status_id;

		$deviceIds = $this->database->table('loan')->select('device_id')->where('status_id IN ?', [$completedStatusId, $cancelledStatusId])->fetchAll();

		if (!empty($deviceIds)) {
			
			foreach ($deviceIds as $device) {
				$this->database->table('devices')
				->where('device_id IN ?', $device->device_id)
				->update(['loan' => false]);
			}
		}
	}
	
	public function getLoanStatus()
    {
        return $this->database->table('loan_status')->fetchPairs('status_id', 'name');
    }
	
	//pujceni zarizeni, todo maximalni doba vypujcky, zarizeni ktera nejdou vypujcit nebo omezit na atelier, spravovani zarizeni majitelem a spravuje vraceni a pujceni
    public function borrowDevice(int $userId, int $deviceId, string $loanStart, string $loanEnd): void
    {
        $device = $this->database->table('devices')->get($deviceId);
		$reservationStatusId = $this->database->table('loan_status')->where('name', 'reservation')->fetch()->status_id;

        if ($device && !$device->loan) {
            $this->database->table('loan')->insert(['user_id' => $userId,'device_id' => $deviceId,'loan_start' => $loanStart,'loan_end' => $loanEnd,'status_id' => $reservationStatusId,]);
            $device->update(['loan' => TRUE,]);
        }
    }

	public function editDevice( int $deviceId, string $name, string $description, int $max_loan_duration, int $group_id, int $atelier_id, bool $loan): void
    {
        $device = $this->database->table('devices')->get($deviceId);

        if ($device) {
            $device->update(['name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id,'atelier_id' => $atelier_id, 'loan' => $loan]);
        }
    }

	public function editGroup( int $group_id, string $name, string $description): void
    {
        $group = $this->database->table('device_groups')->get($group_id);

        if ($group) {
            $group->update(['name' => $name, 'description' => $description]);
        }
    }
	
	public function editReservation( int $group_id, int $status_id): void
    {
        $loan = $this->database->table('loan')->get($group_id);

        $loan->update(['status_id' => $status_id]);
	}

	public function getDeviceById(int $device_id)
    {
        return $this->database->table('devices')->get($device_id);
    }

	public function getLoanById(int $loan_id)
    {
        return $this->database->table('loan')->get($loan_id);
    }

	public function deleteDevice(int $id) : void
	{
		$this->database->table('devices')->where('device_id', $id)->update(['deleted' => true]);
	}
	
	public function deleteGroup(int $id) : void
	{
		$this->database->table('device_groups')->where('group_id', $id)->delete();
	}
	
	public function deleteReservation(int $id) : void
	{
		$device_id = $this->database->table('loan')->get($id)->device_id;
		$this->database->table('loan')->where('loan_id', $id)->delete();
		$this->database->table('devices')->where('device_id', $device_id)->update(['loan' => false]);
	}

	public function getGroupById(int $group_id) 
    {
        return $this->database->table('device_groups')->get($group_id);
    }
	
	public function getStatusById(int $status_id) 
    {
        return $this->database->table('loan_status')->get($status_id);
    }

	public function isNotDeviceReserve(int $device_id) : bool
	{
		$device = $this->database->table('devices')->where('device_id', $device_id)->where('loan', false)->fetch();

    	return $device !== null;
	}
	
	public function isGroupEmpty(int $group_id) : bool
	{
		$device = $this->database->table('devices')->where('group_id', $group_id)->fetch();

    	return $device == null;
	}
	
	public function isDeviceInMyAtelier(int $user_id, int $device_id) : bool
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

	
	public function addDevice(int $user_id, string $name,string $description, int $max_loan_duration,int $group_id) : void
    {
		$atelier_id = $this->database->table('ateliers')->where('admin_id',$user_id)->fetch();
        $this->database->table('devices')->insert(['name' => $name,'atelier_id' => $atelier_id, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id]);
    }
	
	public function addGroup(string $name,string $description) : void
    {
        $this->database->table('device_groups')->insert(['name' => $name, 'description' => $description]);
    }

	
	public function UserWithIdCanBorrowDeviceWithId(int $user_id, int $device_id): void //add
	{
		bdump($user_id);
		$this->database->table('forbidden_user_devices')->where('user_id', $user_id)->where('device_id', $device_id)->delete();
	}

	public function UserWithIdCanNotBorrowDeviceWithId(int $user_id, int $device_id) : void
	{
		bdump($device_id);
		$this->database->table('forbidden_user_devices')->insert(['user_id' => $user_id, 'device_id' => $device_id]);
	}

	public function get_forbidden_users(int $device_id) : array {
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
	public function get_not_forbidden_users(int $device_id) : array {
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

}
//musime omezit schopnosti vyucujiciho jen na atelier kam patri ,vyucujici edituje svoje zarizeni, urcuje misto a cas vypujceni, omezi vypujcku veci na konkretni studenty v atelieru


//???upravuje seznam registrovaných uživatelů přiřazených k ateliéru, kteří si mohou půjčovat vybavení ///s palem

//osetrit všechno co se muze stat