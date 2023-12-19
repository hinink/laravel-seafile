<?php

namespace hinink\SeaFileStorage\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use Seafile\Client\Type\Library as LibraryType;

class UploadUrl extends AbstractPlugin
{

	/**
	 * Get the method name.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'getUploadUrl';
	}

	public function handle($library, $newFile = true, $dir = '/')
	{
		return $this->filesystem->getAdapter()->getUploadUrl($library, $newFile, $dir);
	}
}