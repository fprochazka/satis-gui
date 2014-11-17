<?php

namespace App\Model;

use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\IConsumer;
use Kdyby\Monolog\Logger;
use Nette;
use Nette\Utils\Json;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Process\Process;



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



	public function __construct($outputDir, $binFile, PackageManager $packages, Connection $rabbit, Logger $logger)
	{
		$this->outputDir = realpath($outputDir);
		$this->binFile = realpath($binFile);
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
			return FALSE;
		}

		if (!$request = Json::decode($message->body, Json::FORCE_ARRAY)) {
			return FALSE;
		}

		$process = $this->build();

		if (!$process->isSuccessful()) {
			$this->logger->addWarning('Build of satis package has failed');

		} else {
			$this->logger->addDebug('Build of satis package was successful');
		}

		return self::MSG_ACK;
	}



	/**
	 * @param array $packages
	 * @param callable $callback
	 * @return Process
	 */
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

		return $process;
	}

}
