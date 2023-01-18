<?php

namespace Artica\PHPChartJS\Options\Elements;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Point
 *
 * @package Artica\PHPChartJS\Options\Elements
 */
class Point implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    const STYLE_CIRCLE       = 'circle';
    const STYLE_CROSS        = 'cross';
    const STYLE_CROSS_ROT    = 'crossRot';
    const STYLE_DASH         = 'dash';
    const STYLE_LINE         = 'line';
    const STYLE_RECT         = 'rect';
    const STYLE_RECT_ROUNDED = 'rectRounded';
    const STYLE_RECT_ROT     = 'rectRot';
    const STYLE_RECT_STAR    = 'star';
    const STYLE_TRIANGLE     = 'triangle';

    /**
     * Point radius.
     *
     * @default 3
     * @var int
     */
    private $radius;

    /**
     * Point style.
     *
     * @default self::STYLE_CIRCLE
     * @var string
     */
    private $pointStyle;

    /**
     * Point rotation (in degrees).
     *
     * @default 0
     * @var int
     */
    private $rotation;

    /**
     * Point fill color.
     *
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $backgroundColor;

    /**
     * Point stroke width.
     *
     * @default 1
     * @var int
     */
    private $borderWidth;

    /**
     * Point stroke color.
     *
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $borderColor;

    /**
     * Extra radius added to point radius for hit detection.
     *
     * @default 1
     * @var int
     */
    private $hitRadius;

    /**
     * Point radius when hovered.
     *
     * @default 4
     * @var int
     */
    private $hoverRadius;

    /**
     * Stroke width when hovered.
     *
     * @default 1
     * @var int
     */
    private $hoverBorderWidth;

    /**
     * @return int
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     *
     * @return Point
     */
    public function setRadius($radius)
    {
        $this->radius = intval($radius);

        return $this;
    }

    /**
     * @return string
     */
    public function getPointStyle()
    {
        return $this->pointStyle;
    }

    /**
     * @param string $pointStyle
     *
     * @return Point
     */
    public function setPointStyle($pointStyle)
    {
        $this->pointStyle = is_null($pointStyle) ? null : strval($pointStyle);

        return $this;
    }

    /**
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     *
     * @return Point
     */
    public function setRotation($rotation)
    {
        $this->rotation = intval($rotation);

        return $this;
    }

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
     * @return Point
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
     * @return Point
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
     * @return Point
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = is_null($borderColor) ? null : strval($borderColor);

        return $this;
    }

    /**
     * @return int
     */
    public function getHitRadius()
    {
        return $this->hitRadius;
    }

    /**
     * @param int $hitRadius
     *
     * @return Point
     */
    public function setHitRadius($hitRadius)
    {
        $this->hitRadius = intval($hitRadius);

        return $this;
    }

    /**
     * @return int
     */
    public function getHoverRadius()
    {
        return $this->hoverRadius;
    }

    /**
     * @param int $hoverRadius
     *
     * @return Point
     */
    public function setHoverRadius($hoverRadius)
    {
        $this->hoverRadius = intval($hoverRadius);

        return $this;
    }

    /**
     * @return int
     */
    public function getHoverBorderWidth()
    {
        return $this->hoverBorderWidth;
    }

    /**
     * @param int $hoverBorderWidth
     *
     * @return Point
     */
    public function setHoverBorderWidth($hoverBorderWidth)
    {
        $this->hoverBorderWidth = intval($hoverBorderWidth);

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
