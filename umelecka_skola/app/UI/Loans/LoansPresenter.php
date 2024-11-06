<?php

declare(strict_types=1);


namespace App\UI\Loans;

use Nette\Application\UI\Presenter;

class LoansPresenter extends Presenter
{
    // Akce pro zobrazení aktuálních výpůjček
    public function actionCurrent(): void
    {
        // Zde načti aktuální výpůjčky z databáze
        $this->template->currentLoans = $this->getCurrentLoans();
    }

    // Akce pro zobrazení minulých výpůjček
    public function actionPast(): void
    {
        // Zde načti minulé výpůjčky z databáze
        $this->template->pastLoans = $this->getPastLoans();
    }

    // Metody pro získání výpůjček (příklad)
    private function getCurrentLoans()
    {
        // Příklad vrácení dat (můžeš upravit dle tvé databáze)
        return [
            (object)[
                'device' => (object)['name' => 'Device A'],
                'loan_start' => new \DateTime('2024-10-10 10:00'),
                'loan_end' => new \DateTime('2024-10-12 10:00'),
                'status' => 'active',
            ],
        ];
    }

    private function getPastLoans()
    {
        // Příklad vrácení dat
        return [
            (object)[
                'device' => (object)['name' => 'Device B'],
                'loan_start' => new \DateTime('2024-09-01 10:00'),
                'loan_end' => new \DateTime('2024-09-10 10:00'),
                'status' => 'returned',
            ],
        ];
    }
}
