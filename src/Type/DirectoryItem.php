<?php
/**
 * author: ZJZN
 * createTime: 2023/12/19 10:45
 * description:
 */

namespace hinink\SeaFileStorage\Type;

use Seafile\Client\Type\DirectoryItem as BaseDirectoryItem;

class DirectoryItem extends BaseDirectoryItem
{
	public $last_modified = '';

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