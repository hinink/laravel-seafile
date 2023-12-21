<?php

namespace hinink\SeaFileStorage\Resource;

/**
 * Interface ResourceInterface
 */
interface ResourceInterface
{
	/**
	 * Clip tailing slash
	 *
	 * @param string $uri URI string
	 *
	 * @return mixed|string
	 */
	public function clipUri(string $uri): string;
}
