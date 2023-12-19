<?php
/**
 * author: ZJZN
 * createTime: 2023/12/18 17:23
 * description:
 */

namespace hinink\SeaFileStorage;

use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Config;
use Seafile\Client\Http\Client;
use Seafile\Client\Resource\Library;

class SeaFileAdapter extends AbstractAdapter
{
	use NotSupportingVisibilityTrait, StreamedReadingTrait;

	private string $baseUri;


	private Client $client;

	private $library;

	public function __construct($server)
	{
		$this->baseUri = $server;
	}

	/**
	 * @throws Exception
	 */
	public function getClient(Config $config)
	{
		if ($config->has('token')) {
			$token = $config->get('token');
		} else {
			$token = '';
			throw new Exception('token is empty', 410);
			# todo 根据 账号密码获取Token
		}

		$this->client = new Client(
			[
				'base_uri' => $this->baseUri,
				'debug'    => $config->get('debug', false),
				'headers'  => [
					'Authorization' => 'Token ' . $token
				]
			]
		);
	}

	/**
	 * @throws Exception
	 */
	public function getLibrary(Config $config)
	{
		$libraryResource = new Library($this->client);
		$this->library   = $libraryResource->getById($config->get('repo_id'));
	}


	public function write($path, $contents, Config $config)
	{
		// TODO: Implement write() method.
	}

	public function writeStream($path, $resource, Config $config)
	{
		// TODO: Implement writeStream() method.
	}

	public function update($path, $contents, Config $config)
	{
		// TODO: Implement update() method.
	}

	public function updateStream($path, $resource, Config $config)
	{
		// TODO: Implement updateStream() method.
	}

	public function rename($path, $newpath)
	{
		// TODO: Implement rename() method.
	}

	public function copy($path, $newpath)
	{
		// TODO: Implement copy() method.
	}

	public function delete($path)
	{
		// TODO: Implement delete() method.
	}

	public function deleteDir($dirname)
	{
		// TODO: Implement deleteDir() method.
	}

	public function createDir($dirname, Config $config)
	{
		// TODO: Implement createDir() method.
	}

	public function has($path)
	{
		// TODO: Implement has() method.
	}

	public function listContents($directory = '', $recursive = false)
	{
		// TODO: Implement listContents() method.
	}

	public function getMetadata($path)
	{
		// TODO: Implement getMetadata() method.
	}

	public function getSize($path)
	{
		// TODO: Implement getSize() method.
	}

	public function getMimetype($path)
	{
		// TODO: Implement getMimetype() method.
	}

	public function getTimestamp($path)
	{
		// TODO: Implement getTimestamp() method.
	}

	public function read($path)
	{
		// TODO: Implement read() method.
	}
}