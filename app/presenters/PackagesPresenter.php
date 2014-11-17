<?php

namespace App\Presenters;

use App\Model\Builder;
use App\Model\PackageManager;
use Nette\Application\UI\Form;
use PDOException;
use RuntimeException;



/**
 * Homepage presenter.
 */
class PackagesPresenter extends SecuredPresenter
{

	/** @var PackageManager */
	private $packageManager;

	/** @var Builder */
	private $builder;



	public function __construct(PackageManager $packageManager, Builder $builder)
	{
		$this->packageManager = $packageManager;
		$this->builder = $builder;
	}


	protected function createComponentFormAdd()
	{
		$form = new Form;

		$form->addText('type', 'Type')->setDefaultValue('vcs')->setRequired();
		$form->addText('url', 'Url')->setRequired();
		$form->addText('name', 'Package name')->setRequired();

		$form->addSubmit('btnSubmit', 'Add');

		$form->onSuccess[] = $this->addPackage;

		return $form;
	}


	public function addPackage(Form $form)
	{
		$values = $form->getValues();

		try {
			$this->packageManager->add($values->type, $values->url, $values->name);
			$this->packageManager->compileConfig();
			$this->flashMessage('Package added.', 'success');

		} catch (PDOException $e) {
			if ($e->getCode() === '23000') {
				$this->flashMessage('Package already exists.', 'danger');
			}
		}

		$this->redirect('this');
	}


	public function renderDefault()
	{
		$packages = $this->packageManager->getAll();
		$this->template->packages = $packages;
	}


	public function handleDelete($id)
	{
		$this->packageManager->delete($id);
		$this->packageManager->compileConfig();
		$this->flashMessage('Package deleted.', 'success');
		$this->redirect('this');
	}


	public function handleBuild()
	{
		$this->builder->enqueueBuild();
		$this->flashMessage('Repository build was scheduled.', 'success');
		$this->redirect('this');
	}

}
