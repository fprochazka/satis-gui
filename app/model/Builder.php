<?php

namespace App\Model;

use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\IConsumer;
use Kdyby\Monolog\Logger;
use Nette;
use Nette\Utils\Json;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Process\Process;
use Tracy\Debugger;



/**
 * @author Martin Bažík <martin@bazik.sk>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Builder extends Nette\Object implements IConsumer
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
	 * @var string
	 */
	private $composerHome;

	/**
	 * @var PackageManager
	 */
	private $packages;

	/**
	 * @var Connection
	 */
	private $rabbit;

	/**
	 * @var Logger
	 */
	private $logger;



	public function __construct($outputDir, $binFile, $composerHome, PackageManager $packages, Connection $rabbit, Logger $logger)
	{
		$this->outputDir = realpath($outputDir);
		$this->binFile = realpath($binFile);
		$this->composerHome = $composerHome;
		$this->packages = $packages;
		$this->rabbit = $rabbit;
		$this->logger = $logger->channel('satis-builder');
	}



	public function enqueueBuild()
	{
		$this->rabbit->getProducer('satisBuild')
			->publish(Json::encode(['at' => new \DateTime()]));
	}



	public function process($message)
	{
		if (!$message instanceof AMQPMessage) {
			return self::MSG_REJECT;
		}

		if (!$request = Json::decode($message->body, Json::FORCE_ARRAY)) {
			return self::MSG_REJECT;
		}

		$process = $this->buildCommand();

		try {
			$process->run();

			if (!$process->isSuccessful()) {
				throw new \RuntimeException('Build of satis package has failed: ' . $process->getErrorOutput(), $process->getExitCode());
			}

			$this->logger->addDebug('Build of satis package was successful');

		} catch (\RuntimeException $e) {
			file_put_contents(Debugger::$logDirectory . '/failure-output-' . date('YmdHis') . '.' . uniqid() . '.log', $process->getOutput());
			$this->logger->addWarning($e->getMessage());
			return self::MSG_REJECT_REQUEUE;

		} catch (\Exception $e) {
			Debugger::log($e, Debugger::ERROR);
			return self::MSG_REJECT;
		}

		return self::MSG_ACK;
	}



	/**
	 * @param array $packages
	 * @param callable $callback
	 * @return Process
	 */
	public function runBuild(array $packages = [], callable $callback = NULL)
	{
		$this->buildCommand($packages)->run($callback);
	}



	/**
	 * @param array $packages
	 * @return Process
	 */
	protected function buildCommand(array $packages = [])
	{
		$this->packages->compileConfig();

		$command = sprintf(
			'php %s --no-ansi --no-interaction build %s %s %s',
			escapeshellarg($this->binFile),
			escapeshellarg($this->packages->getConfigFile()),
			escapeshellarg($this->outputDir),
			implode(' ', $packages)
		);

		$process = new Process($command, $this->outputDir, [
			'COMPOSER_HOME' => $this->composerHome
		]);
		$process->setTimeout(30 * 60);

		return $process;
	}

}
