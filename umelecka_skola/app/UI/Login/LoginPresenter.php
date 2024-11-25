<?php

/**
 * LoginPresenter class
 * 
 * This presenter manages the login and signup processes for the application.
 * It provides forms for user authentication, including login and registration,
 * and utilizes the LoginService for handling authentication logic.
 * 
 * @package App\UI\Login
 */
declare(strict_types=1);

namespace App\UI\Login;

use Nette\Application\UI\Form;
use Nette;
use App\Core\LoginService;

final class LoginPresenter extends Nette\Application\UI\Presenter
{

	/**
     * @var LoginService $loginService Service for handling user authentication.
     */
	private $loginService;

	/**
     * Constructor for the LoginPresenter.
     * 
     * @param LoginService $loginService Injected service for handling login functionality.
     */
	public function __construct(LoginService $loginService)
	{
		//initialises the login service
		$this->loginService = $loginService;
	}

	/**
     * Creates the login form component.
     * 
     * @return Form The login form with fields for email and password.
     */
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

	/**
     * Handles the signup click action, redirecting to the signup page.
     * 
     * @return void
     */
	public function handleSignupClicked() : void
	{
		$this->redirect('Login:signup');
	}

	/**
     * Handles the login click action, redirecting to the login page.
     * 
     * @return void
     */
	public function handleLoginClicked() : void
	{
		$this->redirect('Login:login');
	}

	 /**
     * Validates the login form data and logs the user in if valid.
     * 
     * @param Form $form The login form component.
     * @param \stdClass $data The data submitted through the login form.
     * @return void
     */
	public function validateLogin(Form $form, \stdClass $data) : void
	{
		try
		{
			$this->getUser()->setAuthenticator($this->loginService)->login($data->email, $data->password);
			$this->getUser()->setExpiration('30 minutes');
			$this->redirect('MainPage:mainpage');
		}
		catch (Nette\Security\AuthenticationException $e)
		{
			$form->addError('Invalid email or password');
		}
	}

	/**
     * Creates the signup form component.
     * 
     * @return Form The signup form with fields for email, username, and password.
     */
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

	/**
     * Validates the signup form data and registers the user if valid.
     * 
     * @param Form $form The signup form component.
     * @param \stdClass $data The data submitted through the signup form.
     * @return void
     */
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

	/**
     * Renders the login page.
     * 
     * Sets a specific CSS class for the login page template.
     *
     * @return void
     */
	public function renderLogin(): void
	{
		$this->template->pageClass = 'login-page'; // Pro přihlášení
	}

	/**
     * Renders the signup page.
     * 
     * Sets a specific CSS class for the signup page template.
     *
     * @return void
     */
	public function renderSignup(): void
	{
		$this->template->pageClass = 'signup-page'; // Pro registraci
	}

	/**
     * Logs out the user and redirects to the login page.
     * 
     * @return void
     */
	public function actionOut(): void
	{
		$this->getUser()->logout();
		$this->redirect('Login:login');
	}

}
