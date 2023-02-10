<?php

namespace Test\Chart;

use Artica\PHPChartJS\Chart\Bar;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\BarDataSet;
use Artica\PHPChartJS\Options\BarOptions;

/**
 * Class BarTest
 * @package Test\Chart
 */
class BarTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the factory for creating a Bar chart
     */
    public function testBar()
    {
        $bar = new Bar();

        // Check if correct class is returned.
        $this->assertInstanceOf(ChartInterface::class, $bar, 'The correct interface has been implemented');
        $this->assertInstanceOf(Bar::class, $bar, 'The correct class has been created');
    }

    /**
     * Test the DataSet created by the Bar chart
     */
    public function testDataSet()
    {
        $bar       = new Bar();
        $chartData = [ 0, 1, 4, 2, 3, 0, 5, 2, 6 ];

        // DataSet
        $dataSet = $bar->createDataSet();
        $this->assertInstanceOf(BarDataSet::class, $dataSet, 'The correct class has been created by the chart');

        // Populate the collection
        $dataSet->data()->exchangeArray($chartData);

        // Check if data is still correct.
        $bar->addDataSet($dataSet);
        $this->assertEquals($chartData, $bar->dataSets()->offsetGet(0)->data()->getArrayCopy());
    }

    /**
     *
     */
    public function testOptions()
    {
        $bar = new Bar();
        $this->assertInstanceOf(BarOptions::class, $bar->options(), 'The correct class should be created');
    }
}
