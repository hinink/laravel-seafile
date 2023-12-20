<?php
/**
 * author: ZJZN
 * createTime: 2023/12/19 10:48
 * description:
 */

namespace hinink\SeaFileStorage\Resource;

use GuzzleHttp\Exception\GuzzleException;
use Seafile\Client\Resource\File as BaseFile;
use Seafile\Client\Type\DirectoryItem;
use Seafile\Client\Type\Library as LibraryType;

class File extends BaseFile
{
	public function getFileDetail(LibraryType $library, string $remoteFilePath): DirectoryItem
	{
		$url        = $this->getApiBaseUrl()
			. '/repos/'
			. $library->id
			. '/file/detail/'
			. '?p=' . $this->urlEncodePath($remoteFilePath);
		$response   = $this->client->request('GET', $url);
		$path       = dirname($remoteFilePath);
		$path       = $path === '.' ? '/' : $path . '/';
		$json       = json_decode((string)$response->getBody());
		$json->path = $path;
		return (new DirectoryItem)->fromJson($json);
	}

	public function download(LibraryType $library, string $filePath, string $localFilePath, int $reuse = 1)
	{
		$item          = $this->getFileDetail($library, $filePath);
		$localFilePath = $localFilePath ?: $item->name;
		return $this->downloadFromDir($library, $item, $localFilePath, $item->dir, $reuse);
	}

	public function rename($library, $dirItem, string $newFilename): bool
	{
		$filePath = $dirItem->path === '/' ? $dirItem->dir . $dirItem->name : $dirItem->dir . $dirItem->path . $dirItem->name;
		if (empty($filePath)) {
			throw new InvalidArgumentException('Invalid file path: must not be empty');
		}

		if (empty($newFilename) || strpos($newFilename, '/') === 0) {
			throw new InvalidArgumentException('Invalid new file name: length must be >0 and must not start with /');
		}

		$uri      = sprintf(
			'%s/repos/%s/file/?p=%s&reloaddir=%s',
			$this->clipUri($this->getApiBaseUrl()),
			$library->id,
			$this->urlEncodePath($filePath),
			'true'
		);
		$response = $this->client->request(
			'POST',
			$uri,
			[
				'headers'     => [ 'Accept' => 'application/json' ],
				'form_params' => [
					'operation' => 'rename',
					'newname'   => $newFilename,
				],
			]
		);
		$success  = $response->getStatusCode() === 200;
		if ($success) {
			$dirItem->name = $newFilename;
		}
		return $success;
	}

	public function move(
		LibraryType $srcLibrary,
		string $srcFilePath,
		LibraryType $dstLibrary,
		string $dstDirectoryPath
	): bool
	{
		return $this->copy($srcLibrary, $srcFilePath, $dstLibrary, $dstDirectoryPath, self::OPERATION_MOVE);
	}

	/**
	 * Copy a file
	 *
	 * @param LibraryType $srcLibrary Source library object
	 * @param string      $srcFilePath Source file path
	 * @param LibraryType $dstLibrary Destination library object
	 * @param string      $dstDirectoryPath Destination directory path
	 * @param int         $operation Operation mode
	 *
	 * @return bool
	 * @throws GuzzleException
	 */
	public function copy(
		LibraryType $srcLibrary,
		string $srcFilePath,
		LibraryType $dstLibrary,
		string $dstDirectoryPath,
		int $operation = self::OPERATION_COPY
	): bool
	{
		// do not allow empty paths
		if (empty($srcFilePath) || empty($dstDirectoryPath)) {
			return false;
		}

		$operationMode = 'copy';
		$returnCode    = 200;

		if ($operation === self::OPERATION_MOVE) {
			$operationMode = 'move';
			$returnCode    = 301;
		}

		$uri = sprintf(
			'%s/repos/%s/file/?p=%s',
			$this->clipUri($this->getApiBaseUrl()),
			$srcLibrary->id,
			$this->urlEncodePath($srcFilePath)
		);
//		dump($dstLibrary->id);
//		dump($dstDirectoryPath);
//		dump($uri);die;
		$response = $this->client->request(
			'POST',
			$uri,
			[
				'headers'   => [ 'Accept' => 'application/json' ],
				'multipart' => [
					[
						'name'     => 'operation',
						'contents' => $operationMode,
					],
					[
						'name'     => 'dst_repo',
						'contents' => $dstLibrary->id,
					],
					[
						'name'     => 'dst_dir',
						'contents' => $dstDirectoryPath,
					],
				],
			]
		);

		return $response->getStatusCode() === $returnCode;
	}
}

