<?php

namespace Test\Options;

use Artica\PHPChartJS\Options\Scales;
use PHPUnit\Framework\TestCase;

/**
 * Class ScalesTest
 *
 * @package Test\Options
 */
class ScalesTest extends TestCase
{
    /**
     * @var Scales
     */
    private $scales;

    /**
     *
     */
    public function setUp(): void
    {
        $this->scales = new Scales();
    }

    /**
     *
     */
    public function testCreateXAxis()
    {
        self::assertInstanceOf(Scales\XAxis::class, $this->scales->createXAxis());
    }

    /**
     *
     */
    public function testCreateYAxis()
    {
        self::assertInstanceOf(Scales\YAxis::class, $this->scales->createYAxis());
    }

    /**
     *
     */
    public function testEmptyXAxis()
    {
        $expected = new Scales\XAxisCollection();
        $result   = $this->scales->getXAxes();
        self::assertInstanceOf(Scales\XAxisCollection::class, $result);
        self::assertEquals($expected, $result);
    }

    /**
     *
     */
    public function testEmptyYAxis()
    {
        $expected = new Scales\YAxisCollection();
        $result   = $this->scales->getYAxes();
        self::assertInstanceOf(Scales\YAxisCollection::class, $result);
        self::assertEquals($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $xc = new Scales\XAxisCollection();
        $x1 = new Scales\XAxis();
        $x1->setBarThickness(2);
        $xc[]              = $x1;
        $expected['xAxes'] = $xc->jsonSerialize();

        $yc = new Scales\YAxisCollection();
        $y1 = new Scales\YAxis();
        $y1->setBarThickness(3);
        $yc[] = $y1;

        $expected['xAxes']          = $xc->jsonSerialize();
        $expected['yAxes']          = $yc->jsonSerialize();
        $this->scales->getXAxes()[] = $x1;
        $this->scales->getYAxes()[] = $y1;
        $result                     = $this->scales->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
