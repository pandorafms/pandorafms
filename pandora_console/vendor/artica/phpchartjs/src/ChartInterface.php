<?php

namespace Artica\PHPChartJS;

use Halfpastfour\Collection\CollectionInterface;

/**
 * Interface ChartInterface
 * @package Artica\PHPChartJS
 */
interface ChartInterface
{
    /**
     * @return Options
     */
    public function options();

    /**
     * @return Defaults
     */
    public function defaults();

    /**
     * @return DataSet
     */
    public function createDataSet();

    /**
     * @param DataSet $dataSet
     *
     * @return $this
     */
    public function addDataSet(DataSet $dataSet);

    /**
     * @param $offset
     *
     * @return DataSet
     */
    public function getDataSet($offset);

    /**
     * @return CollectionInterface
     */
    public function dataSets();

    /**
     * @return string
     */
    public function render();
}
