<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Collection\ArrayAccess;
use JsonSerializable;

/**
 * Class PluginsCollection
 *
 * @package Artica\PHPChartJS
 */
class PluginsCollection extends ArrayAccess implements JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize():mixed
    {
        return $this->data;
    }
}
