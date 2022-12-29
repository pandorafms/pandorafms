<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Elements\Arc;
use Artica\PHPChartJS\Options\Elements\Line;
use Artica\PHPChartJS\Options\Elements\Point;
use Artica\PHPChartJS\Options\Elements\Rectangle;
use JsonSerializable;

/**
 * Class Elements
 *
 * @package Artica\PHPChartJS\Options
 */
class Elements implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var Rectangle
     */
    private $rectangle;

    /**
     * @var Line
     */
    private $line;

    /**
     * @var Point
     */
    private $point;

    /**
     * @var Arc
     */
    private $arc;

    /**
     * @return Rectangle
     */
    public function getRectangle()
    {
        return $this->rectangle;
    }

    /**
     * @return Rectangle
     */
    public function rectangle()
    {
        if (is_null($this->rectangle)) {
            $this->rectangle = new Rectangle();
        }

        return $this->rectangle;
    }

    /**
     * @return Line
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return Line
     */
    public function line()
    {
        if (is_null($this->line)) {
            $this->line = new Line();
        }

        return $this->line;
    }

    /**
     * @return Point
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @return Point
     */
    public function point()
    {
        if (is_null($this->point)) {
            $this->point = new Point();
        }

        return $this->point;
    }

    /**
     * @return Arc
     */
    public function getArc()
    {
        return $this->arc;
    }

    /**
     * @return Arc
     */
    public function arc()
    {
        if (is_null($this->arc)) {
            $this->arc = new Arc();
        }

        return $this->arc;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
