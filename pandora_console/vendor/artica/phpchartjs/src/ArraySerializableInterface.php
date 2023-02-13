<?php

namespace Artica\PHPChartJS;

/**
 * Interface ArraySerializableInterface
 * @package Artica\PHPChartJS
 */
interface ArraySerializableInterface
{
    /**
     * Should return an array containing all values.
     *
     * @return array
     */
    public function getArrayCopy();
}
