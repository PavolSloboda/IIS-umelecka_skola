<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;

final class DevicesService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	/*
	* @return Nette\Database\table\ActiveRow[]
	*///vypis vsech dostupnych zarizeni
	public function showAllDevices() : array
	{
		$result = $this->database->table('devices')->fetchAll();
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
	
	//vypis jmen vsech dostupnych zarizeni
	public function getAvailableDevices(): array
    {
        $devices = $this->database->table('devices')->where('loan', FALSE);

		$deviceOptions = [];
        foreach ($devices as $device) {
            $deviceOptions[$device->device_id] = $device->name;
        }

        return $deviceOptions;
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

	public function editDevice( int $deviceId, string $name, string $description, int $max_loan_duration, int $group_id, bool $loan): void
    {
        $device = $this->database->table('devices')->get($deviceId);

        if ($device) {
            $device->update(['name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id, 'loan' => $loan]);
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
		$this->database->table('devices')->where('device_id', $id)->delete();
		
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

	public function addDevice(string $name,string $description, int $max_loan_duration,int $group_id) : void
    {
        $this->database->table('devices')->insert(['name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id]);
    }
	
	public function addGroup(string $name,string $description) : void
    {
        $this->database->table('device_groups')->insert(['name' => $name, 'description' => $description]);
    }

	
}
//musime omezit schopnosti vyucujiciho jen na atelier kam patri
//pokud zarizeni patri do jeho atelieru tak se mu zobrazi rezervace//nastavit ze rezervaci muze videt taky vlastnik zarizeni //jde udelat az s palem

//???upravuje seznam registrovaných uživatelů přiřazených k ateliéru, kteří si mohou půjčovat vybavení ///s palem

