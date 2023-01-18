<?php

namespace Artica\PHPChartJS;

/**
 * Class ChartOwned
 *
 * @package Artica\PHPChartJS
 */
trait ChartOwned
{
    /**
     * @var ChartInterface
     */
    private $owner;

    /**
     * @param ChartInterface $chart
     *
     * @return $this
     */
    public function setOwner(ChartInterface $chart)
    {
        $this->owner = $chart;

        return $this;
    }

    /**
     * @return ChartInterface
     */
    public function owner()
    {
        return $this->owner;
    }
}
