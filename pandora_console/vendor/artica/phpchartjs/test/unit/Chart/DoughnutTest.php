<?php

namespace Test\Chart;

use Artica\PHPChartJS\Chart\Doughnut;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\DataSet\PieDataSet;
use Artica\PHPChartJS\Options\PieOptions;

/**
 * Class DoughnutTest
 * @package Test\Chart
 */
class DoughnutTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the factory for creating a Doughnut chart
     */
    public function testDoughnut()
    {
        $doughnut = new Doughnut();

        // Check if correct class is returned.
        $this->assertInstanceOf(ChartInterface::class, $doughnut, 'The correct interface has been implemented');
        $this->assertInstanceOf(Doughnut::class, $doughnut, 'The correct class has been created');
    }

    /**
     * Test the DataSet created by the Doughnut chart
     */
    public function testDataSet()
    {
        $Doughnut       = new Doughnut();
        $chartData = [ 0, 1, 4, 2, 3, 0, 5, 2, 6 ];

        // DataSet
        $dataSet = $Doughnut->createDataSet();
        $this->assertInstanceOf(PieDataSet::class, $dataSet, 'The correct class has been created by the chart');

        // Populate the collection
        $dataSet->data()->exchangeArray($chartData);

        // Check if data is still correct.
        $Doughnut->addDataSet($dataSet);
        $this->assertEquals($chartData, $Doughnut->dataSets()->offsetGet(0)->data()->getArrayCopy());
    }

    /**
     *
     */
    public function testOptions()
    {
        $Doughnut = new Doughnut();
        $this->assertInstanceOf(PieOptions::class, $Doughnut->options(), 'The correct class should be created');
    }
}
