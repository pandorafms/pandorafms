<?php

namespace Artica\PHPChartJS\DataSet;

use Artica\PHPChartJS\DataSet;

/**
 * Class BubbleDataSet
 *
 * @package Artica\PHPChartJS\DataSet
 */
class BubbleDataSet extends DataSet
{
    /**
     * @var string
     */
    protected $pointStyle;

    /**
     * @var int
     */
    protected $radius;

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
     * @return \Artica\PHPChartJS\DataSet\BubbleDataSet
     */
    public function setPointStyle($pointStyle)
    {
        $this->pointStyle = $pointStyle;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     *
     * @return \Artica\PHPChartJS\DataSet\BubbleDataSet
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;

        return $this;
    }
}
