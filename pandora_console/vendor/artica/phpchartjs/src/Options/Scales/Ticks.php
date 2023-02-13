<?php

namespace Artica\PHPChartJS\Options\Scales;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;
use Artica\PHPChartJS\Options\Fonts;

/**
 * Class Ticks
 *
 * @package Artica\PHPChartJS\Options\Scales
 */
class Ticks implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var float
     */
    private $suggestedMin;

    /**
     * @var bool
     */
    private $beginAtZero;

    /**
     * @var float
     */
    private $stepSize;

    /**
     * @var bool
     */
    private $autoSkip;

    /**
     * @var int
     */
    private $autoSkipPadding;

    /**
     * @var Expr
     */
    private $callback;

    /**
     * @var bool
     */
    private $display;

    /**
     * @var Font
     */
    private $font;

    /**
     * @var int
     */
    private $labelOffset;

    /**
     * @var int
     */
    private $maxRotation;

    /**
     * @var int
     */
    private $minRotation;

    /**
     * @var bool
     */
    private $mirror;

    /**
     * @var int
     */
    private $padding;

    /**
     * @var bool
     */
    private $reverse;

    /**
     * @var int
     */
    private $max;

    /**
     * @return float
     */
    public function getSuggestedMin()
    {
        return $this->suggestedMin;
    }

    /**
     * @param float $suggestedMin
     *
     * @return $this
     */
    public function setSuggestedMin($suggestedMin)
    {
        $this->suggestedMin = floatval($suggestedMin);

        return $this;
    }

    /**
     * @return bool
     */
    public function isBeginAtZero()
    {
        return $this->beginAtZero;
    }

    /**
     * @param bool $beginAtZero
     *
     * @return $this
     */
    public function setBeginAtZero($beginAtZero)
    {
        $this->beginAtZero = boolval($beginAtZero);

        return $this;
    }

    /**
     * @return float
     */
    public function getStepSize()
    {
        return $this->stepSize;
    }

    /**
     * @param float $stepSize
     *
     * @return $this
     */
    public function setStepSize($stepSize)
    {
        $this->stepSize = floatval($stepSize);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoSkip()
    {
        return $this->autoSkip;
    }

    /**
     * @param bool $autoSkip
     *
     * @return $this
     */
    public function setAutoSkip($autoSkip)
    {
        $this->autoSkip = boolval($autoSkip);

        return $this;
    }

    /**
     * @return int
     */
    public function getAutoSkipPadding()
    {
        return $this->autoSkipPadding;
    }

    /**
     * @param int $autoSkipPadding
     *
     * @return $this
     */
    public function setAutoSkipPadding($autoSkipPadding)
    {
        $this->autoSkipPadding = intval($autoSkipPadding);

        return $this;
    }

    /**
     * @return Expr
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param string $callback
     *
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = new Expr(strval($callback));

        return $this;
    }

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
        $this->display = boolval($display);

        return $this;
    }

    /**
     * Return Font.
     *
     * @return Font
     */
    public function getFonts()
    {
        if (isset($this->font) === false) {
            $this->font = new Fonts();
        }

        return $this->font;
    }

    /**
     * @return int
     */
    public function getLabelOffset()
    {
        return $this->labelOffset;
    }

    /**
     * @param int $labelOffset
     *
     * @return $this
     */
    public function setLabelOffset($labelOffset)
    {
        $this->labelOffset = intval($labelOffset);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRotation()
    {
        return $this->maxRotation;
    }

    /**
     * @param int $maxRotation
     *
     * @return $this
     */
    public function setMaxRotation($maxRotation)
    {
        $this->maxRotation = intval($maxRotation);

        return $this;
    }

    /**
     * @return int
     */
    public function getMinRotation()
    {
        return $this->minRotation;
    }

    /**
     * @param int $minRotation
     *
     * @return $this
     */
    public function setMinRotation($minRotation)
    {
        $this->minRotation = intval($minRotation);

        return $this;
    }

    /**
     * @return bool
     */
    public function isMirror()
    {
        return $this->mirror;
    }

    /**
     * @param bool $mirror
     *
     * @return $this
     */
    public function setMirror($mirror)
    {
        $this->mirror = boolval($mirror);

        return $this;
    }

    /**
     * @return int
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @param int $padding
     *
     * @return $this
     */
    public function setPadding($padding)
    {
        $this->padding = intval($padding);

        return $this;
    }

    /**
     * @return bool
     */
    public function isReverse()
    {
        return $this->reverse;
    }

    /**
     * @param bool $reverse
     *
     * @return $this
     */
    public function setReverse($reverse)
    {
        $this->reverse = boolval($reverse);

        return $this;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param int $max
     *
     * @return $this
     */
    public function setMax($max)
    {
        $this->max = intval($max);

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
