<?php

namespace Test;

use DOMDocument;
use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\Chart\Bar;
use Artica\PHPChartJS\DataSet;
use Artica\PHPChartJS\DataSet\BarDataSet;
use Artica\PHPChartJS\DataSetCollection;
use Artica\PHPChartJS\LabelsCollection;
use Artica\PHPChartJS\Options\BarOptions;
use \PHPUnit\Framework\TestCase;

/**
 * Class ChartTest
 *
 * @package Artica\PHPChartJS
 */
class ChartTest extends TestCase
{

    /**
     * @var Chart
     */
    private $chart;

    /**
     * @var array
     */
    private $data_types = [
        'height'   => 'height',
    // int
        'width'    => 'width',
    // int
        'title'    => 'title',
    // string
        'labels'   => 'labels',
    // LabelsCollection
        'options'  => 'options',
    // Options
        'dataSets' => 'dataSets',
    // DataSetCollection
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'height' => null,
    // int
        'width'  => null,
    // int
        'title'  => null,
    // string
    ];


    /**
     *
     */
    public function setUp(): void
    {
        $this->chart = new Bar();
    }


    /**
     *
     */
    public function testGetId()
    {
        $result = $this->chart->getId();
        self::assertNotNull($result);
    }


    /**
     *
     */
    public function testSetId()
    {
        $expected = '1203';
        $this->chart->setId($expected);
        self::assertSame($expected, $this->chart->getId());
    }


    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->chart, $this->data_types);
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testHeight()
    {
        $expected = 15;
        $this->chart->setHeight($expected);
        $result = $this->chart->getHeight();
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testWidth()
    {
        $expected = 17;
        $this->chart->setWidth($expected);
        $result = $this->chart->getWidth();
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testTitle()
    {
        $expected = 'no title';
        $this->chart->setTitle($expected);
        $result = $this->chart->getTitle();
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testLabels()
    {
        $result = $this->chart->labels();
        self::assertInstanceOf(LabelsCollection::class, $result);
    }


    /**
     *
     */
    public function testLabelsAdd()
    {
        $labels = new LabelsCollection();
        $label  = 'no label at all';
        $labels->append($label);
        $result = $this->chart->addLabel($label)->labels();
        self::assertNotSame($labels, $result);
        self::assertEquals($labels, $result);
    }


    /**
     *
     */
    public function testGetLabelValid()
    {
        $labels = new LabelsCollection();
        $label  = 'no label at all';
        $labels->append($label);
        $this->chart->addLabel($label)->labels();
        $result = $this->chart->getLabel(0);
        self::assertSame($label, $result);
        $result1 = $this->chart->getLabel(1);
        self::assertNull($result1);
    }


    /**
     *
     */
    public function testGetLabelInValid()
    {
        $labels = new LabelsCollection();
        $label  = 'only 1 label';
        $labels->append($label);
        $this->chart->addLabel($label)->labels();
        $result1 = $this->chart->getLabel(1);
        self::assertNull($result1);
    }


    /**
     *
     */
    public function testDataSets()
    {
        $expected = new DataSetCollection();
        $result   = $this->chart->dataSets();
        self::assertNotSame($expected, $result);
        self::assertEquals($expected, $result);
    }


    /**
     *
     */
    public function testAddDataSet()
    {
        $expected = new DataSetCollection();
        $dataSet1 = new DataSet();
        $expected->append($dataSet1);
        $dataSet1->setOwner($this->chart);
        $dataSet2 = new DataSet();
        $this->chart->addDataSet($dataSet2);
        self::assertEquals($dataSet1, $dataSet2);
    }


    /**
     *
     */
    public function testGetDataSetEmpty()
    {
        $result = $this->chart->getDataSet(0);
        self::assertNull($result);
    }


    /**
     *
     */
    public function testGetDataSet()
    {
        $dataSet = new DataSet();
        $this->chart->addDataSet($dataSet);
        $result = $this->chart->getDataSet(0);
        self::assertSame($dataSet, $result);
    }


    /**
     *
     */
    public function testRenderCanvas()
    {
        $chartHtml = '<div>'.$this->chart->render().'</div>';
        $htmlDoc   = new DOMDocument();
        $htmlDoc->loadXML($chartHtml);
        $canvas = $htmlDoc->getElementsByTagName('canvas')->item(0);
        $result = $canvas->getAttribute('id');
        self::assertStringStartsWith('chart', $result);
    }


    /**
     *
     */
    public function testRenderHeight()
    {
        $expected = '500';
        $this->chart->setHeight($expected);
        $chartHtml = '<div>'.$this->chart->render(true).'</div>';
        $htmlDoc   = new DOMDocument();
        $htmlDoc->loadXML($chartHtml);
        $canvas = $htmlDoc->getElementsByTagName('canvas')->item(0);
        $result = $canvas->getAttribute('height');
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testRenderWidth()
    {
        $expected = '500';
        $this->chart->setWidth($expected);
        $chartHtml = '<div>'.$this->chart->render(true).'</div>';
        $htmlDoc   = new DOMDocument();
        $htmlDoc->loadXML($chartHtml);
        $canvas = $htmlDoc->getElementsByTagName('canvas')->item(0);
        $result = $canvas->getAttribute('width');
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testRenderScript()
    {
        $chartHtml = '<div>'.$this->chart->render(true).'</div>';
        $htmlDoc   = new DOMDocument();
        $htmlDoc->loadXML($chartHtml);
        $script = $htmlDoc->getElementsByTagName('script')->item(0);
        self::assertNotEmpty($script->nodeValue);
    }


    /**
     *
     */
    public function testCreateDataSet()
    {
        $result = $this->chart->createDataSet();
        self::assertInstanceOf(BarDataSet::class, $result);
    }


    /**
     *
     */
    public function testOptions()
    {
        $result = $this->chart->options();
        self::assertInstanceOf(BarOptions::class, $result);
    }


}
