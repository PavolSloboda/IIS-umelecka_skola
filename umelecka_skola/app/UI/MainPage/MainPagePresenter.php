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

	public function createComponentMainPageForm() : Form
	{
		$form = new Form;
		$form->addButton('devicemanagement', 'Device management')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('DevicesClicked!').'"');
		$form->addButton('logout', 'Log out')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('loginClicked!').'"');

		return $form;
	}

	public function handleLoginClicked() : void
	{
		$this->getUser()->logout();
		$this->redirect('Login:login');
	}

	public function handleDevicesClicked() : void
	{
		$this->redirect('Devices:devices');
	}
}
