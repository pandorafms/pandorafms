<?php

namespace Artica\PHPChartJS\DataSet;

use Artica\PHPChartJS\DataSet;

/**
 * Class LineDataSet
 * @package Artica\PHPChartJS\DataSet
 */
class LineDataSet extends DataSet
{
    /**
     * @var bool|string
     */
    protected $fill;

    /**
     * @var string
     */
    protected $cubicInterpolationMode;

    /**
     * @var int
     */
    protected $lineTension;

    /**
     * @var string
     */
    protected $borderCapStyle;

    /**
     * @var array
     */
    protected $borderDash;

    /**
     * @var float
     */
    protected $borderDashOffset;

    /**
     * @var string
     */
    protected $borderJoinStyle;

    /**
     * @var string|string[]
     */
    protected $pointBorderColor;

    /**
     * @var string|string[]
     */
    protected $pointBackgroundColor;

    /**
     * @var int|int[]
     */
    protected $pointBorderWidth;

    /**
     * @var int|int[]
     */
    protected $pointRadius;

    /**
     * @var int|int[]
     */
    protected $pointHoverRadius;

    /**
     * @var int|int[]
     */
    protected $pointHitRadius;

    /**
     * @var string|string[]
     */
    protected $pointHoverBackgroundColor;

    /**
     * @var string|string[]
     */
    protected $pointHoverBorderColor;

    /**
     * @var int|int[]
     */
    protected $pointHoverBorderWidth;

    /**
     * @var mixed
     */
    protected $pointStyle;

    /**
     * @var bool
     */
    protected $showLine;

    /**
     * @var bool
     */
    protected $spanGaps;

    /**
     * @var bool
     */
    protected $steppedLine;

    /**
     * @return bool|string
     */
    public function getFill()
    {
        return $this->fill;
    }

    /**
     * @param bool|string $fill
     *
     * @return $this
     */
    public function setFill($fill)
    {
        $this->fill = $fill;

        return $this;
    }

    /**
     * @return string
     */
    public function getCubicInterpolationMode()
    {
        return $this->cubicInterpolationMode;
    }

    /**
     * @param string $cubicInterpolationMode
     *
     * @return $this
     */
    public function setCubicInterpolationMode($cubicInterpolationMode)
    {
        $this->cubicInterpolationMode = $cubicInterpolationMode;

        return $this;
    }

    /**
     * @return int
     */
    public function getLineTension()
    {
        return $this->lineTension;
    }

    /**
     * @param float $lineTension
     *
     * @return $this
     */
    public function setLineTension($lineTension)
    {
        $this->lineTension = floatval($lineTension);

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
     *
     * @return $this
     */
    public function setBorderCapStyle($borderCapStyle)
    {
        $this->borderCapStyle = $borderCapStyle;

        return $this;
    }

    /**
     * @return array
     */
    public function getBorderDash()
    {
        return $this->borderDash;
    }

    /**
     * @param array $borderDash
     *
     * @return $this
     */
    public function setBorderDash($borderDash)
    {
        $this->borderDash = $borderDash;

        return $this;
    }

    /**
     * @return int
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
     * @return string
     */
    public function getBorderJoinStyle()
    {
        return $this->borderJoinStyle;
    }

    /**
     * @param string $borderJoinStyle
     *
     * @return $this
     */
    public function setBorderJoinStyle($borderJoinStyle)
    {
        $this->borderJoinStyle = $borderJoinStyle;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPointBorderColor()
    {
        return $this->pointBorderColor;
    }

    /**
     * @param string|string[] $pointBorderColor
     *
     * @return $this
     */
    public function setPointBorderColor($pointBorderColor)
    {
        $this->pointBorderColor = $pointBorderColor;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPointBackgroundColor()
    {
        return $this->pointBackgroundColor;
    }

    /**
     * @param string|string[] $pointBackgroundColor
     *
     * @return $this
     */
    public function setPointBackgroundColor($pointBackgroundColor)
    {
        $this->pointBackgroundColor = $pointBackgroundColor;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getPointBorderWidth()
    {
        return $this->pointBorderWidth;
    }

    /**
     * @param int|int[] $pointBorderWidth
     *
     * @return $this
     */
    public function setPointBorderWidth($pointBorderWidth)
    {
        $this->pointBorderWidth = $pointBorderWidth;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getPointRadius()
    {
        return $this->pointRadius;
    }

    /**
     * @param int|int[] $pointRadius
     *
     * @return $this
     */
    public function setPointRadius($pointRadius)
    {
        $this->pointRadius = $pointRadius;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getPointHoverRadius()
    {
        return $this->pointHoverRadius;
    }

    /**
     * @param int|int[] $pointHoverRadius
     *
     * @return $this
     */
    public function setPointHoverRadius($pointHoverRadius)
    {
        $this->pointHoverRadius = $pointHoverRadius;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getPointHitRadius()
    {
        return $this->pointHitRadius;
    }

    /**
     * @param int|int[] $pointHitRadius
     *
     * @return $this
     */
    public function setPointHitRadius($pointHitRadius)
    {
        $this->pointHitRadius = $pointHitRadius;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPointHoverBackgroundColor()
    {
        return $this->pointHoverBackgroundColor;
    }

    /**
     * @param string|string[] $pointHoverBackgroundColor
     *
     * @return $this
     */
    public function setPointHoverBackgroundColor($pointHoverBackgroundColor)
    {
        $this->pointHoverBackgroundColor = $pointHoverBackgroundColor;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPointHoverBorderColor()
    {
        return $this->pointHoverBorderColor;
    }

    /**
     * @param string|string[] $pointHoverBorderColor
     *
     * @return $this
     */
    public function setPointHoverBorderColor($pointHoverBorderColor)
    {
        $this->pointHoverBorderColor = $pointHoverBorderColor;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getPointHoverBorderWidth()
    {
        return $this->pointHoverBorderWidth;
    }

    /**
     * @param int|int[] $pointHoverBorderWidth
     *
     * @return $this
     */
    public function setPointHoverBorderWidth($pointHoverBorderWidth)
    {
        $this->pointHoverBorderWidth = $pointHoverBorderWidth;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPointStyle()
    {
        return $this->pointStyle;
    }

    /**
     * @param mixed $pointStyle
     *
     * @return $this
     */
    public function setPointStyle($pointStyle)
    {
        $this->pointStyle = $pointStyle;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowLine()
    {
        return $this->showLine;
    }

    /**
     * @param bool $showLine
     *
     * @return $this
     */
    public function setShowLine($showLine)
    {
        $this->showLine = $showLine;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSpanGaps()
    {
        return $this->spanGaps;
    }

    /**
     * @param bool $spanGaps
     *
     * @return $this
     */
    public function setSpanGaps($spanGaps)
    {
        $this->spanGaps = $spanGaps;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSteppedLine()
    {
        return $this->steppedLine;
    }

    /**
     * @param bool $steppedLine
     *
     * @return $this
     */
    public function setSteppedLine($steppedLine)
    {
        $this->steppedLine = $steppedLine;

        return $this;
    }
}
