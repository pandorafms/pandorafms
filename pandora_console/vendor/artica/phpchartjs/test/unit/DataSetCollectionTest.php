<?php

namespace Artica\PHPChartJS;

use PHPUnit\Framework\TestCase;

/**
 * Class DataSetCollectionTest
 *
 * @package Artica\PHPChartJS
 */
class DataSetCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var  DataSetCollection */
    private $dataSetCollection;

    /**
     *
     */
    public function setUp(): void
    {
        $this->dataSetCollection = new DataSetCollection();
    }

    /**
     *
     */
    public function testGetArrayCopyEmpty()
    {
        $expected = [];
        $result   = $this->dataSetCollection->getArrayCopy();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetArrayCopyNonEmpty()
    {
        $expected = [[]];
        $this->dataSetCollection->append(new DataSet());
        $result = $this->dataSetCollection->getArrayCopy();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerializeEmpty()
    {
        $expected = [];
        $result   = $this->dataSetCollection->jsonSerialize();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testJsonSerializeNonEmpty()
    {
        $expected = [[]];
        $this->dataSetCollection->append(new DataSet());
        $result = $this->dataSetCollection->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
