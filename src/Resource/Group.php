<?php

namespace hinink\SeaFileStorage\Resource;

use Exception;
use hinink\SeaFileStorage\Type\Group as GroupType;

/**
 * Handles everything regarding Seafile groups.
 *
 * @author    Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene+_seafile_github@sdo.sh>
 * @copyright 2015-2020 Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene+_seafile_github@sdo.sh>
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/Schmidt-DevOps/seafile-php-sdk
 */
class Group extends Resource
{
    const API_VERSION = '2';

    /**
     * List groups
     *
     * @return GroupType[]
     * @throws Exception
     */
    public function getAll(): array
    {
        $response = $this->client->request('GET', $this->getApiBaseUrl() . '/groups/');

        $json = json_decode($response->getBody());

        $groupCollection = [];

        foreach ($json->groups as $group) {
            $groupCollection[] = (new GroupType)->fromJson($group);
        }

        return $groupCollection;
    }
}
