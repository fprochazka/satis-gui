<?php

namespace App\Model;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HtmlIndexManager extends Nette\Object
{

	/**
	 * @var string
	 */
	private $indexFile;

	/**
	 * @var string
	 */
	private $tempDir;



	public function __construct($indexFile, $tempDir)
	{
		$this->indexFile = $indexFile;
		$this->tempDir = $tempDir;
	}



	public function getHeadTagContentsFile()
	{
		list($headFile, $bodyFile) = $this->build();
		return $headFile;
	}



	public function getBodyTagContentsFile()
	{
		list($headFile, $bodyFile) = $this->build();
		return $bodyFile;
	}



	private function build()
	{
		if (!file_exists($this->indexFile)) {
			throw new \LogicException("The index was not yet built");
		}

		$headFile = $this->tempDir . '/head.html';
		$bodyFile = $this->tempDir . '/body.html';

		if (file_exists($headFile) && file_exists($bodyFile) && filemtime($this->indexFile) < filemtime($headFile)) {
			return [$headFile, $bodyFile];
		}

		Nette\Utils\FileSystem::createDir($this->tempDir);

		$indexContents = file_get_contents($this->indexFile);
		if (!$m = Nette\Utils\Strings::match($indexContents, '~.*?\\<head\\>(?P<head>.*?)\\<\\/head\\>.*?\\<body\\>(?P<body>.*?)\\<\\/body\\>.*?~is')) {
			throw new \LogicException("The index could not be parsed");
		}

		$headTemplate = self::sanitize($m['head']);
		$bodyTemplate = self::sanitize($m['body']);

		file_put_contents($headFile, $headTemplate);
		file_put_contents($bodyFile, $bodyTemplate);

		return [$headFile, $bodyFile];
	}



	private static function sanitize($html)
	{
		return Nette\Utils\Strings::replace($html, '~\\<(?P<element>style|script)(?P<args>.*?)\\>(?P<content>.*?)\\<\\/\\1\\>~is', function ($m) {
			return '<' . $m['element'] . $m['args'] . ' n:syntax="off">' . $m['content'] . '</' . $m['element'] . '>';
		});
	}

}
