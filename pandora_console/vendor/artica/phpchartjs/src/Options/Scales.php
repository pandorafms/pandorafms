<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Scales\XAxis;
use Artica\PHPChartJS\Options\Scales\XAxisCollection;
use Artica\PHPChartJS\Options\Scales\YAxis;
use Artica\PHPChartJS\Options\Scales\YAxisCollection;
use JsonSerializable;

/**
 * Class Scales
 * @package Artica\PHPChartJS\Options
 */
class Scales implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var XAxisCollection
     */
    private $x;

    /**
     * @var YAxisCollection
     */
    private $y;

    /**
     * @return XAxis
     */
    public function createX()
    {
        return new XAxis();
    }

    /**
     * @return YAxis
     */
    public function createY()
    {
        return new YAxis();
    }

    /**
     * @return XAxis
     */
    public function getX()
    {
        if (is_null($this->x)) {
            $this->x = new XAxis();
        }

        return $this->x;
    }

    /**
     * @return YAxis
     */
    public function getY()
    {
        if (is_null($this->y)) {
            $this->y = new YAxis();
        }

        return $this->y;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
