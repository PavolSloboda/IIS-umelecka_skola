<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('<presenter>/<action>[/<id>]', 'MainPage:mainpage');

		$router->addRoute('/myProfile', 'MyProfile:myProfile');    // Zobrazení profilu
		$router->addRoute('/myProfile/editProfile', 'MyProfile:editProfile');     // Úprava profilu
		$router->addRoute('/myProfile/passwordChange', 'MyProfile:passwordChange');  // Změna hesla
		$router->addRoute('/myProfile/currentLoans', 'MyProfile:currentLoans');  // Aktuální výpůjčky
		$router->addRoute('/myProfile/pastLoans', 'MyProfile:pastLoans');  // Minulé výpůjčky

		bdump($router); // Tento řádek vypíše nastavení routeru do debuggeru Tracy.

		return $router;
	}
}
