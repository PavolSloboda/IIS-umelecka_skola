<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class DevicesService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}

	/*
	* @return Nette\Database\table\ActiveRow[]
	*/
	public function showAllDevices() : array
	{
		$result = $this->database->table('devices')->fetchAll();
		return $result;
	}
	public function showAllAvailableDevices() : array
	{
		$result = $this->database->table('devices')->where('available', true)->fetchAll();
		return $result;
	}
}
