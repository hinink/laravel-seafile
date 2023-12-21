<?php
/**
 * author: Hinink Z
 * createTime: 2023/12/19 10:45
 * description:
 */

namespace hinink\SeaFileStorage\Type;

use Seafile\Client\Resource\Resource;

class DirectoryItem extends Resource
{
	/**
	 * @var string
	 */
	public $id = "";

	/**
	 * @var bool
	 */
	public $dir = '/';

	/**
	 * @var DateTime
	 */
	public $mtime;

	/**
	 * @var string
	 */
	public $name = "";

	/**
	 * @var int|null
	 */
	public $org = null;

	/**
	 * @var string|null
	 */
	public $path = null;

	/**
	 * @var string|null
	 */
	public $repo = null;

	/**
	 * @var string
	 */
	public $size = "";

	/**
	 * @var string
	 */
	public $type = "";

	const TYPE_DIR = 'dir';
	const TYPE_FILE = 'file';

	/**
	 * Populate from array
	 *
	 * @param array $fromArray Create from array
	 *
	 * @return \Seafile\Client\Type\DirectoryItem
	 * @throws Exception
	 */
	public function fromArray(array $fromArray): DirectoryItem
	{
		$typeExists = array_key_exists('type', $fromArray);
		$dirExists  = array_key_exists('dir', $fromArray);

		if ($typeExists === false && $dirExists === true && is_bool($fromArray['dir'])) {
			$fromArray['type'] = $fromArray['dir'] === true ? self::TYPE_DIR : self::TYPE_FILE;
		}

		/**
		 * @var self $dirItem
		 */
		$dirItem = parent::fromArray($fromArray);

		return $dirItem;
	}
}