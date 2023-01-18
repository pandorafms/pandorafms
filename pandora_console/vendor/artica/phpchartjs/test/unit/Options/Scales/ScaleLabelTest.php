<?php

namespace Test\Options\Scales;

use Artica\PHPChartJS\Options\Scales\ScaleLabel;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class ScaleLabelTest
 *
 * @package Test\Options\Scales
 */
class ScaleLabelTest extends TestCase
{
    /**
     * @var ScaleLabel
     */
    private $scaleLabel;

    /**
     * @var array
     */
    private $data_types = [
        'display'     => false,
        'labelString' => '',
        'fontColor'   => '',
        'fontFamily'  => '',
        'fontSize'    => 1,
        'fontStyle'   => '',
    ];

    /**
     * @var array
     */
    private $input_data = [
        'display'     => false,
        'labelString' => 'labelString',
        'fontColor'   => 'fontColor',
        'fontFamily'  => 'fontFamily',
        'fontSize'    => 1,
        'fontStyle'   => 'fontStyle',
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'display'     => null,
        'labelString' => null,
        'fontColor'   => null,
        'fontFamily'  => null,
        'fontSize'    => null,
        'fontStyle'   => null,
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->scaleLabel = new ScaleLabel();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->scaleLabel, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSet()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->scaleLabel, $this->input_data);
        $result = TestUtils::getAttributes($this->scaleLabel, $this->data_types);
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
        $expected = $this->input_data;
        TestUtils::setAttributes($this->scaleLabel, $this->input_data);
        $result = $this->scaleLabel->jsonSerialize();
        self::assertEquals($expected, $result);
    }
}
