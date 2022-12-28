<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\DataSet\BarDataSet;
use Artica\PHPChartJS\Options\BarOptions;

/**
 * Class Bar
 * @package Artica\PHPChartJS\Chart
 *
 * @method BarDataSet createDataSet
 * @method BarOptions options
 */
class Bar extends Chart
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'bar';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => BarDataSet::class,
        'options' => BarOptions::class,
    ];
}
