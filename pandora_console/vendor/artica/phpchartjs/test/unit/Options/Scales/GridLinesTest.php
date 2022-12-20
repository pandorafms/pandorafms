<?php

namespace Test\Options\Scales;

use Artica\PHPChartJS\Options\Scales\GridLines;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class GridLinesTest
 *
 * @package Test\Options\Scales
 */
class GridLinesTest extends TestCase
{
    /**
     * @var GridLines
     */
    private $gridLines;

    /**
     * @var array
     */
    private $data_types = [
        'display'          => false,
        'color'            => '',
        'borderDash'       => 1.0,
        'borderDashOffset' => [1.0],
        'lineWidth'        => 1,
        'drawBorder'       => false,
        'drawOnChartArea'  => false,
        'drawTicks'        => false,
        'tickMarkLength'   => 1,
        'zeroLineWidth'    => 1,
        'zeroLineColor'    => '',
        'offsetGridLines'  => false,
    ];

    /**
     * @var array
     */
    private $input_data_single_value = [
        'display'          => true,
        'color'            => 'color',
        'borderDash'       => [2.0],
        'borderDashOffset' => 3.0,
        'lineWidth'        => 4,
        'drawBorder'       => true,
        'drawOnChartArea'  => true,
        'drawTicks'        => true,
        'tickMarkLength'   => 5,
        'zeroLineWidth'    => 6,
        'zeroLineColor'    => 'zeroLineColor',
        'offsetGridLines'  => true,
    ];

    private $input_data_nested_arrays = [
        'display'          => true,
        'color'            => ['color1', 'color2', ['color3', 'color4']],
        'borderDash'       => [2.0, 3.0, [4.0, 5.0]],
        'borderDashOffset' => 3.0,
        'lineWidth'        => [4, 5, [6, 7], 8],
        'drawBorder'       => true,
        'drawOnChartArea'  => true,
        'drawTicks'        => true,
        'tickMarkLength'   => 5,
        'zeroLineWidth'    => 6,
        'zeroLineColor'    => 'zeroLineColor',
        'offsetGridLines'  => true,
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'display'          => null,
        'color'            => null,
        'borderDash'       => null,
        'borderDashOffset' => null,
        'lineWidth'        => null,
        'drawBorder'       => null,
        'drawOnChartArea'  => null,
        'drawTicks'        => null,
        'tickMarkLength'   => null,
        'zeroLineWidth'    => null,
        'zeroLineColor'    => null,
        'offsetGridLines'  => null,
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->gridLines = new GridLines();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->gridLines, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetSingleValues()
    {
        $expected = $this->input_data_single_value;
        TestUtils::setAttributes($this->gridLines, $this->input_data_single_value);
        $result = TestUtils::getAttributes($this->gridLines, $this->data_types);
        self::assertSame($expected, $result);
    }

    public function testGetAndSetNestedArrayValues()
    {
        $expected = $this->input_data_nested_arrays;
        TestUtils::setAttributes($this->gridLines, $this->input_data_nested_arrays);
        $result = TestUtils::getAttributes($this->gridLines, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     * This test uses assertEquals in stead of assertSame because json_encode / json_decode
     * transform the float numbers to string, after which the decimal zero's disappear. It is
     * still a float, but will be recognized by assertSame as an int. For that reason assertSame
     * will not work as expected.
     *
     */
    public function testJsonSerialize()
    {
        $expected = $this->input_data_single_value;
        TestUtils::setAttributes($this->gridLines, $this->input_data_single_value);
        $result = $this->gridLines->jsonSerialize();
        self::assertEquals($expected, $result);
    }
}
