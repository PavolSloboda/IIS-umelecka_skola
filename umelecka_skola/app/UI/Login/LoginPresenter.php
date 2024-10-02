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
	}

	public function createComponentLoginForm() : Form
	{
		$form = new Form;

		$form->addEmail('email', 'Email:')->setRequired('Plase enter an email');
		$form->addPassword('password', 'Password:')->setRequired('Plase enter your password');
		$form->addSubmit('login', 'Log in');
		$form->addButton('signup', 'Sign up')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('signupClicked!').'"');
		
		$form->onSuccess[] = [$this, 'validateLogin']; 
		return $form;
	}

	public function handleSignupClicked() : void
	{
		$this->redirect('Login:signup');
	}

	public function handleLoginClicked() : void
	{
		$this->redirect('Login:login');
	}

	public function validateLogin(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->getUser()->setAuthenticator($this->loginService)->login($data->email, $data->password);
			$this->redirect('MainPage:mainpage');
		}
		catch (Nette\Security\AuthenticationException $e)
		{
			$form->addError('Invalid email or password');
		}
	}

	public function createComponentSignUpForm() : Form
	{
		
		$form = new Form;

		$form->addEmail('email', 'Email:')->setRequired('Plase enter an email');
		$form->addPassword('password', 'Password:')->setRequired('Plase enter your password');
		$form->addSubmit('signup', 'Sign up');
		$form->addButton('login', 'Log in')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('loginClicked!').'"');
		
		$form->onSuccess[] = [$this, 'validateSignUp']; 
		return $form;
	}

	public function validateSignUp(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->loginService->signUp($data->email, $data->password);
			$this->redirect('Login:login');
		}
		catch(Nette\Security\AuthenticationException $e)
		{
			$form->addError('An error occured during sign up');
		}
	}

	public function renderLogin(): void
	{
    	$this->template->pageClass = 'login-page'; // Pro přihlášení
	}

	public function renderSignup(): void
	{
    	$this->template->pageClass = 'signup-page'; // Pro registraci
	}

}
