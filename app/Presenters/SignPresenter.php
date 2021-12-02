<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Forms\SignInFormFactory;
use App\Forms\SignUpFormFactory;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

final class SignPresenter extends Presenter
{
	#[Persistent]
	public string $backlink = '';

	private SignInFormFactory $signInFactory;

	private SignUpFormFactory $signUpFactory;


	public function __construct(SignInFormFactory $signInFactory, SignUpFormFactory $signUpFactory)
	{
		$this->signInFactory = $signInFactory;
		$this->signUpFactory = $signUpFactory;
	}

	/**
	 * Sign-in form factory.
	 */
	protected function createComponentSignInForm(): Form
	{
		return $this->signInFactory->create(function (): void {
			$this->restoreRequest($this->backlink);
			$this->redirect('Dashboard:');
		});
	}

	/**
	 * Sign-up form factory.
	 */
	protected function createComponentSignUpForm(): Form
	{
		return $this->signUpFactory->create(function (): void {
			$this->redirect('Dashboard:');
		});
	}

	public function actionOut(): void
	{
		$this->getUser()->logout();
	}
}
