<?php

namespace App\Model;

use Nette\Database\Context;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;



/**
 * @author Martin Bažík <martin@bazik.sk>
 */
class PackageManager
{

	const TABLE_NAME = 'packages';

	/** @var string */
	private $configFile;

	/** @var array */
	private $parameters;

	/** @var Context */
	private $db;

	/** @var Request */
	private $httpRequest;



	public function __construct($configFile, $parameters, Context $db, Request $httpRequest)
	{
		$this->configFile = $configFile;
		$this->parameters = $parameters;
		$this->db = $db;
		$this->httpRequest = $httpRequest;
	}


	public function add($type, $url, $packageName)
	{
		$this->db->table(self::TABLE_NAME)->insert([
			'type' => $type,
			'url' => $url,
			'name' => $packageName,
		]);
	}


	public function getAll()
	{
		return $this->db->table(self::TABLE_NAME)->order('name ASC');
	}


	public function delete($id)
	{
		$this->db->table(self::TABLE_NAME)->get($id)->delete();
	}


	public function compileConfig()
	{
		$repositories = $this->getAll();

		$domain = $this->httpRequest->getUrl()->getHostUrl();

		$config = [
			'homepage' => $domain,
			'repositories' => [
				 ["type" => "composer", "url" => "https://packagist.org/"],
			],
			'require' => [],
		];

		foreach ($this->parameters as $property => $value) {
			$config[$property] = $value;
		}

		foreach ($repositories as $repository) {
			$config['repositories'][] = [
				'type' => $repository->type,
				'url' => $repository->url
			];

			$config['require'][$repository->name] = '*';
		}

		$json = Json::encode($config, Json::PRETTY);
		FileSystem::write($this->configFile, $json);
	}


	/**
	 * @return string
	 */
	public function getConfigFile()
	{
		return $this->configFile;
	}

}
