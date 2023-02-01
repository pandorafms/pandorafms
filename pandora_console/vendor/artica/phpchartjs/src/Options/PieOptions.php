<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\Options;
use Artica\PHPChartJS\Options\Animation\PieAnimation;

/**
 * Class PieOptions
 * @package Artica\PHPChartJS\Options
 */
class PieOptions extends Options
{
    /**
     * @var int
     */
    protected $cutoutPercentage;

    /**
     * @var float
     */
    protected $rotation;

    /**
     * @var float
     */
    protected $circumference;

    /**
     * @return Animation
     */
    public function getAnimation()
    {
        if (is_null($this->animation)) {
            $this->animation    = new PieAnimation();
        }

        return $this->animation;
    }

    /**
     * @return int
     */
    public function getCutoutPercentage()
    {
        return $this->cutoutPercentage;
    }

    /**
     * @param int $cutoutPercentage
     *
     * @return $this
     */
    public function setCutoutPercentage($cutoutPercentage)
    {
        $this->cutoutPercentage = $cutoutPercentage;

        return $this;
    }

    /**
     * @return float
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param float $rotation
     *
     * @return $this
     */
    public function setRotation($rotation)
    {
        $this->rotation = $rotation;

        return $this;
    }

    /**
     * @return float
     */
    public function getCircumference()
    {
        return $this->circumference;
    }

    /**
     * @param float $circumference
     *
     * @return $this
     */
    public function setCircumference($circumference)
    {
        $this->circumference = $circumference;

        return $this;
    }
}
