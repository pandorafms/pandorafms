<?php

namespace Test\Chart;

use Artica\PHPChartJS\Chart\Scatter;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\ScatterDataSet;
use Artica\PHPChartJS\Options\ScatterOptions;

/**
 * Class ScatterTest
 *
 * @package Test\Chart
 */
class ScatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the factory for creating a Scatter chart
     */
    public function testScatter()
    {
        $scatter = new Scatter();

        // Check if correct class is returned.
        $this->assertInstanceOf(ChartInterface::class, $scatter, 'The correct interface has been implemented');
        $this->assertInstanceOf(Scatter::class, $scatter, 'The correct class has been created');
    }

    /**
     * Test the DataSet created by the Scatter chart
     */
    public function testDataSet()
    {
        $scatter   = new Scatter();
        $chartData = [0, 1, 4, 2, 3, 0, 5, 2, 6];

        // DataSet
        $dataSet = $scatter->createDataSet();
        $this->assertInstanceOf(ScatterDataSet::class, $dataSet, 'The correct class has been created by the chart');

        // Populate the collection
        $dataSet->data()->exchangeArray($chartData);

        // Check if data is still correct.
        $scatter->addDataSet($dataSet);
        $this->assertEquals($chartData, $scatter->dataSets()->offsetGet(0)->data()->getArrayCopy());
    }

    /**
     *
     */
    public function testOptions()
    {
        $scatter = new Scatter();
        $this->assertInstanceOf(ScatterOptions::class, $scatter->options(), 'The correct class should be created');
    }
}
