<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;
use Nette\Application\UI\Form;
use Nette;


final class DashboardPresenter extends Nette\Application\UI\Presenter
{
	use RequireLoggedUser;
	protected function createComponentEditGameForm(): Form
    {

        $form = new Form;
        $form->addText('name', 'název:');
        $form->addText('publisher', 'vydavatel:');
        $form->addText('platform', 'platforma:');
        $form->addText('description', 'popis:');
        $form->addSubmit('send', 'Upravit');
        $form->onSuccess[] = [$this, 'editGameFormSucceeded'];
        return $form;
    }

    public function editGameFormSucceeded(array $values): void
    {
        //bdump($values);
        $gameId = $this->getParameter('id');

        $game = $this->database
            ->table('games')
            ->get($gameId);
        $game->update($values);

        $this->flashMessage('Editovano.', 'success');
    }
}
