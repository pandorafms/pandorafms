<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Collection\ArrayAccess;
use JsonSerializable;

/**
 * Class LabelsCollection
 *
 * @package Artica\PHPChartJS
 */
class LabelsCollection extends ArrayAccess implements JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize():mixed
    {
        return $this->data;
    }
}
