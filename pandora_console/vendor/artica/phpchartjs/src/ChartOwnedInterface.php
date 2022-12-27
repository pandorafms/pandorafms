<?php

namespace Artica\PHPChartJS;

/**
 * Class DataSet
 * @package Artica\PHPChartJS
 */
interface ChartOwnedInterface
{
    /**
     * @param ChartInterface $chart
     *
     * @return $this
     */
    public function setOwner(ChartInterface $chart);

    /**
     * @return ChartInterface
     */
    public function owner();
}
