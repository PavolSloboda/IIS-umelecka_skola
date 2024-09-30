<?php

declare(strict_types=1);

namespace App\UI\Home;

use Nette;
use App\Core\Service;


final class HomePresenter extends Nette\Application\UI\Presenter
{
	private $service;

	public function __construct(Service $service)
	{
		$this->service = $service;
	}
}
