<?php

declare(strict_types=1);

namespace App\UI\Login;

use Nette\Application\UI\Form;
use Nette;

final class LoginPresenter extends Nette\Application\UI\Presenter
{

	public function createComponentLoginForm() : Form
	{
		$form = new Form;

		$form->addText('email', 'Email:');
		$form->addText('password', 'Password:');
		$form->addSubmit('login', 'Log in');
		$form->addSubmit('signup', 'Sign up');
		
		return $form;
	}

	public function validateLogin() : void
	{

	}


}
