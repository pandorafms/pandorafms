<?php

namespace Test\Options\Elements;

use Artica\PHPChartJS\Options\Elements\Line;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class LineTest
 *
 * @package Test\Options\Elements
 */
class LineTest extends TestCase
{
    /**
     * @var Line
     */
    protected $line;

    /**
     * @var array
     */
    private $data_types = [
        'tension'          => 1.0, /* float */
        'backgroundColor'  => '', /* string */
        'borderWidth'      => 1, /* int */
        'borderColor'      => '', /* string */
        'borderCapStyle'   => '', /* string */
        'borderDash'       => [1, 2], /* int[] */
        'borderDashOffset' => 1.0, /* float */
        'borderJoinStyle'  => '', /* string */
        'capBezierPoints'  => false, /* bool */
        'fill'             => '', /* string */
        'stepped'          => false, /* bool */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'tension'          => null, /* float */
        'backgroundColor'  => null, /* string */
        'borderWidth'      => null, /* int */
        'borderColor'      => null, /* string */
        'borderCapStyle'   => null, /* string */
        'borderDash'       => null, /* int[] */
        'borderDashOffset' => null, /* float */
        'borderJoinStyle'  => null, /* string */
        'capBezierPoints'  => null, /* bool */
        'fill'             => null, /* bool */
        'stepped'          => null, /* bool */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'tension'          => 0.2, /* float */
        'backgroundColor'  => 'backgroundColor', /* string */
        'borderWidth'      => 2, /* int */
        'borderColor'      => 'borderColor', /* string */
        'borderCapStyle'   => 'borderCapStyle', /* string */
        'borderDash'       => [5, 6], /* int[] */
        'borderDashOffset' => 0.1, /* float */
        'borderJoinStyle'  => 'borderJoinStyle', /* string */
        'capBezierPoints'  => true, /* bool */
        'fill'             => true, /* bool */
        'stepped'          => true, /* bool */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->line = new Line();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->line, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetNoObjects()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->line, $this->input_data);
        $result = TestUtils::getAttributes($this->line, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->line, $this->input_data);
        $result = $this->line->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
