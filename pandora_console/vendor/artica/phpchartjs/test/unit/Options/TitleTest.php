<?php

namespace Test\Options;

use Artica\PHPChartJS\Options\Title;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class TitleTest
 * @package Test\Options
 */
class TitleTest extends TestCase
{
    /**
     * @var Title
     */
    private $title;

    /**
     * @var array
     */
    private $data_types = [
        'display'    => false, /* bool */
        'position'   => '', /* string */
        'fullWidth'  => false, /* bool */
        'fontSize'   => 1, /* int */
        'fontFamily' => '', /* string */
        'fontColor'  => '', /* string */
        'fontStyle'  => '', /* string */
        'padding'    => 1, /* int */
        'text'       => '', /* string */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'display'    => true, /* bool */
        'position'   => 'position', /* string */
        'fullWidth'  => true, /* bool */
        'fontSize'   => 2, /* int */
        'fontFamily' => 'fontFamily', /* string */
        'fontColor'  => 'fontColor', /* string */
        'fontStyle'  => 'fontStyle', /* string */
        'padding'    => 3, /* int */
        'text'       => 'text', /* string */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'display'    => null, /* bool */
        'position'   => null, /* string */
        'fullWidth'  => null, /* bool */
        'fontSize'   => null, /* int */
        'fontFamily' => null, /* string */
        'fontColor'  => null, /* string */
        'fontStyle'  => null, /* string */
        'padding'    => null, /* int */
        'text'       => null, /* string */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->title = new Title();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->title, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSet()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->title, $this->input_data);
        $result = TestUtils::getAttributes($this->title, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->title, $this->input_data);
        $result = $this->title->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
