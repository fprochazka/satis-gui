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
		$configFile = $this->packages->getConfigFile();

		$packageList = implode(' ', $packages);
		$command = sprintf('php %s build %s %s %s', escapeshellarg($this->binFile), escapeshellarg($configFile), escapeshellarg($this->outputDir), $packageList);

		$process = new Process($command);
		$process->run($callback);

		return $process->getOutput();
	}

}
