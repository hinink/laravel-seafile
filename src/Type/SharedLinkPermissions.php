<?php

namespace hinink\SeaFileStorage\Type;

use Sdo\Bitmask\AbstractBitmask;

/**
 * Bitmask for share links permissions.
 *
 * @see https://download.seafile.com/published/web-api/v2.1/share-links.md#user-content-Create%20Share%20Link
 * @package hinink\SeaFileStorage\Type
 */
class SharedLinkPermissions extends AbstractBitmask
{
    const CAN_DOWNLOAD = 1;
    const CAN_EDIT = 2;
}