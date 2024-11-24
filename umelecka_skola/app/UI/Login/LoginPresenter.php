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

		$form->addEmail('email', 'Email:')->addRule($form::MaxLength, 'Email is limited to a maximum of 50 characters.', 50)->setRequired('Plase enter an email');
		$form->addPassword('password', 'Password:')->addRule($form::MaxLength, 'Password is limited to a maximum of 50 characters.', 50)->setRequired('Plase enter your password');
		$form->addSubmit('login', 'Log in');
		//$form->addButton('signup', 'Sign up')->setHtmlAttribute('onclick', 'window.location.href="'.$this->link('signupClicked!').'"');
		
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
			$this->getUser()->setExpiration('10 minutes');
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

		$form->addEmail('email', 'Email:')->addRule($form::MaxLength, 'Email is limited to a maximum of 50 characters.', 50)->setRequired('Plase enter an email');
		$form->addText('username', 'Username:')->addRule($form::MaxLength, 'Username is limited to a maximum of 50 characters.', 50)->setRequired('Please enter a username');
		$form->addPassword('password', 'Password:')->addRule($form::MaxLength, 'Password is limited to a maximum of 50 characters.', 50)->setRequired('Plase enter your password')->addRule($form::MinLength, 'Password must be at least %d characters long.', 8);
		$form->addSubmit('signup', 'Sign up');
		
		$form->onSuccess[] = [$this, 'validateSignUp']; 
		return $form;
	}

	public function validateSignUp(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->loginService->signUp($data->email, $data->password, $data->username);
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

	public function actionOut(): void
	{
		$this->getUser()->logout();
		$this->redirect('Login:login');
	}

}
