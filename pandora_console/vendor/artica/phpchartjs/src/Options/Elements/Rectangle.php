<?php

namespace Artica\PHPChartJS\Options\Elements;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Rectangle
 *
 * @package Artica\PHPChartJS\Options\Elements
 */
class Rectangle implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    const BORDER_SKIPPED_BOTTOM = 'bottom';
    const BORDER_SKIPPED_LEFT   = 'left';
    const BORDER_SKIPPED_TOP    = 'top';
    const BORDER_SKIPPED_RIGHT  = 'right';

    /**
     * Bar fill color.
     *
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $backgroundColor;

    /**
     * Bar stroke width.
     *
     * @default 0
     * @var int
     */
    private $borderWidth;

    /**
     * Bar stroke color.
     *
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $borderColor;

    /**
     * Skipped (excluded) border: 'bottom', 'left', 'top' or 'right'.
     *
     * @default self::BORDER_SKIPPED_BOTTOM
     * @var string
     */
    private $borderSkipped;

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
     * @return Rectangle
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = is_null($backgroundColor) ? null : strval($backgroundColor);

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
     * @return Rectangle
     */
    public function setBorderWidth($borderWidth)
    {
        $this->borderWidth = intval($borderWidth);

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
     * @return Rectangle
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = is_null($borderColor) ? null : strval($borderColor);

        return $this;
    }

    /**
     * @return string
     */
    public function getBorderSkipped()
    {
        return $this->borderSkipped;
    }

    /**
     * @param string $borderSkipped
     *
     * @return Rectangle
     */
    public function setBorderSkipped($borderSkipped)
    {
        $this->borderSkipped = is_null($borderSkipped) ? null : strval($borderSkipped);

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
