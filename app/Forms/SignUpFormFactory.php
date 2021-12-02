<?php

declare(strict_types=1);

namespace App\Forms;

use App\Exception\DuplicateNameException;
use App\Model\UserFacade;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use stdClass;

final class SignUpFormFactory
{
	use Nette\SmartObject;

	private FormFactory $factory;

	private UserFacade $userFacade;

	public function __construct(FormFactory $factory, UserFacade $userFacade)
	{
		$this->factory = $factory;
		$this->userFacade = $userFacade;
	}

	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addText('username', 'Pick a username:')
			->setRequired('Please pick a username.');

		$form->addEmail('email', 'Your e-mail:')
			->setRequired('Please enter your e-mail.');

		$form->addPassword('password', 'Create a password:')
			->setOption('description', sprintf('at least %d characters', $this->userFacade::PASSWORD_MIN_LENGTH))
			->setRequired('Please create a password.')
			->addRule($form::MIN_LENGTH, null, $this->userFacade::PASSWORD_MIN_LENGTH);

		$form->addSubmit('send', 'Sign up');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
			try {
				$this->userFacade->add($values->username, $values->email, $values->password);
			} catch (DuplicateNameException $e) {
				/** @var TextInput */
				$username = $form->getComponent('username');
				$username->addError('Username is already taken.');
				//$form['username']->addError('Username is already taken.');
				return;
			}
			$onSuccess();
		};

		return $form;
	}
}