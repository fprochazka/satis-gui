<?php

namespace App\Model;

use Symfony\Component\Process\Process;



/**
 * @author Martin Bažík <martin@bazik.sk>
 */
class Builder
{

	/**
	 * @var string
	 */
	private $outputDir;

	/**
	 * @var string
	 */
	private $binFile;

	/**
	 * @var PackageManager
	 */
	private $packages;



	public function __construct($outputDir, $binFile, PackageManager $packages)
	{
		$this->outputDir = realpath($outputDir);
		$this->binFile = realpath($binFile);
		$this->packages = $packages;
	}



	public function build(array $packages = [], callable $callback = NULL)
	{
		$this->packages->compileConfig();

		$command = sprintf(
			'php %s --no-ansi --no-interaction build %s %s %s',
			escapeshellarg($this->binFile),
			escapeshellarg($this->packages->getConfigFile()),
			escapeshellarg($this->outputDir),
			implode(' ', $packages)
		);

		$process = new Process($command, $this->outputDir);
		$process->setTimeout(15 * 60);
		$process->run($callback);

		return $process->getOutput();
	}

}
