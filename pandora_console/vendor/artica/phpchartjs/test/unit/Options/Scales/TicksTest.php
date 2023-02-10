<?php

namespace Test\Options\Scales;

use Artica\PHPChartJS\Options\Scales\Ticks;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class TicksTest
 *
 * @package Test\Options\Scales
 */
class TicksTest extends TestCase
{
    /**
     * @var Ticks
     */
    private $ticks;

    /**
     * @var array
     */
    private $data_types = [
        'suggestedMin'    => 1.0,
        'beginAtZero'     => false,
        'stepSize'        => 1.0,
        'autoSkip'        => false,
        'autoSkipPadding' => 1,
        'callback'        => '',
        'display'         => false,
        'fontColor'       => '',
        'fontFamily'      => '',
        'fontSize'        => 1,
        'fontStyle'       => '',
        'labelOffset'     => 1,
        'maxRotation'     => 1,
        'minRotation'     => 1,
        'mirror'          => false,
        'padding'         => 1,
        'reverse'         => false,
    ];

    /**
     * @var array
     */
    private $input_data_1 = [
        'suggestedMin'    => 2.0,
        'beginAtZero'     => true,
        'stepSize'        => 3.0,
        'autoSkip'        => true,
        'autoSkipPadding' => 4,
        'callback'        => null,
        'display'         => true,
        'fontColor'       => 'fontColor',
        'fontFamily'      => 'fontFamily',
        'fontSize'        => 5,
        'fontStyle'       => 'fontStyle',
        'labelOffset'     => 6,
        'maxRotation'     => 7,
        'minRotation'     => 8,
        'mirror'          => true,
        'padding'         => 9,
        'reverse'         => true,
    ];

    /**
     * @var array
     */
    private $input_data_2 = [
        'suggestedMin'    => 2.0,
        'beginAtZero'     => true,
        'stepSize'        => 3.0,
        'autoSkip'        => true,
        'autoSkipPadding' => 4,
        'callback'        => "function () { console.log('Hello'); }",
        'display'         => true,
        'fontColor'       => 'fontColor',
        'fontFamily'      => 'fontFamily',
        'fontSize'        => 5,
        'fontStyle'       => 'fontStyle',
        'labelOffset'     => 6,
        'maxRotation'     => 7,
        'minRotation'     => 8,
        'mirror'          => true,
        'padding'         => 9,
        'reverse'         => true,
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'suggestedMin'    => null,
        'beginAtZero'     => null,
        'stepSize'        => null,
        'autoSkip'        => null,
        'autoSkipPadding' => null,
        'callback'        => null,
        'display'         => null,
        'fontColor'       => null,
        'fontFamily'      => null,
        'fontSize'        => null,
        'fontStyle'       => null,
        'labelOffset'     => null,
        'maxRotation'     => null,
        'minRotation'     => null,
        'mirror'          => null,
        'padding'         => null,
        'reverse'         => null,
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->ticks = new Ticks();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->ticks, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSetWithoutExpr()
    {
        $expected = $this->input_data_1;
        TestUtils::setAttributes($this->ticks, $this->input_data_1);
        $result = TestUtils::getAttributes($this->ticks, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testExpr()
    {
        TestUtils::setAttributes($this->ticks, $this->input_data_2);
        $result   = $this->ticks->getCallback()->__toString();
        $expected = $this->input_data_2['callback'];
        self::assertSame($expected, $result);
    }

    /**
     * This test uses assertEquals in stead of assertSame because json_encode / json_decode
     * transform the float numbers to string, after which the decimal zero's disappear. It is
     * still a float, but will be recognized by assertSame as an int. For that reason assertSame
     * will not work as expected.
     *
     */
    public function testJsonSerializeWithoutExpr()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data_1);
        TestUtils::setAttributes($this->ticks, $expected);
        $result = $this->ticks->jsonSerialize();
        self::assertEquals($expected, $result);
    }
}
