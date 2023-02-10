<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Collection\Data;
use Artica\PHPChartJS\Delegate;
use JsonSerializable as JsonSerializableInterface;

/**
 * Class DataSet
 *
 * @package Artica\PHPChartJS
 */
class DataSet implements ChartOwnedInterface, ArraySerializableInterface, JsonSerializableInterface
{
    use ChartOwned;
    use Delegate\ArraySerializable;
    use Delegate\JsonSerializable;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $xAxisID;

    /**
     * @var string
     */
    protected $yAxisID;

    /**
     * @var string|string[]
     */
    protected $backgroundColor;

    /**
     * @var string|string[]
     */
    protected $borderColor;

    /**
     * @var int|int[]
     */
    protected $borderWidth;

    /**
     * @var string
     */
    protected $borderSkipped;

    /**
     * @var string|string[]
     */
    protected $hoverBackgroundColor;

    /**
     * @var string|string[]
     */
    protected $hoverBorderColor;

    /**
     * @var int|int[]
     */
    protected $hoverBorderWidth;

    /**
     * @var bool|null
     */
    protected $hidden;

    /**
     * @var string|null
     */
    protected $axis;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Data
     */
    public function data()
    {
        if (is_null($this->data)) {
            $this->data = new Data();
        }

        return $this->data;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = strval($label);

        return $this;
    }

    /**
     * @return string
     */
    public function getAxis()
    {
        return $this->axis;
    }

    /**
     * @param string $axis
     *
     * @return $this
     */
    public function setAxis($axis)
    {
        $this->axis = $axis;

        return $this;
    }

    /**
     * @return string
     */
    public function getXAxisID()
    {
        return $this->xAxisID;
    }

    /**
     * @param string $xAxisID
     *
     * @return $this
     */
    public function setXAxisID($xAxisID)
    {
        $this->xAxisID = strval($xAxisID);

        return $this;
    }

    /**
     * @return string
     */
    public function getYAxisID()
    {
        return $this->yAxisID;
    }

    /**
     * @param string $yAxisID
     *
     * @return $this
     */
    public function setYAxisID($yAxisID)
    {
        $this->yAxisID = strval($yAxisID);

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param string|string[] $backgroundColor
     *
     * @return $this
     */
    public function setBackgroundColor($backgroundColor)
    {
        if (is_array($backgroundColor)) {
            $backgroundColor = array_map('strval', $backgroundColor);
        }
        if (! is_array($backgroundColor)) {
            $backgroundColor = strval($backgroundColor);
        }

        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * @param string|string[] $borderColor
     *
     * @return $this
     */
    public function setBorderColor($borderColor)
    {
        if (is_array($borderColor)) {
            $borderColor = array_map('strval', $borderColor);
        }
        if (! is_array($borderColor)) {
            $borderColor = strval($borderColor);
        }

        $this->borderColor = $borderColor;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getBorderWidth()
    {
        return $this->borderWidth;
    }

    /**
     * @param int|int[] $borderWidth
     *
     * @return $this
     */
    public function setBorderWidth($borderWidth)
    {
        if (is_array($borderWidth)) {
            $borderWidth = array_map('intval', $borderWidth);
        }
        if (! is_array($borderWidth)) {
            $borderWidth = intval($borderWidth);
        }

        $this->borderWidth = $borderWidth;

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
     * @return $this
     */
    public function setBorderSkipped($borderSkipped)
    {
        $this->borderSkipped = strval($borderSkipped);

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getHoverBackgroundColor()
    {
        return $this->hoverBackgroundColor;
    }

    /**
     * @param string|string[] $hoverBackgroundColor
     *
     * @return $this
     */
    public function setHoverBackgroundColor($hoverBackgroundColor)
    {
        if (is_array($hoverBackgroundColor)) {
            $hoverBackgroundColor = array_map('strval', $hoverBackgroundColor);
        }
        if (! is_array($hoverBackgroundColor)) {
            $hoverBackgroundColor = strval($hoverBackgroundColor);
        }

        $this->hoverBackgroundColor = $hoverBackgroundColor;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getHoverBorderColor()
    {
        return $this->hoverBorderColor;
    }

    /**
     * @param string|string[] $hoverBorderColor
     *
     * @return $this
     */
    public function setHoverBorderColor($hoverBorderColor)
    {
        if (is_array($hoverBorderColor)) {
            $hoverBorderColor = array_map('strval', $hoverBorderColor);
        }
        if (! is_array($hoverBorderColor)) {
            $hoverBorderColor = strval($hoverBorderColor);
        }

        $this->hoverBorderColor = $hoverBorderColor;

        return $this;
    }

    /**
     * @return int|int[]
     */
    public function getHoverBorderWidth()
    {
        return $this->hoverBorderWidth;
    }

    /**
     * @param int|int[] $hoverBorderWidth
     *
     * @return $this
     */
    public function setHoverBorderWidth($hoverBorderWidth)
    {
        if (is_array($hoverBorderWidth)) {
            $hoverBorderWidth = array_map('intval', $hoverBorderWidth);
        }
        if (! is_array($hoverBorderWidth)) {
            $hoverBorderWidth = intval($hoverBorderWidth);
        }

        $this->hoverBorderWidth = $hoverBorderWidth;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isHidden()
    {
        if (is_null($this->hidden)) {
            $this->hidden = false;
        }

        return $this->hidden;
    }

    /**
     * @param bool|null $hidden
     *
     * @return $this
     */
    public function setHidden($hidden)
    {
        $this->hidden = is_null($hidden) ? null : boolval($hidden);

        return $this;
    }
}
