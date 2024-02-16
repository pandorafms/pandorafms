<?php

namespace Artica\PHPChartJS\Collection;

use Artica\PHPChartJS\Collection\ArrayAccess;
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
