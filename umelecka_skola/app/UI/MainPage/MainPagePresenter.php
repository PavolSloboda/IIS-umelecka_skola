<?php

/**
 * MainPagePresenter class
 * 
 * This presenter manages the main page view and ensures the user is logged in.
 * If the user is not authenticated, they are redirected to the login page.
 * 
 * @package App\UI\MainPage
 */

declare(strict_types=1);

namespace App\UI\MainPage;

use Nette\Application\UI\Form;
use Nette;

final class MainPagePresenter extends Nette\Application\UI\Presenter
{

	/**
     * Performs the startup actions for the main page.
     * 
     * Checks if the user is logged in. If the user is not logged in, redirects to the login page.
     *
     * @return void
     */
	protected function startup() : void
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Login:login');
		}
	}
}
