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
	public function showAllAvailableDevices() : array
	{
		$result = $this->database->table('devices')->where('loan',false)->fetchAll();
		return $result;
	}

	public function showAllAvailableLoans(int $userId) : array
	{
		$result = $this->database->table('loan')->where('user_id',$userId)->fetchAll();
		return $result;
	}

	public function showAllAvailableTypes() : array
	{
		$result = $this->database->table('device_groups')->fetchAll();
		return $result;
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

	public function editDevice( int $deviceId, string $name): void
    {
        $device = $this->database->table('devices')->get($deviceId);

        if ($device) {
            $device->update(['name' => $name]);
        }
    }

	public function getDeviceById(int $deviceId)
    {
        return $this->database->table('devices')->get($deviceId);
    }

	public function deleteDevice(int $id) : void
	{
		bdump($id);
		$this->database->table('devices')->where('device_id', $id)->delete();
		
	}
}
//maximalni doba vypujcky
//vyucujici si vypujcuje jen z vlastnich atelieru
//kdyz majitel nezmeni ze si to clovek vyzvedl automaticky pada reservace