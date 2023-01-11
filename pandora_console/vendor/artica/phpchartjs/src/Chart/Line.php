<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\DataSet\LineDataSet;
use Artica\PHPChartJS\Options\LineOptions;

/**
 * Class Line
 * @package Artica\PHPChartJS\Chart
 * @method LineDataSet createDataSet()
 */
class Line extends Chart
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'line';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => LineDataSet::class,
        'options' => LineOptions::class,
    ];
}
