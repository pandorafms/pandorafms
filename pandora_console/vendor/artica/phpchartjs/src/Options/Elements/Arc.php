<?php

namespace Artica\PHPChartJS\Options\Elements;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Arc
 *
 * @package Artica\PHPChartJS\Options\Elements
 */
class Arc implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * Arc fill color.
     *
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $backgroundColor;

    /**
     * Arc stroke color.
     *
     * @default '#fff'
     * @var string
     */
    private $borderColor;

    /**
     * Arc stroke width.
     *
     * @default 2
     * @var int
     */
    private $borderWidth;

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return Arc
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = is_null($backgroundColor) ? null : strval($backgroundColor);

        return $this;
    }

    /**
     * @return string
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * @param string $borderColor
     *
     * @return Arc
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = is_null($borderColor) ? null : strval($borderColor);

        return $this;
    }

    /**
     * @return int
     */
    public function getBorderWidth()
    {
        return $this->borderWidth;
    }

    /**
     * @param int $borderWidth
     *
     * @return Arc
     */
    public function setBorderWidth($borderWidth)
    {
        $this->borderWidth = intval($borderWidth);

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
