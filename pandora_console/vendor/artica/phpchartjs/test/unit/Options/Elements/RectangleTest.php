<?php

namespace Test\Options\Elements;

use Artica\PHPChartJS\Options\Elements\Rectangle;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class RectangleTest
 *
 * @package Test\Options\Elements
 */
class RectangleTest extends TestCase
{
    /**
     * @var Rectangle
     */
    protected $rectangle;

    /**
     * @var array
     */
    private $data_types = [
        'backgroundColor' => '', /* string */
        'borderWidth'     => 1, /* int */
        'borderColor'     => '', /* string */
        'borderSkipped'   => '', /* string */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'backgroundColor' => null, /* string */
        'borderWidth'     => null, /* int */
        'borderColor'     => null, /* string */
        'borderSkipped'   => null, /* string */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'backgroundColor' => 'backgroundColor', /* string */
        'borderWidth'     => 2, /* int */
        'borderColor'     => 'borderColor', /* string */
        'borderSkipped'   => 'borderSkipped', /* string */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->rectangle = new Rectangle();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->rectangle, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetNoObjects()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->rectangle, $this->input_data);
        $result = TestUtils::getAttributes($this->rectangle, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->rectangle, $this->input_data);
        $result = $this->rectangle->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
