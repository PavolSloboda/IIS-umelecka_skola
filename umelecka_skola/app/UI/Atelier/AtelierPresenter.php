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
}
