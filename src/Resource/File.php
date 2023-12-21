<?php
/**
 * author: Hinink Z
 * createTime: 2023/12/19 10:48
 * description:
 */

namespace hinink\SeaFileStorage\Resource;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use hinink\SeaFileStorage\Type\DirectoryItem;
use hinink\SeaFileStorage\Type\FileHistoryItem;
use hinink\SeaFileStorage\Type\Library as LibraryType;
use Psr\Http\Message\ResponseInterface;

class File extends Resource
{
	const API_VERSION = '2';

	/**
	 * Mode of operation: copy
	 */
	const OPERATION_COPY = 1;

	/**
	 * Mode of operation: move
	 */
	const OPERATION_MOVE = 2;

	/**
	 * Mode of operation: create
	 */
	const OPERATION_CREATE = 3;

	/**
	 * @param LibraryType   $library
	 * @param DirectoryItem $item
	 * @param string        $dir
	 * @param int           $reuse
	 * @return array|string|string[]|null
	 * @throws GuzzleException
	 */
	public function getDownloadUrl(LibraryType $library, DirectoryItem $item, string $dir = '/', int $reuse = 1)
	{
		$url = $this->getApiBaseUrl()
			. '/repos/'
			. $library->id
			. '/file/'
			. '?reuse=' . $reuse
			. '&p=' . $this->urlEncodePath($dir . $item->name);

		$response    = $this->client->request('GET', $url);
		$downloadUrl = (string)$response->getBody();

		return preg_replace("/\"/", '', $downloadUrl);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function urlEncodePath(string $path)
	{
		return implode('/', array_map('rawurlencode', explode('/', (string)$path)));
	}

	/**
	 * Get download URL of a file from a Directory item
	 *
	 * @param LibraryType   $library Library instance
	 * @param DirectoryItem $item Item instance
	 * @param string        $localFilePath Save file to path
	 * @param string        $dir Dir string
	 * @param int           $reuse Reuse more than once per hour
	 *
	 * @return ResponseInterface
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function downloadFromDir(LibraryType $library, DirectoryItem $item, string $localFilePath, string $dir, int $reuse = 1): ResponseInterface
	{
		if (is_readable($localFilePath)) {
			throw new Exception('File already exists');
		}
		$downloadUrl = $this->getDownloadUrl($library, $item, $dir, $reuse);

		return $this->client->request('GET', $downloadUrl, [ 'save_to' => $localFilePath ]);
	}

	public function download(LibraryType $library, string $filePath, string $localFilePath, int $reuse = 1)
	{
		$item          = $this->getFileDetail($library, $filePath);
		$localFilePath = $localFilePath ?: $item->name;
		return $this->downloadFromDir($library, $item, $localFilePath, $item->dir, $reuse);
	}

	/**
	 * @param LibraryType $library
	 * @param string      $remoteFilePath
	 * @return DirectoryItem
	 * @throws GuzzleException
	 */
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

	/**
	 * @param LibraryType $library
	 * @param bool        $newFile
	 * @param string      $dir
	 * @return string
	 * @throws GuzzleException
	 */
	public function getUploadUrl(LibraryType $library, bool $newFile = true, string $dir = '/'): string
	{
		$url        = $this->getApiBaseUrl() . '/repos/' . $library->id . '/' . ($newFile ? 'upload' : 'update') . '-link/' . '?p=' . $dir;
		$response   = $this->client->request('GET', $url);
		$uploadLink = (string)$response->getBody();
		return preg_replace("/\"/", '', $uploadLink);
	}

	/**
	 * @param LibraryType   $library
	 * @param DirectoryItem $dirItem
	 * @param string        $newFilename
	 * @return bool
	 * @throws GuzzleException
	 */
	public function rename(LibraryType $library, DirectoryItem $dirItem, string $newFilename): bool
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

	/**
	 * @param LibraryType $library
	 * @param string      $filePath
	 * @return bool
	 * @throws GuzzleException
	 */
	public function remove(LibraryType $library, string $filePath): bool
	{
		if (empty($filePath)) {
			return false;
		}
		$uri = sprintf(
			'%s/repos/%s/file/?p=%s',
			$this->clipUri($this->getApiBaseUrl()),
			$library->id,
			$this->urlEncodePath($filePath)
		);

		$response = $this->client->request(
			'DELETE',
			$uri,
			[
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		return $response->getStatusCode() === 200;
	}

	public function move(LibraryType $srcLibrary, string $srcFilePath, LibraryType $dstLibrary, string $dstDirectoryPath): bool
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
	public function copy(LibraryType $srcLibrary, string $srcFilePath, LibraryType $dstLibrary, string $dstDirectoryPath, int $operation = self::OPERATION_COPY): bool
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

		$uri      = sprintf(
			'%s/repos/%s/file/?p=%s',
			$this->clipUri($this->getApiBaseUrl()),
			$srcLibrary->id,
			$this->urlEncodePath($srcFilePath)
		);
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

	public function update(LibraryType $library, $fullPath, $contents, int $replace = 1)
	{
		return $this->upload($library, $fullPath, $contents, $replace);
	}

	public function upload(LibraryType $library, $fullPath, $contents, int $replace = 1, string $parent_dir = '/')
	{
		$uri          = $this->getUploadUrl($library, true, $parent_dir);
		$fileBaseName = basename($fullPath);
		$path         = dirname($fullPath);
		$path         = $path === '.' ? '/' : $path . '/';
		$multipart    = [
			[
				'headers'  => [ 'Content-Type' => 'application/octet-stream' ],
				'name'     => 'file',
				'contents' => $contents,
				'filename' => $fileBaseName
			],
			[
				'name'     => 'parent_dir',
				'contents' => $parent_dir,
			],
			[
				'name'     => 'replace',
				'contents' => $replace,
			],
		];
		if ($path !== '/') {
			$multipart[] = [
				'name'     => 'relative_path',
				'contents' => $path,
			];
		}
		$options  = [
			'headers'   => [ 'Accept' => '*/*' ],
			'multipart' => $multipart
		];
		$response = $this->client->request('POST', $uri, $options);
		return $response->getStatusCode() === 200;
	}

	/**
	 * Get file revision download URL
	 *
	 * @param LibraryType     $library Source library object
	 * @param DirectoryItem   $dirItem Item instance
	 * @param FileHistoryItem $fileHistoryItem FileHistory item instance
	 *
	 * @return string|string[]
	 * @throws GuzzleException
	 */
	public function getFileRevisionDownloadUrl(LibraryType $library, DirectoryItem $dirItem, FileHistoryItem $fileHistoryItem)
	{
		$url = $this->getApiBaseUrl()
			. '/repos/'
			. $library->id
			. '/file/revision/'
			. '?p=' . $this->urlEncodePath($dirItem->path . $dirItem->name)
			. '&commit_id=' . $fileHistoryItem->id;

		$response = $this->client->request('GET', $url);

		return preg_replace("/\"/", '', (string)$response->getBody());
	}

	/**
	 * Download file revision
	 *
	 * @param LibraryType     $library Source library object
	 * @param DirectoryItem   $dirItem Item instance
	 * @param FileHistoryItem $fileHistoryItem FileHistory item instance
	 * @param string          $localFilePath Save file to path. Existing files will be overwritten without warning
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	public function downloadRevision(LibraryType $library, DirectoryItem $dirItem, FileHistoryItem $fileHistoryItem, string $localFilePath): ResponseInterface
	{
		$downloadUrl = $this->getFileRevisionDownloadUrl($library, $dirItem, $fileHistoryItem);

		return $this->client->request('GET', $downloadUrl, [ 'save_to' => $localFilePath ]);
	}

	/**
	 * Get history of a file DirectoryItem
	 *
	 * @param LibraryType   $library Library instance
	 * @param DirectoryItem $item Item instance
	 *
	 * @return FileHistoryItem[]
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public function getHistory(LibraryType $library, DirectoryItem $item)
	{
		$url = $this->getApiBaseUrl()
			. '/repos/'
			. $library->id
			. '/file/history/'
			. '?p=' . $this->urlEncodePath($item->path . $item->name);

		$response = $this->client->request('GET', $url);

		$json = json_decode($response->getBody());

		$fileHistoryCollection = [];

		foreach ($json->commits as $lib) {
			$fileHistoryCollection[] = (new FileHistoryItem)->fromJson($lib);
		}

		return $fileHistoryCollection;
	}

	/**
	 * Create empty file on Seafile server
	 *
	 * @param LibraryType   $library Library instance
	 * @param DirectoryItem $item Item instance
	 *
	 * @return bool
	 * @throws GuzzleException
	 */
	public function create(LibraryType $library, DirectoryItem $item): bool
	{
		// do not allow empty paths
		if (empty($item->path)) {
			return false;
		}

		$uri = sprintf(
			'%s/repos/%s/file/?p=%s',
			$this->clipUri($this->getApiBaseUrl()),
			$library->id,
			$this->urlEncodePath($item->path . $item->name)
		);

		$response = $this->client->request(
			'POST',
			$uri,
			[
				'headers'   => [ 'Accept' => 'application/json' ],
				'multipart' => [
					[
						'name'     => 'operation',
						'contents' => 'create',
					],
				],
			]
		);
// @todo Return the actual response instead of bool
		return $response->getStatusCode() === 201;
	}
}

