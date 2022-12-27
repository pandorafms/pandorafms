<?php

namespace Collection;

use Artica\PHPChartJS\Collection\Data;
use PHPUnit\Framework\TestCase;

/**
 * Class DataTest
 *
 * @package Collection
 */
class DataTest extends TestCase
{

    /**
     * @var Data
     */
    private $data;


    /**
     *
     */
    public function setUp(): void
    {
        $this->data = new Data();
    }


    /**
     *
     */
    public function testJsonSerializeEmpty()
    {
        $expected = [];
        $result   = $this->data->jsonSerialize();
        self::assertSame($expected, $result);
    }


}
