<?php

namespace App\Presenters;

use App\Model\HtmlIndexManager;



/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	/**
	 * @var HtmlIndexManager
	 */
	private $indexManager;



	public function __construct(HtmlIndexManager $indexManager)
	{
		$this->indexManager = $indexManager;
	}



	public function actionDefault()
	{
		if (!$this->user->isLoggedIn()) {
			$this->setView('public');
		}
	}



	public function renderDefault()
	{
		$this->template->webhookPassword = $this->context->expand('%webhook.password%');

		try {
			$this->template->bodyTagContentsFile = $this->indexManager->getBodyTagContentsFile();

		} catch (\LogicException $e) {
			// meh
		}
	}



	public function renderPublic()
	{
		try {
			$this->template->headTagContentsFile = $this->indexManager->getHeadTagContentsFile();
			$this->template->bodyTagContentsFile = $this->indexManager->getBodyTagContentsFile();

		} catch (\LogicException $e) {
			$this->error($e->getMessage());
		}
	}

}
