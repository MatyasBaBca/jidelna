<?php

declare(strict_types=1);

namespace App\Module\Auxiliary\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;

final class Error4xxPresenter extends Presenter
{
	public function startup(): void
	{
		parent::startup();
		if (!$this->getRequest()?->isMethod(Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(BadRequestException $exception): void
	{
		/** @var int */
		$code = $exception->getCode();
		$file = __DIR__ . "/templates/Error/{$code}.latte";
		/** @var Template $this->template */
		$this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
	}
}
