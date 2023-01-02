<?php

namespace Artica\PHPChartJS\Delegate;

use Artica\PHPChartJS\ArraySerializableInterface;

/**
 * Class ArraySerializable
 *
 * @package Artica\PHPChartJS\Model
 */
trait ArraySerializable
{
    /**
     * Returns an array copy of the properties and their current values in this class
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $currentValues = array_map(function ($value) {
            if (is_object($value)) {
                if ($value instanceof ArraySerializableInterface) {
                    return $value->getArrayCopy();
                }
            }

            return $value;
        }, get_object_vars($this));

        // Filter out null values and return the remaining.
        return array_filter($currentValues, function ($value, $key) {
            return ! is_null($value) && $key !== 'owner';
        }, ARRAY_FILTER_USE_BOTH);
    }
}
