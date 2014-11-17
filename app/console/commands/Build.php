<?php

namespace Console\Commands;

use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;



/**
 * @author Martin Bažík <martin@bazik.sk>
 */
class Build extends Command
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
		$this->setName('satis:build')
			->addArgument('packages', InputArgument::OPTIONAL, 'list of packages you want to rebuild', '')
			->setDescription('build');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('building package.json...');

		$packages = explode(' ', $input->getArgument('packages'));

		/** @var \App\Model\Builder $builder */
		$builder = $this->container->getByType('App\Model\Builder');
		$builder->runBuild($packages, function ($type, $buffer) use($output) {
			if ($type === Process::ERR) {
				$output->write(sprintf('<error>%s</error>', $buffer));
			} else {
				$output->write(sprintf('<info>%s</info>', $buffer));
			}
		});

		$output->writeln('built package.json');
	}

}
