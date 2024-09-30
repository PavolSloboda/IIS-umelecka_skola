<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Database\Explorer;

//TODO
//figure out what this should extend

//Main Service, other services will be derived from it
final class Service
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}
}
