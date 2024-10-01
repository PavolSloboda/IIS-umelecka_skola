<?php

declare(strict_types=1);

namespace App\UI\Home;

use Nette;
use App\Core\Service;
use App\Core\LoginService;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	private $service;
	private $loginService;

	public function __construct(Service $service, LoginService $loginService)
	{
		$this->service = $service;
		$this->loginService = $loginService;
	}

}
