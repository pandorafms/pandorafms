<?php

namespace Artica\PHPChartJS\DataSet;

use Artica\PHPChartJS\DataSet;

/**
 * Class RadarDataSet
 * @package Artica\PHPChartJS\DataSet
 */
class RadarDataSet extends DataSet
{
    /**
     * @var bool
     */
    protected $fill;

    /**
     * @var float
     */
    protected $lineTension;

    /**
     * @var string
     */
    protected $borderCapStyle;

    /**
     * @var int[]
     */
    protected $borderDash;

    /**
     * @var int
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
    protected $hitRadius;

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
     * @var string|string[]
     */
    protected $pointStyle;

    /**
     * @return bool
     */
    public function isFill()
    {
        return $this->fill;
    }

    /**
     * @param bool $fill
     *
     * @return $this
     */
    public function setFill($fill)
    {
        $this->fill = $fill;

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
        $this->lineTension = $lineTension;

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
     * @return int[]
     */
    public function getBorderDash()
    {
        return $this->borderDash;
    }

    /**
     * @param int[] $borderDash
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
     * @param int $borderDashOffset
     *
     * @return $this
     */
    public function setBorderDashOffset($borderDashOffset)
    {
        $this->borderDashOffset = $borderDashOffset;

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
    public function getHitRadius()
    {
        return $this->hitRadius;
    }

    /**
     * @param int|int[] $hitRadius
     *
     * @return $this
     */
    public function setHitRadius($hitRadius)
    {
        $this->hitRadius = $hitRadius;

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
     * @return string|string[]
     */
    public function getPointStyle()
    {
        return $this->pointStyle;
    }

    /**
     * @param string|string[] $pointStyle
     *
     * @return $this
     */
    public function setPointStyle($pointStyle)
    {
        $this->pointStyle = $pointStyle;

        return $this;
    }
}
