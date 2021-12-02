<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Forms\SignInFormFactory;
use App\Forms\SignUpFormFactory;
use App\Model\UserFacade;
use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;
use Throwable;
use Tracy\Debugger;

final class SignPresenter extends Presenter
{
	#[Persistent]
	public string $backlink = '';

	private SignInFormFactory $signInFactory;

	private SignUpFormFactory $signUpFactory;

	private Google $google;

	private UserFacade $userFacade;

	public function __construct(
		SignInFormFactory $signInFactory,
		SignUpFormFactory $signUpFactory,
		UserFacade $userFacade,
		Google $google
	) {
		$this->signInFactory = $signInFactory;
		$this->signUpFactory = $signUpFactory;
		$this->userFacade = $userFacade;
		$this->google = $google;
	}

	public function handleGoogleLogin(): void
	{
		$this->backlink = '';
		$authUrl = $this->google->getAuthorizationUrl([
			'redirect_uri' => $this->link('//google'),
		]);
		$this->getSession(Google::class)->state = $this->google->getState();
		$this->redirectUrl($authUrl);
	}

	public function actionGoogle(): void
	{
		$error = $this->getParameter('error');
		if (!is_null($error)) {
			$this->flashMessage('Google login failed', 'error');
			$this->redirect('Sign:in');
		}

		$state = $this->getParameter('state');
		$stateInSession = $this->getSession(Google::class)->state;
		if (is_null($state) || is_null($stateInSession) || !hash_equals($stateInSession, $state)) {
			$this->flashMessage('Invalid CSRF token', 'error');
			$this->redirect('Sign:in');
		}

		unset($this->getSession(Google::class)->state);

		$accessToken = $this->google->getAccessToken('authorization_code', [
			'code' => $this->getParameter('code'),
			'redirect_uri' => $this->link('//google'),
		]);

		try {
			/** @var GoogleUser $googleUser */
			$googleUser = $this->google->getResourceOwner($accessToken);
		} catch (HostedDomainException $e) {
			Debugger::log($e->getMessage(), Debugger::ERROR);
			$this->flashMessage('Přihlašte se pomocí školního účtu.', 'error');
			$this->redirect('Sign:in');
		}

		$googleId = $googleUser->getId();
		try {
			$user = $this->userFacade->findByGoogleId($googleId);
			$this->user->login($user->username);
			$this->redirect('Dashboard:');
		} catch (AuthenticationException $e) {
			//
		}

		$googleEmail = $googleUser->getEmail();
		try {
			$user = $this->userFacade->findByEmail($googleEmail);
			$this->user->login($user->username);
			$this->redirect('Dashboard:');
		} catch (AuthenticationException $e) {
			//
		}
		bdump($googleUser);
		$user = $this->userFacade->registerFromGoogle($googleUser);
		$this->user->login($user->username);
		$this->redirect('Dashboard:');
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
		$this->redirect('Sign:in');
	}
}
