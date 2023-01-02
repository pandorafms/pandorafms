<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\PolarAreaDataSet;
use Artica\PHPChartJS\Options\PolarAreaOptions;

/**
 * Class PolarArea
 * @package Artica\PHPChartJS\Chart
 *
 * @method PolarAreaDataSet createDataSet
 * @method PolarAreaOptions options
 */
class PolarArea extends Chart implements ChartInterface
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'polarArea';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => PolarAreaDataSet::class,
        'options' => PolarAreaOptions::class,
    ];
}
