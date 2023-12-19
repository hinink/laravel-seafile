<?php
/**
 * author: ZJZN
 * createTime: 2023/12/19 10:48
 * description:
 */

namespace hinink\SeaFileStorage\Resource;

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
		$path       = pathinfo($remoteFilePath, PATHINFO_DIRNAME);
		$path       = $path === '.' ? '/' : $path;
		$json       = json_decode((string)$response->getBody());
		$json->path = $path;
		return (new DirectoryItem)->fromJson($json);
	}

	public function rename($library, $dirItem, string $newFilename): bool
	{
		$filePath = $dirItem->path === '/' ? $dirItem->dir . $dirItem->name : $dirItem->dir . $dirItem->path . '/' . $dirItem->name;

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

	protected function urlEncodePath(string $path)
	{
		return implode('/', array_map('rawurlencode', explode('/', (string)$path)));
	}
}

