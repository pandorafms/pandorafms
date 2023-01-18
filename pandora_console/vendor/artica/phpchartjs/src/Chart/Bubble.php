<?php

namespace Artica\PHPChartJS\Chart;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\DataSet\BubbleDataSet;
use Artica\PHPChartJS\Options\BubbleOptions;

/**
 * Class Bubble
 * @package Artica\PHPChartJS\Chart
 *
 * @method BubbleDataSet createDataSet
 * @method BubbleOptions options
 */
class Bubble extends Chart
{
    /**
     * The internal type of chart.
     */
    const TYPE = 'bubble';

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => BubbleDataSet::class,
        'options' => BubbleOptions::class,
    ];
}
