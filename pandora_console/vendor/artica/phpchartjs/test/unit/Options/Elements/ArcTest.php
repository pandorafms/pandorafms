<?php

namespace Test\Options\Elements;

use Artica\PHPChartJS\Options\Elements\Arc;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class ArcTest
 *
 * @package Test\Options\Elements
 */
class ArcTest extends TestCase
{
    /**
     * @var Arc
     */
    protected $arc;

    /**
     * @var array
     */
    private $data_types = [
        'backgroundColor' => '', /* string */
        'borderColor'     => '', /* string */
        'borderWidth'     => 1, /* int */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'backgroundColor' => null, /* string */
        'borderColor'     => null, /* string */
        'borderWidth'     => null, /* int */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'backgroundColor' => 'backgroundColor', /* string */
        'borderColor'     => 'borderColor', /* string */
        'borderWidth'     => 2, /* int */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->arc = new Arc();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->arc, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetNoObjects()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->arc, $this->input_data);
        $result = TestUtils::getAttributes($this->arc, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->arc, $this->input_data);
        $result = $this->arc->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
