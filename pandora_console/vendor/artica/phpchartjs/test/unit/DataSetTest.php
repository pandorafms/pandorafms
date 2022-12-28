<?php

namespace Test;

use Halfpastfour\Collection\Collection\ArrayAccess;
use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Chart\Bar;
use Artica\PHPChartJS\ChartInterface;
use Artica\PHPChartJS\ChartOwnedInterface;
use Artica\PHPChartJS\Collection\Data;
use Artica\PHPChartJS\DataSet;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

/**
 * Class DataSetTest
 *
 * @package Test
 */
class DataSetTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     */
    public function testImplementation()
    {
        $dataSet = new DataSet();
        $this->assertInstanceOf(ChartOwnedInterface::class, $dataSet, 'Class implements ChartOwnedInterface');
        $this->assertInstanceOf(
            ArraySerializableInterface::class,
            $dataSet,
            'Class implements ArraySerializableInterface'
        );
        $this->assertInstanceOf(JsonSerializable::class, $dataSet, 'Class implements JsonSerializable');
    }

    /**
     *
     */
    public function testOwner()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->owner(), 'The dataset has no owner');

        $chart = new Bar();
        $chart->addDataSet($dataSet);

        $this->assertEquals($chart, $dataSet->owner(), 'The owner of the dataSet is set and returned correctly');
        $this->assertInstanceOf(
            ChartInterface::class,
            $dataSet->owner(),
            'The owner of the dataSet implements the correct interface'
        );
    }

    /**
     *
     */
    public function testType()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getType(), 'The type is not set');

        $dataSet->setType(Bar::TYPE);
        $this->assertEquals(Bar::TYPE, $dataSet->getType(), 'The type is set and returned correctly');
    }

    /**
     *
     */
    public function testData()
    {
        $dataSet = new DataSet();

        $dataCollection = $dataSet->data();
        $this->assertInstanceOf(Data::class, $dataCollection, 'The data collection is the right class');
        $this->assertInstanceOf(ArrayAccess::class, $dataCollection, 'The data collection extends Collection');
        $this->assertInstanceOf(
            JsonSerializable::class,
            $dataCollection,
            'The data collection implements JsonSerializable'
        );
    }

    /**
     *
     */
    public function testLabel()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getLabel(), 'The label should not be set');

        $dataSet->setLabel('Foo');
        $this->assertEquals('Foo', $dataSet->getLabel(), 'The label should have been set correctly');
    }

    /**
     *
     */
    public function testBackgroundColor()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getBackgroundColor(), 'The background color is not set');

        $dataSet->setBackgroundColor('#fff');
        $this->assertEquals('#fff', $dataSet->getBackgroundColor());

        $backgroundColorArray = ['#fff', 'rgb( 255, 255, 255 )', 'rgba( 255, 255, 255, .5 )', 'white'];
        $dataSet->setBackgroundColor($backgroundColorArray);
        $this->assertEquals(
            $backgroundColorArray,
            $dataSet->getBackgroundColor(),
            'The background color is set again and returned correctly'
        );
    }

    /**
     *
     */
    public function testBorderColor()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getBorderColor(), 'The border color is not set');

        $dataSet->setBorderColor('#fff');
        $this->assertEquals('#fff', $dataSet->getBorderColor(), 'The border color is set and returned correctly');

        $borderColorArray = ['#fff', 'rgb( 255, 255, 255 )', 'rgba( 255, 255, 255, .5 )', 'white'];
        $dataSet->setBorderColor($borderColorArray);
        $this->assertEquals(
            $borderColorArray,
            $dataSet->getBorderColor(),
            'The border color is set again and returned correctly'
        );
    }

    /**
     * Test setting and getting the border width.
     */
    public function testBorderWidth()
    {
        $dataSet = new DataSet();
        $this->assertNull($dataSet->getBorderWidth(), 'The border width is not set');

        $this->assertEquals(
            $dataSet,
            $dataSet->setBorderWidth(10),
            'Setting the border width should return the DataSet instance'
        );
        $this->assertTrue(is_int($dataSet->getBorderWidth()), 'Return type should be int');
        $this->assertEquals(10, $dataSet->getBorderWidth(), 'The border width should equal int 10');

        $dataSet->setBorderWidth('20');
        $this->assertTrue(is_int($dataSet->getBorderWidth()), 'Return type should be int');
        $this->assertEquals(20, $dataSet->getBorderWidth(), 'The border width should equal int 20');

        $dataSet->setBorderWidth('30abc');
        $this->assertTrue(is_int($dataSet->getBorderWidth()), 'Return type should be int');
        $this->assertEquals(30, $dataSet->getBorderWidth(), 'The border width should equal int 30');

        $dataSet->setBorderWidth(40.00);
        $this->assertTrue(is_int($dataSet->getBorderWidth()), 'Return type should be int');
        $this->assertEquals(40, $dataSet->getBorderWidth(), 'The border width should equal int 40');

        $dataSet->setBorderWidth('abc50');
        $this->assertTrue(is_int($dataSet->getBorderWidth()), 'Return type should be int');
        $this->assertEquals(0, $dataSet->getBorderWidth(), 'The border width should equal int 0');

        $dataSet->setBorderWidth([10, '20', '30abc', 40.00, 'abc50']);
        $this->assertTrue(is_array($dataSet->getBorderWidth()), 'Return type should be array');
        $this->assertEquals([10, 20, 30, 40, 0], $dataSet->getBorderWidth(), 'Return value should be array of int');
    }

    /**
     *
     */
    public function testBorderSkipped()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getBorderSkipped(), 'The border skipped value is not set');

        $this->assertInstanceOf(
            DataSet::class,
            $dataSet->setBorderSkipped('bottom'),
            'The correct class is returned'
        );
        $this->assertEquals('bottom', $dataSet->getBorderSkipped(), 'The correct value is returned');
    }

    /**
     *
     */
    public function testAxes()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getXAxisID(), 'The xAxisID value should not be set');

        $this->assertInstanceOf(DataSet::class, $dataSet->setXAxisID('myXAxis'));
        $this->assertEquals('myXAxis', $dataSet->getXAxisID(), 'The correct value is returned');
        $this->assertEquals('myXAxis', $dataSet->getArrayCopy()['xAxisID'], 'getArrayCopy is failing');
        $this->assertEquals('myXAxis', $dataSet->jsonSerialize()['xAxisID'], 'Serialized data is not correct');

        $this->assertNull($dataSet->getYAxisID(), 'The yAxisID value is not set');

        $this->assertInstanceOf(DataSet::class, $dataSet->setYAxisID('myYAxis'));
        $this->assertEquals('myYAxis', $dataSet->getYAxisID(), 'The correct value is not returned');
        $this->assertEquals('myXAxis', $dataSet->jsonSerialize()['xAxisID'], 'Serialized data is not correct');
    }

    /**
     *
     */
    public function testHoverBackgroundColor()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getHoverBackgroundColor(), 'The hoverBackgroundColor value is not set');

        $this->assertInstanceOf(DataSet::class, $dataSet->setHoverBackgroundColor('#fff'));
        $this->assertEquals('#fff', $dataSet->getHoverBackgroundColor(), 'The correct value is returned');

        $newColors = ['silver', '#fff', 'rgb( 0, 0, 0 )', 'rgba( 255, 255, 255, .5 )'];
        $dataSet->setHoverBackgroundColor($newColors);
        $this->assertEquals($newColors, $dataSet->getHoverBackgroundColor(), 'The correct value is returned');
    }

    /**
     *
     */
    public function testHoverBorderColor()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getHoverBorderColor(), 'The hoverBorderColor value is not set');

        $this->assertInstanceOf(DataSet::class, $dataSet->setHoverBorderColor('#fff'));
        $this->assertEquals('#fff', $dataSet->getHoverBorderColor(), 'The correct value is returned');

        $dataSet->setHoverBorderColor(['silver', '#fff', 'rgb( 0, 0, 0 )', 'rgba( 255, 255, 255, .5 )', 0]);
        $this->assertEquals(
            ['silver', '#fff', 'rgb( 0, 0, 0 )', 'rgba( 255, 255, 255, .5 )', '0'],
            $dataSet->getHoverBorderColor(),
            'The correct value is returned'
        );
    }

    /**
     *
     */
    public function testHoverBorderWidth()
    {
        $dataSet = new DataSet();

        $this->assertNull($dataSet->getHoverBorderWidth(), 'The hoverBorderWidth value is not set');

        $this->assertInstanceOf(DataSet::class, $dataSet->setHoverBorderWidth(1));
        $this->assertEquals(1, $dataSet->getHoverBorderWidth(), 'The correct value is returned');

        $dataSet->setHoverBorderWidth([1, 10, '5a', 0]);
        $this->assertEquals([1, 10, 5, 0], $dataSet->getHoverBorderWidth(), 'The correct value is returned');
    }

    /**
     *
     */
    public function testVisibility()
    {
        $dataSet = new DataSet();
        $this->assertArrayNotHasKey('hidden', $dataSet->jsonSerialize(), 'Value should not be present');
        $this->assertFalse($dataSet->isHidden(), 'Default value should be false');
        $this->assertArrayHasKey('hidden', $dataSet->jsonSerialize(), 'Value should be present');
        $this->assertInstanceOf(DataSet::class, $dataSet->setHidden(true));
        $this->assertTrue($dataSet->isHidden(), 'Value should be true');
        $this->assertTrue($dataSet->jsonSerialize()['hidden'], 'Value should be true');
        $this->assertInstanceOf(DataSet::class, $dataSet->setHidden(null));
        $this->assertArrayNotHasKey('hidden', $dataSet->jsonSerialize(), 'Value should not be present');
    }
}
