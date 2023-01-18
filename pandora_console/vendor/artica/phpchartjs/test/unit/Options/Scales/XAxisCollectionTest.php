<?php

namespace Test\Options\Scales;

use Artica\PHPChartJS\Options\Scales\XAxis;
use Artica\PHPChartJS\Options\Scales\XAxisCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class XAxisCollectionTest
 *
 * @package Test\Options\Scales
 */
class XAxisCollectionTest extends TestCase
{
    /**
     * @var XAxisCollection
     */
    private $xAxisCollection;

    /**
     * @var XAxis[]
     */
    private $input_data;

    /**
     *
     */
    public function setUp(): void
    {
        $this->xAxisCollection = new XAxisCollection();
        $this->input_data      = [];
        foreach ($this->input_data as $value) {
            $this->xAxisCollection[] = $value;
        }
    }

    /**
     *
     */
    public function testGetArrayCopyEmpty()
    {
        $expected        = [];
        $xAxisCollection = new XAxisCollection();
        $result          = $xAxisCollection->getArrayCopy();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetArrayCopyNonEmpty()
    {
        $expected                = [];
        $x                       = new XAxis();
        $expected[]              = $x->getArrayCopy();
        $this->xAxisCollection[] = $x;
        $result                  = $this->xAxisCollection->getArrayCopy();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerializeEmpty()
    {
        $expected = [];
        $result   = $this->xAxisCollection->jsonSerialize();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerializeNonEmpty()
    {
        $expected                = [];
        $x                       = new XAxis();
        $expected[]              = $x->getArrayCopy();
        $this->xAxisCollection[] = $x;
        $result                  = $this->xAxisCollection->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
