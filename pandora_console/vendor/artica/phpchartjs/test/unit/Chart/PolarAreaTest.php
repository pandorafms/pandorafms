<?php

namespace Test\Chart;

use Artica\PHPChartJS\Chart\PolarArea;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\PolarAreaDataSet;
use Artica\PHPChartJS\Options\PolarAreaOptions;

/**
 * Class PolarAreaTest
 * @package Test\Chart
 */
class PolarAreaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the factory for creating a PolarArea chart
     */
    public function testPolarArea()
    {
        $PolarArea = new PolarArea();

        // Check if correct class is returned.
        $this->assertInstanceOf(ChartInterface::class, $PolarArea, 'The correct interface has been implemented');
        $this->assertInstanceOf(PolarArea::class, $PolarArea, 'The correct class has been created');
    }

    /**
     * Test the DataSet created by the PolarArea chart
     */
    public function testDataSet()
    {
        $PolarArea = new PolarArea();
        $chartData = [ 0, 1, 4, 2, 3, 0, 5, 2, 6 ];

        // DataSet
        $dataSet = $PolarArea->createDataSet();
        $this->assertInstanceOf(PolarAreaDataSet::class, $dataSet, 'The correct class has been created by the chart');

        // Populate the collection
        $dataSet->data()->exchangeArray($chartData);

        // Check if data is still correct.
        $PolarArea->addDataSet($dataSet);
        $this->assertEquals($chartData, $PolarArea->dataSets()->offsetGet(0)->data()->getArrayCopy());
    }

    /**
     *
     */
    public function testOptions()
    {
        $PolarArea = new PolarArea();
        $this->assertInstanceOf(
            PolarAreaOptions::class,
            $PolarArea->options(),
            'The correct class should be created'
        );
    }
}
