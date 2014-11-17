<?php

namespace Console\Commands;

use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Martin Bažík <martin@bazik.sk>
 */
class Install extends Command
{

	/** @var Container */
	private $container;



	public function __construct(Container $container)
	{
		parent::__construct();
		$this->container = $container;
	}


	protected function configure()
	{
		$this->setName('app:install')
			->setDescription('install');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$db = $this->container->getByType('Nette\Database\Connection');

		try {
			$db->query('CREATE TABLE "main"."users" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL, "username" VARCHAR UNIQUE , "password" VARCHAR)');
			$db->query('CREATE TABLE "main"."packages" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL, "type" VARCHAR, "url" VARCHAR UNIQUE, "name" VARCHAR UNIQUE)');

			$output->writeln('App installed');

		} catch (\PDOException $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
		}
	}

}
