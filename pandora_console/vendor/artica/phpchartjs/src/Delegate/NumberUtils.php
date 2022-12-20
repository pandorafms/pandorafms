<?php

namespace Artica\PHPChartJS\Delegate;

/**
 * Trait NumberUtils
 *
 * @package Artica\PHPChartJS\Delegate
 */
trait NumberUtils
{

    /**
     * @param array $input
     *
     * @return array
     */
    public function recursiveToFloat(array $input)
    {
        array_walk_recursive($input, function (&$value) {
            $value = floatval($value);
        });

        return $input;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function recursiveToInt(array $input)
    {
        array_walk_recursive($input, function (&$value) {
            $value = intval($value);
        });

        return $input;
    }
}
