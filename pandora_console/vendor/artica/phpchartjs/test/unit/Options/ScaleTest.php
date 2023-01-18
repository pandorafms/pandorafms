<?php

namespace Test\Options;

use Artica\PHPChartJS\Options\Scale;
use Artica\PHPChartJS\Options\Scales\GridLines;
use Artica\PHPChartJS\Options\Scales\ScaleLabel;
use Artica\PHPChartJS\Options\Scales\Ticks;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class AScale
 *
 * @package Test\Options
 */
class AScale extends Scale
{
}

/**
 * Class ScaleTest
 *
 * @package Test\Options
 */
class ScaleTest extends TestCase
{
    /**
     * @var Scale
     */
    private $scale;

    /**
     * @var array
     */
    private $data_types = [
        'type'                        => '', /* string */
        'display'                     => false, /* bool */
        'id'                          => '', /* string */
        'stacked'                     => false, /* bool */
        'barThickness'                => 1, /* int */
        'position'                    => '', /* string */
        'beforeUpdate'                => '', /* string */
        'beforeSetDimensions'         => '', /* string */
        'afterSetDimensions'          => '', /* string */
        'beforeDataLimits'            => '', /* string */
        'afterDataLimits'             => '', /* string */
        'beforeBuildTicks'            => '', /* string */
        'afterBuildTicks'             => '', /* string */
        'beforeTickToLabelConversion' => '', /* string */
        'afterTickToLabelConversion'  => '', /* string */
        'beforeCalculateTickRotation' => '', /* string */
        'afterCalculateTickRotation'  => '', /* string */
        'beforeFit'                   => '', /* string */
        'afterFit'                    => '', /* string */
        'afterUpdate'                 => '', /* string */
        'gridLines'                   => '', /* GridLines */
        'scaleLabel'                  => '', /* ScaleLabel */
        'ticks'                       => '', /* Ticks */
    ];

    /**
     * @var array
     */
    private $input_data = [
        'type'                        => 'type', /* string */
        'display'                     => true, /* bool */
        'id'                          => 'id', /* string */
        'stacked'                     => true, /* bool */
        'barThickness'                => 2, /* int */
        'position'                    => 'position', /* string */
        'beforeUpdate'                => 'beforeUpdate', /* string */
        'beforeSetDimensions'         => 'beforeSetDimensions', /* string */
        'afterSetDimensions'          => 'afterSetDimensions', /* string */
        'beforeDataLimits'            => 'beforeDataLimits', /* string */
        'afterDataLimits'             => 'afterDataLimits', /* string */
        'beforeBuildTicks'            => 'beforeBuildTicks', /* string */
        'afterBuildTicks'             => 'afterBuildTicks', /* string */
        'beforeTickToLabelConversion' => 'beforeTickToLabelConversion', /* string */
        'afterTickToLabelConversion'  => 'afterTickToLabelConversion', /* string */
        'beforeCalculateTickRotation' => 'beforeCalculateTickRotation', /* string */
        'afterCalculateTickRotation'  => 'afterCalculateTickRotation', /* string */
        'beforeFit'                   => 'beforeFit', /* string */
        'afterFit'                    => 'afterFit', /* string */
        'afterUpdate'                 => 'afterUpdate', /* string */
        'gridLines'                   => null, /* GridLines */
        'scaleLabel'                  => null, /* ScaleLabel */
        'ticks'                       => null, /* Ticks */
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'type'                        => null, /* string */
        'display'                     => null, /* bool */
        'id'                          => null, /* string */
        'stacked'                     => null, /* bool */
        'barThickness'                => null, /* int */
        'position'                    => null, /* string */
        'beforeUpdate'                => null, /* string */
        'beforeSetDimensions'         => null, /* string */
        'afterSetDimensions'          => null, /* string */
        'beforeDataLimits'            => null, /* string */
        'afterDataLimits'             => null, /* string */
        'beforeBuildTicks'            => null, /* string */
        'afterBuildTicks'             => null, /* string */
        'beforeTickToLabelConversion' => null, /* string */
        'afterTickToLabelConversion'  => null, /* string */
        'beforeCalculateTickRotation' => null, /* string */
        'afterCalculateTickRotation'  => null, /* string */
        'beforeFit'                   => null, /* string */
        'afterFit'                    => null, /* string */
        'afterUpdate'                 => null, /* string */
        'gridLines'                   => null, /* GridLines */
        'scaleLabel'                  => null, /* ScaleLabel */
        'ticks'                       => null, /* Ticks */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->scale = new AScale();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->scale, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetNoObjects()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->scale, $this->input_data);
        $result = TestUtils::getAttributes($this->scale, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGridLines()
    {
        $g = $this->scale->gridLines();
        self::assertInstanceOf(GridLines::class, $g);
    }

    /**
     *
     */
    public function testScaleLabel()
    {
        $s = $this->scale->scaleLabel();
        self::assertInstanceOf(ScaleLabel::class, $s);
    }

    /**
     *
     */
    public function testTicks()
    {
        $t = $this->scale->ticks();
        self::assertInstanceOf(Ticks::class, $t);
    }

    /**
     *
     */
    public function testJsonSerializeNoObjects()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data);
        TestUtils::setAttributes($this->scale, $this->input_data);
        $result = $this->scale->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
