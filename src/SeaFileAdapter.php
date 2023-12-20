<?php
/**
 * author: ZJZN
 * createTime: 2023/12/18 17:23
 * description:
 */

namespace hinink\SeaFileStorage;

use Cache;
use Exception;
use hinink\SeaFileStorage\Resource\Directory;
use hinink\SeaFileStorage\Resource\File;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Config;
use Seafile\Client\Http\Client;
use Seafile\Client\Resource\Library;

class SeaFileAdapter extends AbstractAdapter
{
	use NotSupportingVisibilityTrait, StreamedReadingTrait;

	protected string $baseUri;
	protected string $repo_id;
	protected string $username;
	protected string $password;
	protected Client $client;
	protected File $fileResource;
	protected $library;
	protected $parentDir = '/';

	public function __construct($server, $repo_id, $token, $username = '', $password = '')
	{
		$this->baseUri  = $server;
		$this->repo_id  = $repo_id;
		$this->token    = $token;
		$this->username = $username;
		$this->password = $password;
		$this->client   = $this->getClient();
		$this->library  = $this->getLibrary();
	}

	/**
	 * @throws Exception
	 */
	public function getClient()
	{
		if (!$this->token) {
			throw new Exception('token is empty', 410);
			# todo 根据 账号密码获取Token
		}
		return new Client(
			[
				'base_uri' => $this->baseUri,
				'debug'    => config('debug'),
				'headers'  => [
					'Authorization' => 'Token ' . $this->token
				]
			]
		);
	}

	public function getToken()
	{

	}

	/**
	 * @throws Exception
	 */
	public function getLibrary()
	{
		$libraryResource = new Library($this->client);
		return $libraryResource->getById($this->repo_id);
	}

	public function getUploadUrl($library, $newFile, $dir = '/')
	{

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

	/**
	 * @param $path
	 * @param $newpath
	 * @description The request was successful and 404 was returned.
	 * @return bool
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function rename($path, $newname)
	{
//		$fileResource = new File($this->client);
//		return $fileResource->move($this->library, $path, $this->library, $newpath);
//		try {
		$directory_item = $this->getMetadata($path);
		if ($directory_item) {
			return $this->fileResource->rename($this->library, $directory_item, $newname);
		}
		return false;
	}

	public function copy($path, $newpath)
	{

	}

	public function delete($path)
	{
		$directory_item = $this->getMetadata($path);
		if ($directory_item) {
			return $this->fileResource->remove($this->library, $path);
		}
		return false;
	}

	public function deleteDir($dirname)
	{
		try {
			$directoryResource = new Directory($this->client);
			return $directoryResource->remove($this->library, $dirname);
		} catch (Exception $exception) {
			return false;
		}
	}

	public function createDir($dirname, Config $config)
	{
		$directoryResource = new Directory($this->client);
		$recursive         = $config->get('recursive', true);
		return $directoryResource->create($this->library, $dirname, $this->parentDir, $recursive);
	}

	/**
	 * @param $path
	 * @return bool
	 */
	public function has($path)
	{
		$metadata = $this->getMetadata($path);
		if ($metadata) {
			return true;
		}
		return false;
	}

	public function listContents($directory = '', $recursive = false)
	{
		try {
			$contents          = [];
			$directoryResource = new Directory($this->client);
			$items             = $directoryResource->getAll($this->library, $directory);
			foreach ($items as $item) {
				$info = [
					'type'      => $item->type,
					'path'      => $directory === '' ? $item->name : $directory . '/' . $item->name,
					'timestamp' => $item->mtime->getTimestamp(),
					'dirname'   => $directory,
					'basename'  => $item->name,
					'filename'  => $item->name,
				];
				if ($info['type'] === 'file') {
					$info['extension'] = pathinfo($info['filename'], PATHINFO_EXTENSION);
					$info['size']      = $item->size;
					$info['filename']  = pathinfo($info['filename'], PATHINFO_FILENAME);
				}
				array_push($contents, $info);
			}
			return $contents;
		} catch (Exception $exception) {
			dump($exception->getMessage());
			return [];
		}

	}

	public function getMetadata($path)
	{
		try {
			$fileResource       = new File($this->client);
			$this->fileResource = $fileResource;
			return $fileResource->getFileDetail($this->library, $path);
		} catch (Exception $exception) {
			return false;
		}
	}

	public function getSize($path)
	{
		$stat = $this->getMetadata($path);
		if ($stat) {
			return [ 'size' => $stat->size ];
		}
		return false;
	}

	public function getMimetype($path)
	{
		$metadata = $this->getMetadata($path);
		return [ 'mimetype' => $metadata->type ];
	}

	public function getTimestamp($path)
	{
		$metadata = $this->getMetadata($path);
		if ($metadata) {
			return [ 'timestamp' => $metadata->mtime->getTimestamp() ];
		}

		return false;
	}

	public function read($path)
	{
		// TODO: Implement read() method.
	}

	/**
	 * @param array|string $path
	 * @return array|mixed|string
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getUrl($path)
	{
		try {
			$url = $path;
			if (is_array($path)) {
				$url = $path['url'];
				if ($path['cache'] and Cache::tags([ 'seafile', 'url' ])->has($url)) {
					return Cache::tags([ 'seafile', 'url' ])->get($url);
				}
			}
			$fileResource   = new File($this->client);
			$directory_item = $this->getMetadata($path);
			$full_url       = $fileResource->getDownloadUrl($this->library, $directory_item, $directory_item->path);
			if (is_array($path) and $path['cache']) {
				Cache::tags([ 'seafile', 'url' ])->put($url, $full_url, 3000);
			}
			return $full_url;
		} catch (Exception $exception) {
			dump($exception->getMessage());
			return false;
		}
	}

}
