<?php

use Nette\Configurator;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Configurator;

$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');

if ($configurator->isDebugMode()) {
	$configurator->createRobotLoader()
		->addDirectory(__DIR__)
		->register();
}

$configurator->addConfig(__DIR__ . '/config/config.neon');
if (file_exists($localConfig = __DIR__ . '/config/config.local.neon')) {
	$configurator->addConfig($localConfig);
}

return $configurator->createContainer();
