<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\PieDataSet;
use Artica\PHPChartJS\Options\PieOptions;

/**
 * Class Pie
 * @package Artica\PHPChartJS\Chart
 *
 * @method PieDataSet createDataSet
 * @method PieOptions options
 */
class Pie extends Chart implements ChartInterface
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'pie';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => PieDataSet::class,
        'options' => PieOptions::class,
    ];
}
