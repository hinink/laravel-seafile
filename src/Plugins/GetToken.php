<?php

namespace hinink\SeaFileStorage\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;


class GetToken extends AbstractPlugin
{

	/**
	 * Get the method name.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return 'getToken';
	}

	public function handle()
	{
		return $this->filesystem->getAdapter()->getToken();
	}
}