<?php

namespace Artica\PHPChartJS\Collection;

use Halfpastfour\Collection\Collection\ArrayAccess;
use Artica\PHPChartJS\Delegate;
use JsonSerializable as JsonSerializableInterface;

/**
 * Class Data
 *
 * @package Artica\PHPChartJS\Collection
 */
class Data extends ArrayAccess implements JsonSerializableInterface
{
    use Delegate\JsonSerializable;
}
