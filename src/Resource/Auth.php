<?php

namespace hinink\SeaFileStorage\Resource;

/**
 * This is currently just a "facade" for the auth endpoint
 *
 * @author    Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene+_seafile_github@sdo.sh>
 * @copyright 2015-2020 Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene+_seafile_github@sdo.sh>
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/Schmidt-DevOps/seafile-php-sdk
 */
class Auth extends Resource implements ResourceInterface
{
	const API_VERSION = '2';

	/**
	 * @param $username
	 * @param $password
	 * @return string
	 */
	public function getToken($username, $password)
	{
		$clippedBaseUri = $this->clipUri($this->getApiBaseUrl());
		$response       = $this->client->request(
			'POST',
			$clippedBaseUri . '/auth-token/',
			[
				'form_params' => [
					'username' => $username,
					'password' => $password,
				]
			]
		);
		$result         = json_decode((string)$response->getBody(), true);
		return isset($result['token']) ? $result['token'] : '';
	}
}
