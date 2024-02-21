<?php

namespace Artica\PHPChartJS\Options\Scales;

use Artica\PHPChartJS\Collection\ArrayAccess;
use Artica\PHPChartJS\ArraySerializableInterface;
use JsonSerializable;

/**
 * Class RAxisCollection
 *
 * @package Artica\PHPChartJS\Collection
 */
class RAxisCollection extends ArrayAccess implements ArraySerializableInterface, JsonSerializable
{
    /**
     * @return array
     */
    public function getArrayCopy()
    {
        $rows = [];
        foreach ($this->data as $row) {
            /** @var RAxis $row */
            $rows[] = $row->getArrayCopy();
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
