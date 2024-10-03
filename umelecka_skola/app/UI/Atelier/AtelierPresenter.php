<?php

declare(strict_types=1);

namespace App\UI\Atelier;

use App\Core\AtelierService;
use Nette\Application\UI\Form;
use Nette;

final class AtelierPresenter extends Nette\Application\UI\Presenter
{
	private $atelier;

	public function __construct(AtelierService $atelier)
	{
		$this->atelier = $atelier;
	}

	public function renderAtelier() : void
	{
		$this->template->result = $this->atelier->showAllAteliers();
	}

	public function createComponentLogoutForm() : Form
	{
		$form = new Form;

		$form->addButton('logout', 'Log out')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('loginClicked!').'"');
		return $form;
	}

	public function handleLoginClicked() : void
	{
		$this->redirect('Login:login');
	}
}
