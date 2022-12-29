<?php

namespace Test\Options;

use Artica\PHPChartJS\Options\Elements;
use Artica\PHPChartJS\Options\Elements\Arc;
use Artica\PHPChartJS\Options\Elements\Line;
use Artica\PHPChartJS\Options\Elements\Point;
use Artica\PHPChartJS\Options\Elements\Rectangle;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class ElementsTest
 *
 * @package Test\Options
 */
class ElementsTest extends TestCase
{
    /**
     * @var Elements
     */
    private $elements;

    /**
     * @var array
     */
    private $data_types = [
        'rectangle' => '', /* Rectangle */
        'line'      => '', /* Line */
        'point'     => '', /* Point */
        'arc'       => '', /* Arc */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'rectangle' => null, /* Rectangle */
        'line'      => null, /* Line */
        'point'     => null, /* Point */
        'arc'       => null, /* Arc */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'rectangle' => null, /* Rectangle */
        'line'      => null, /* Line */
        'point'     => null, /* Point */
        'arc'       => null, /* Arc */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->elements = new Elements();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->elements, $this->data_types);
        self::assertEquals($expected, $result);
    }

    /**
     *
     */
    public function testRectangle()
    {
        $rectangle = $this->elements->rectangle();
        self::assertNotNull($rectangle);
        self::assertInstanceOf(Rectangle::class, $rectangle);
    }

    /**
     *
     */
    public function testLine()
    {
        $line = $this->elements->line();
        self::assertNotNull($line);
        self::assertInstanceOf(Line::class, $line);
    }

    /**
     *
     */
    public function testPoint()
    {
        $point = $this->elements->point();
        self::assertNotNull($point);
        self::assertInstanceOf(Point::class, $point);
    }

    /**
     *
     */
    public function testArc()
    {
        $arc = $this->elements->arc();
        self::assertNotNull($arc);
        self::assertInstanceOf(Arc::class, $arc);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->elements, $this->input_data);
        $result = $this->elements->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
