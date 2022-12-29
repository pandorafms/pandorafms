<?php

namespace Test\Options\Elements;

use Artica\PHPChartJS\Options\Elements\Point;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class PointTest
 *
 * @package Test\Options\Elements
 */
class PointTest extends TestCase
{
    /**
     * @var Point
     */
    protected $point;

    /**
     * @var array
     */
    private $data_types = [
        'radius'           => 1, /* int */
        'pointStyle'       => '', /* string */
        'rotation'         => 1, /* int */
        'backgroundColor'  => '', /* string */
        'borderWidth'      => 1, /* int */
        'borderColor'      => '', /* string */
        'hitRadius'        => 1, /* int */
        'hoverRadius'      => 1, /* int */
        'hoverBorderWidth' => 1, /* int */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'radius'           => null, /* int */
        'pointStyle'       => null, /* string */
        'rotation'         => null, /* int */
        'backgroundColor'  => null, /* string */
        'borderWidth'      => null, /* int */
        'borderColor'      => null, /* string */
        'hitRadius'        => null, /* int */
        'hoverRadius'      => null, /* int */
        'hoverBorderWidth' => null, /* int */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'radius'           => 2, /* int */
        'pointStyle'       => 'pointStyle', /* string */
        'rotation'         => 2, /* int */
        'backgroundColor'  => 'backgroundColor', /* string */
        'borderWidth'      => 2, /* int */
        'borderColor'      => 'borderColor', /* string */
        'hitRadius'        => 2, /* int */
        'hoverRadius'      => 2, /* int */
        'hoverBorderWidth' => 2, /* int */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->point = new Point();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->point, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetNoObjects()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->point, $this->input_data);
        $result = TestUtils::getAttributes($this->point, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->point, $this->input_data);
        $result = $this->point->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
