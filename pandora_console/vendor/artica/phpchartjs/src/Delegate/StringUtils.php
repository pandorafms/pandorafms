<?php

namespace Artica\PHPChartJS\Delegate;

/**
 * Trait StringUtils
 *
 * @package Artica\PHPChartJS\Delegate
 */
trait StringUtils
{
    /**
     * @param array $input
     *
     * @return array
     */
    public function recursiveToString(array $input)
    {
        array_walk_recursive($input, function (&$value) {
            $value = strval($value);
        });

        return $input;
    }
}
