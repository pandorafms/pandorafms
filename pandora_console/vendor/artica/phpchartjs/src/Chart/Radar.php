<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\RadarDataSet;
use Artica\PHPChartJS\Options\RadarOptions;

/**
 * Class Radar
 * @package Artica\PHPChartJS\Chart
 *
 * @method RadarDataSet createDataSet
 * @method RadarOptions options
 */
class Radar extends Chart implements ChartInterface
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'radar';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => RadarDataSet::class,
        'options' => RadarOptions::class,
    ];
}
