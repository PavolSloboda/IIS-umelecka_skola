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

	protected function createComponentAddAtelierForm(): Form
	{
		$form = new Form;

		$form->addText('name', 'Name:');  
		$form->addText('admin_email', 'Email of atelier admin:');

		$form->addSubmit('submit', 'Add atelier');

		$form->onSuccess[] = [$this, 'processAddAtelierForm'];
		
		return $form;
	}

	public function processAddAtelierForm(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->atelier->createAtelier($data->name, $data->admin_email);
		}
		catch (\Exception $e)
		{
			$form->addError('An error occured while atempting to ad an atelier');
		}
	}
}
