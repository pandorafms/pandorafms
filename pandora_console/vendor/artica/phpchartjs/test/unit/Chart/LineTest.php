<?php

namespace Test\Chart;

use Artica\PHPChartJS\Chart\Line;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\LineDataSet;
use Artica\PHPChartJS\Options\LineOptions;

/**
 * Class LineTest
 * @package Test\Chart
 */
class LineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the factory for creating a Line chart
     */
    public function testLine()
    {
        $Line = new Line();

        // Check if correct class is returned.
        $this->assertInstanceOf(ChartInterface::class, $Line, 'The correct interface has been implemented');
        $this->assertInstanceOf(Line::class, $Line, 'The correct class has been created');
    }

    /**
     * Test the DataSet created by the Line chart
     */
    public function testDataSet()
    {
        $Line       = new Line();
        $chartData = [ 0, 1, 4, 2, 3, 0, 5, 2, 6 ];

        // DataSet
        $dataSet = $Line->createDataSet();
        $this->assertInstanceOf(LineDataSet::class, $dataSet, 'The correct class has been created by the chart');

        // Populate the collection
        $dataSet->data()->exchangeArray($chartData);

        // Check if data is still correct.
        $Line->addDataSet($dataSet);
        $this->assertEquals($chartData, $Line->dataSets()->offsetGet(0)->data()->getArrayCopy());
    }

    /**
     *
     */
    public function testOptions()
    {
        $Line = new Line();
        $this->assertInstanceOf(LineOptions::class, $Line->options(), 'The correct class should be created');
    }
}
