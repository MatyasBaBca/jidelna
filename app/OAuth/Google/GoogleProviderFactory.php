<?php

namespace App\OAuth\Google;

use League\OAuth2\Client\Provider\Google;
use Nette\Application\LinkGenerator;

final class GoogleProviderFactory
{
	private string $clientId;

	private string $clientSecret;

	private LinkGenerator $linkGenerator;

	public function __construct(
		string $clientId,
		string $clientSecret,
		LinkGenerator $linkGenerator
	) {
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->linkGenerator = $linkGenerator;
	}

	public function create(): Google
	{
		return new Google([
			'clientId' => $this->clientId,
			'clientSecret' => $this->clientSecret,
			'redirectUri' => $this->linkGenerator->link('Sign:google'),
			'hostedDomain' => 'ossp.cz'
		]);
	}
}
