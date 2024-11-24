<?php

declare(strict_types=1);

namespace App\UI\MainPage;

use Nette\Application\UI\Form;
use Nette;

final class MainPagePresenter extends Nette\Application\UI\Presenter
{

	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
	}
}
