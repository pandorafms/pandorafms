<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Scales\GridLines;
use Artica\PHPChartJS\Options\Scales\ScaleLabel;
use Artica\PHPChartJS\Options\Scales\Ticks;
use JsonSerializable;

/**
 * Class Scale
 *
 * @package Artica\PHPChartJS\Options
 */
abstract class Scale implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $bounds;

    /**
     * @var bool
     */
    protected $display;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var bool
     */
    protected $stacked;

    /**
     * @var int
     */
    protected $barThickness;

    /**
     * @var string
     */
    protected $position;

    /**
     * @var string
     */
    protected $beforeUpdate;

    /**
     * @var string
     */
    protected $beforeSetDimensions;

    /**
     * @var string
     */
    protected $afterSetDimensions;

    /**
     * @var string
     */
    protected $beforeDataLimits;

    /**
     * @var string
     */
    protected $afterDataLimits;

    /**
     * @var string
     */
    protected $beforeBuildTicks;

    /**
     * @var string
     */
    protected $afterBuildTicks;

    /**
     * @var string
     */
    protected $beforeTickToLabelConversion;

    /**
     * @var string
     */
    protected $afterTickToLabelConversion;

    /**
     * @var string
     */
    protected $beforeCalculateTickRotation;

    /**
     * @var string
     */
    protected $afterCalculateTickRotation;

    /**
     * @var string
     */
    protected $beforeFit;

    /**
     * @var string
     */
    protected $afterFit;

    /**
     * @var string
     */
    protected $afterUpdate;

    /**
     * @var GridLines
     */
    protected $grid;

    /**
     * @var ScaleLabel
     */
    protected $scaleLabel;

    /**
     * @var Ticks
     */
    protected $ticks;

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
     * @return string
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @param string $bounds
     *
     * @return $this
     */
    public function setBounds($bounds)
    {
        $this->bounds = $bounds;

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
        $this->display = $display;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = strval($id);

        return $this;
    }

    /**
     * @return bool
     */
    public function isStacked()
    {
        return $this->stacked;
    }

    /**
     * @param bool $stacked
     *
     * @return $this
     */
    public function setStacked($stacked)
    {
        $this->stacked = ! ! $stacked;

        return $this;
    }

    /**
     * @return int
     */
    public function getBarThickness()
    {
        return $this->barThickness;
    }

    /**
     * @param int $barThickness
     *
     * @return $this
     */
    public function setBarThickness($barThickness)
    {
        $this->barThickness = intval($barThickness);

        return $this;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = strval($position);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeUpdate()
    {
        return $this->beforeUpdate;
    }

    /**
     * @param string $beforeUpdate
     *
     * @return $this
     */
    public function setBeforeUpdate($beforeUpdate)
    {
        $this->beforeUpdate = strval($beforeUpdate);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeSetDimensions()
    {
        return $this->beforeSetDimensions;
    }

    /**
     * @param string $beforeSetDimensions
     *
     * @return $this
     */
    public function setBeforeSetDimensions($beforeSetDimensions)
    {
        $this->beforeSetDimensions = strval($beforeSetDimensions);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterSetDimensions()
    {
        return $this->afterSetDimensions;
    }

    /**
     * @param string $afterSetDimensions
     *
     * @return $this
     */
    public function setAfterSetDimensions($afterSetDimensions)
    {
        $this->afterSetDimensions = strval($afterSetDimensions);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeDataLimits()
    {
        return $this->beforeDataLimits;
    }

    /**
     * @param string $beforeDataLimits
     *
     * @return $this
     */
    public function setBeforeDataLimits($beforeDataLimits)
    {
        $this->beforeDataLimits = strval($beforeDataLimits);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterDataLimits()
    {
        return $this->afterDataLimits;
    }

    /**
     * @param string $afterDataLimits
     *
     * @return $this
     */
    public function setAfterDataLimits($afterDataLimits)
    {
        $this->afterDataLimits = strval($afterDataLimits);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeBuildTicks()
    {
        return $this->beforeBuildTicks;
    }

    /**
     * @param string $beforeBuildTicks
     *
     * @return $this
     */
    public function setBeforeBuildTicks($beforeBuildTicks)
    {
        $this->beforeBuildTicks = strval($beforeBuildTicks);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterBuildTicks()
    {
        return $this->afterBuildTicks;
    }

    /**
     * @param string $afterBuildTicks
     *
     * @return $this
     */
    public function setAfterBuildTicks($afterBuildTicks)
    {
        $this->afterBuildTicks = strval($afterBuildTicks);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeTickToLabelConversion()
    {
        return $this->beforeTickToLabelConversion;
    }

    /**
     * @param string $beforeTickToLabelConversion
     *
     * @return $this
     */
    public function setBeforeTickToLabelConversion($beforeTickToLabelConversion)
    {
        $this->beforeTickToLabelConversion = strval($beforeTickToLabelConversion);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterTickToLabelConversion()
    {
        return $this->afterTickToLabelConversion;
    }

    /**
     * @param string $afterTickToLabelConversion
     *
     * @return $this
     */
    public function setAfterTickToLabelConversion($afterTickToLabelConversion)
    {
        $this->afterTickToLabelConversion = strval($afterTickToLabelConversion);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeCalculateTickRotation()
    {
        return $this->beforeCalculateTickRotation;
    }

    /**
     * @param string $beforeCalculateTickRotation
     *
     * @return $this
     */
    public function setBeforeCalculateTickRotation($beforeCalculateTickRotation)
    {
        $this->beforeCalculateTickRotation = strval($beforeCalculateTickRotation);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterCalculateTickRotation()
    {
        return $this->afterCalculateTickRotation;
    }

    /**
     * @param string $afterCalculateTickRotation
     *
     * @return $this
     */
    public function setAfterCalculateTickRotation($afterCalculateTickRotation)
    {
        $this->afterCalculateTickRotation = strval($afterCalculateTickRotation);

        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeFit()
    {
        return $this->beforeFit;
    }

    /**
     * @param string $beforeFit
     *
     * @return $this
     */
    public function setBeforeFit($beforeFit)
    {
        $this->beforeFit = strval($beforeFit);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterFit()
    {
        return $this->afterFit;
    }

    /**
     * @param string $afterFit
     *
     * @return $this
     */
    public function setAfterFit($afterFit)
    {
        $this->afterFit = strval($afterFit);

        return $this;
    }

    /**
     * @return string
     */
    public function getAfterUpdate()
    {
        return $this->afterUpdate;
    }

    /**
     * @param string $afterUpdate
     *
     * @return $this
     */
    public function setAfterUpdate($afterUpdate)
    {
        $this->afterUpdate = strval($afterUpdate);

        return $this;
    }

    /**
     * @return GridLines
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * @return GridLines
     */
    public function grid()
    {
        if (is_null($this->grid)) {
            $this->grid = new GridLines();
        }

        return $this->grid;
    }

    /**
     * @return ScaleLabel
     */
    public function getScaleLabel()
    {
        return $this->scaleLabel;
    }

    /**
     * @return ScaleLabel
     */
    public function scaleLabel()
    {
        if (is_null($this->scaleLabel)) {
            $this->scaleLabel = new ScaleLabel();
        }

        return $this->scaleLabel;
    }

    /**
     * @return Ticks
     */
    public function getTicks()
    {
        return $this->ticks;
    }

    /**
     * @return Ticks
     */
    public function ticks()
    {
        if (is_null($this->ticks)) {
            $this->ticks = new Ticks();
        }

        return $this->ticks;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
