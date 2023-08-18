<?php

namespace Artica\PHPChartJS\Options\Scales;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Delegate\NumberUtils;
use Artica\PHPChartJS\Delegate\StringUtils;
use JsonSerializable;

/**
 * Class AngleLines
 *
 * @package Artica\PHPChartJS\Options\Scales
 */
class AngleLines implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;
    use StringUtils;
    use NumberUtils;

    /**
     * @var bool
     */
    private $display;

    /**
     * @var string|string[]
     */
    private $color;

    /**
     * @var float[]|null
     */
    private $borderDash;

    /**
     * @var float
     */
    private $borderDashOffset;

    /**
     * @return bool
     */
    public function isDisplay()
    {
        return $this->display;
    }

    /**
     * @param bool $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string|string[] $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        if (is_array($color)) {
            $this->color = $this->recursiveToString($color);
        } else {
            $this->color = is_null($color) ? null : strval($color);
        }

        return $this;
    }

    /**
     * @return float[]|null
     */
    public function getBorderDash()
    {
        return $this->borderDash;
    }

    /**
     * @param float[] $borderDash
     *
     * @return $this
     */
    public function setBorderDash($borderDash)
    {
        if (is_array($borderDash)) {
            $this->borderDash = $this->recursiveToFloat($borderDash);
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
     *
     * @return $this
     */
    public function setBorderDashOffset($borderDashOffset)
    {
        $this->borderDashOffset = floatval($borderDashOffset);

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
