<?php

namespace hinink\SeaFileStorage\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use Seafile\Client\Type\Library as LibraryType;

class Download extends AbstractPlugin
{

	/**
	 * Get the method name.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'download';
	}

	public function handle($path, $savepath = '')
	{
		return $this->filesystem->getAdapter()->download($path, $savepath);
	}
}