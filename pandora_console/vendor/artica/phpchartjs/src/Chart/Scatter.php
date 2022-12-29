<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\DataSet\ScatterDataSet;
use Artica\PHPChartJS\Options\ScatterOptions;

/**
 * Class Scatter
 *
 * @package Artica\PHPChartJS\Chart
 *
 * @method ScatterDataSet createDataSet
 * @method ScatterOptions options
 */
class Scatter extends Chart
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'scatter';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => ScatterDataSet::class,
        'options' => ScatterOptions::class,
    ];
}
