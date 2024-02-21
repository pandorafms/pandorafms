<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Collection\ArrayAccess;
use JsonSerializable;

/**
 * Class DataSetCollection
 *
 * @package Artica\PHPChartJS
 */
class DataSetCollection extends ArrayAccess implements JsonSerializable
{
    /**
     * @return array
     */
    public function getArrayCopy()
    {
        $rows = [];
        foreach ($this->data as $row) {
            /** @var DataSet $row */
            $rows[] = $row->getArrayCopy();
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function jsonSerialize():mixed
    {
        $rows = [];
        foreach ($this->data as $row) {
            /** @var DataSet $row */
            $rows[] = $row->jsonSerialize();
        }

        return $rows;
    }
}
