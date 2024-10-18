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

	public function showAllAvailableLoans(int $userId) : array
	{
		$result = $this->database->table('loan')->where('user_id',$userId)->where('status IN ?', ['reservation', 'loan'])->fetchAll();
		return $result;
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

        // stav se aktualizuje na zruseno pokud majitel nezmenil stav a tedy si predmet nikdo nevyzvedl
        $this->database->table('loan')
            ->where('loan_start < ? AND status = ?', $currentTime->format('Y-m-d H:i:s'), 'reservation')
            ->update(['status' => 'cancelled']);
		
    }

	public function updateLoanStatus(): void
	{
		$deviceIds = $this->database->table('loan')
			->select('device_id') 
			->where('status IN ?', ['completed', 'cancelled']) 
			->fetchAll(); 

		if (!empty($deviceIds)) {
			
			foreach ($deviceIds as $device) {
				$this->database->table('devices')
				->where('device_id IN ?', $device->device_id)
				->update(['loan' => false]);
			}
		}
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

        if ($device && !$device->loan) {
            $this->database->table('loan')->insert(['user_id' => $userId,'device_id' => $deviceId,'loan_start' => $loanStart,'loan_end' => $loanEnd,'status' => 'reservation',]);
            $device->update(['loan' => TRUE,]);
        }
    }

	public function editDevice( int $deviceId, string $name, string $description, int $max_loan_duration, int $group_id): void
    {
        $device = $this->database->table('devices')->get($deviceId);

        if ($device) {
            $device->update(['name' => $name, 'description' => $description, 'max_loan_duration' => $max_loan_duration, 'group_id' => $group_id]);
        }
    }

	public function editGroup( int $group_id, string $name, string $description): void
    {
        $group = $this->database->table('device_groups')->get($group_id);

        if ($group) {
            $group->update(['name' => $name, 'description' => $description]);
        }
    }

	public function getDeviceById(int $deviceId)
    {
        return $this->database->table('devices')->get($deviceId);
    }

	public function deleteDevice(int $id) : void
	{
		$this->database->table('devices')->where('device_id', $id)->delete();
		
	}
	
	public function getGroupById(int $group_id) 
    {
        return $this->database->table('device_groups')->get($group_id);
    }

	public function isNotDeviceReserve(int $device_id) : bool
	{
		$device = $this->database->table('devices')->where('device_id', $device_id)->where('loan', false)->fetch();

    	return $device !== null;
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

//vyucujici si vypujcuje jen z vlastnich atelieru
//pridavani zarizeni 
//edit groups, add groups
//zakas pujcovani zarizeni
//delete groups(jenom ty co se nepouzivaji)
//???upravuje seznam registrovaných uživatelů přiřazených k ateliéru, kteří si mohou půjčovat vybavení

