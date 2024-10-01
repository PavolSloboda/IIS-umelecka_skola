<?php

declare(strict_types=1);

namespace App\UI\Login;

use Nette\Application\UI\Form;
use Nette;
use App\Core\LoginService;

final class LoginPresenter extends Nette\Application\UI\Presenter
{

	private $loginService;

	public function __construct(LoginService $loginService)
	{
		//initialises the login service
		$this->loginService = $loginService;
		//$this->loginService->signUp('test@test.com', 'test');
	}

	public function createComponentLoginForm() : Form
	{
		$form = new Form;

		$form->addEmail('email', 'Email:')->setRequired('Plase enter an email');
		$form->addPassword('password', 'Password:')->setRequired('Plase enter your password');
		$form->addSubmit('login', 'Log in');
		$form->addButton('signup', 'Sign up')->setHtmlAttribute('onclick', 'redirectToSign()');
		
		$form->onSuccess[] = [$this, 'validateLogin']; 
		return $form;
	}

	public function redirectToSign() : void
	{
		$this->redirect('Devices:devices');
	}

	public function validateLogin(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->getUser()->setAuthenticator($this->loginService)->login($data->email, $data->password);
			$this->redirect('Devices:devices');
		}
		catch (Nette\Security\AuthenticationException $e)
		{
			$form->addError('Invalid email or password');
		}
	}

	public function createSignUpForm(Form $form, \stdClass $data) : Form
	{
		
		$form = new Form;

		$form->addEmail('email', 'Email:')->setRequired('Plase enter an email');
		$form->addPassword('password', 'Password:')->setRequired('Plase enter your password');
		$form->addSubmit('signup', 'Sign up');
		$form->addButton('signup', 'Log in')->setHtmlAttribute('onclick', 'redirectToSign()');
		
		$form->onSuccess[] = [$this, 'validateSignUp']; 
		return $form;
	}

	public function validateSignUp() : void
	{

	}
}
