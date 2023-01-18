<?php

namespace Artica\PHPChartJS\Options\Elements;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Line
 * @package Artica\PHPChartJS\Options\Elements
 */
class Line implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /** https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/lineCap */
    const CAP_STYLE_BUTT   = 'butt';
    const CAP_STYLE_ROUND  = 'round';
    const CAP_STYLE_SQUARE = 'square';

    /** https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/lineJoin */
    const JOIN_STYLE_ROUND = 'round';
    const JOIN_STYLE_BEVEL = 'bevel';
    const JOIN_STYLE_MITER = 'miter';

    const FILL_LOCATION_ZERO = 'zero';
    const FILL_LOCATION_TOP = 'top';
    const FILL_LOCATION_BOTTOM = 'bottom';
    const FILL_LOCATION_TRUE = true;
    const FILL_LOCATION_FALSE = false;

    /**
     * Bézier curve tension (0 for no Bézier curves).
     * @default 0.4
     * @var float
     */
    private $tension;

    /**
     * Line fill color.
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $backgroundColor;

    /**
     * Line stroke width.
     * @default 3
     * @var int
     */
    private $borderWidth;

    /**
     * Line stroke color.
     * @default 'rgba(0,0,0,0.1)'
     * @var string
     */
    private $borderColor;

    /**
     * Line cap style.
     * @default self::CAP_STYLE_BUTT
     * @var string
     */
    private $borderCapStyle;

    /**
     * Line dash. See https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/setLineDash
     * @default []
     * @var int[]
     */
    private $borderDash;

    /**
     * Line dash offset.
     * @default 0
     * @var float
     */
    private $borderDashOffset;

    /**
     * Line join style.
     * @default self::JOIN_STYLE_MITER
     * @var string
     */
    private $borderJoinStyle;

    /**
     * true to keep Bézier control inside the chart, false for no restriction.
     * @default true
     * @var bool
     */
    private $capBezierPoints;

    /**
     * Fill location: 'zero', 'top', 'bottom', true (eq. 'zero') or false (no fill).
     * @default self::FILL_LOCATION_TRUE
     * @var bool|string
     */
    private $fill;

    /**
     * true to show the line as a stepped line (tension will be ignored).
     * @default false
     * @var bool
     */
    private $stepped;

    /**
     * @return float
     */
    public function getTension()
    {
        return $this->tension;
    }

    /**
     * @param float $tension
     * @return Line
     */
    public function setTension($tension)
    {
        $this->tension = floatval($tension);
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
     * @return Line
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
     * @return Line
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
     * @return Line
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = is_null($borderColor) ? null : strval($borderColor);
        return $this;
    }

    /**
     * @return string
     */
    public function getBorderCapStyle()
    {
        return $this->borderCapStyle;
    }

    /**
     * @param string $borderCapStyle
     * @return Line
     */
    public function setBorderCapStyle($borderCapStyle)
    {
        $this->borderCapStyle = is_null($borderCapStyle) ? null : strval($borderCapStyle);
        return $this;
    }

    /**
     * @return int[]
     */
    public function getBorderDash()
    {
        return $this->borderDash;
    }

    /**
     * @param int[] $borderDash
     * @return Line
     */
    public function setBorderDash($borderDash)
    {
        if (is_array($borderDash)) {
            array_walk_recursive(
                $borderDash,
                function (&$value) {
                    $value = intval($value);
                }
            );
            $this->borderDash = $borderDash;
        }
        return $this;
    }

    /**
     * @return float
     */
    public function getBorderDashOffset()
    {
        return $this->borderDashOffset;
    }

    /**
     * @param float $borderDashOffset
     * @return Line
     */
    public function setBorderDashOffset($borderDashOffset)
    {
        $this->borderDashOffset = floatval($borderDashOffset);
        return $this;
    }

    /**
     * @return string
     */
    public function getBorderJoinStyle()
    {
        return $this->borderJoinStyle;
    }

    /**
     * @param string $borderJoinStyle
     * @return Line
     */
    public function setBorderJoinStyle($borderJoinStyle)
    {
        $this->borderJoinStyle = is_null($borderJoinStyle) ? null : strval($borderJoinStyle);
        return $this;
    }

    /**
     * @return bool
     */
    public function isCapBezierPoints()
    {
        return $this->capBezierPoints;
    }

    /**
     * @param bool $capBezierPoints
     * @return Line
     */
    public function setCapBezierPoints($capBezierPoints)
    {
        $this->capBezierPoints = boolval($capBezierPoints);
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getFill()
    {
        return $this->fill;
    }

    /**
     * @param bool|string $fill
     * @return Line
     */
    public function setFill($fill)
    {
        $this->fill = is_null($fill) ? null : (is_bool($fill) ? $fill : strval($fill));
        return $this;
    }

    /**
     * @return bool
     */
    public function isStepped()
    {
        return $this->stepped;
    }

    /**
     * @param bool $stepped
     * @return Line
     */
    public function setStepped($stepped)
    {
        $this->stepped = boolval($stepped);
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
