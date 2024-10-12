<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use Nette\Application\UI\Form;
use Nette;

final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	private $devices; 

	public function __construct(DevicesService $devices)
	{
		$this->devices = $devices;
	}

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
	}

	public function renderDevices() : void
	{
		$this->template->result = $this->devices->showAllAvailableDevices();
	}

}
