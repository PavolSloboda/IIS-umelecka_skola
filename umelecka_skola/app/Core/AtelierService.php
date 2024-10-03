<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Database\Explorer;

final class AtelierService
{
	private Explorer $database;

	public function __construct(Explorer $database)
	{
		$this->database = $database;
	}
}
