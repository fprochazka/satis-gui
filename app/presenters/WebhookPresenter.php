<?php

namespace App\Presenters;

use App\Model\Builder;
use Tracy\Debugger;



/**
 * Webhook presenter.
 */
class WebhookPresenter extends BasePresenter
{

	/** @var Builder */
	private $builder;



	public function __construct(Builder $builder)
	{
		$this->builder = $builder;
	}



	protected function startup()
	{
		parent::startup();
		Debugger::$productionMode = TRUE;
	}



	public function actionDefault($password)
	{
		if ($password === $this->context->parameters['webhook']['password']) {
			$this->builder->enqueueBuild();
		}

		$logDir = $this->context->expand('%logDir%/api-calls.log');

		$request = '[' . date('Y-m-d H:i:s') . "]\n";
		foreach ($this->getHttpRequest()->getHeaders() as $header => $value) {
			$request .= $header . ': ' . $value . "\n";
		}
		$request .= $this->getHttpRequest()->getRawBody() . "\n\n";
		file_put_contents($logDir, $request, FILE_APPEND);

		$this->terminate();
	}

}
