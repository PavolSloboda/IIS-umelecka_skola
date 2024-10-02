<?php

declare(strict_types=1);

namespace App\UI\Devices;

use App\Core\DevicesService;
use Nette;

final class DevicesPresenter extends Nette\Application\UI\Presenter
{
	private $devices; 

	public function __construct(DevicesService $devices)
	{
		$this->devices = $devices;
	}

	public function renderDevices() : void
	{
		$this->template->result = $this->devices->showAllAvailableDevices();
	}
}
