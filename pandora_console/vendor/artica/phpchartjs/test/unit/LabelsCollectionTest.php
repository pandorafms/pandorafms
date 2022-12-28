<?php

namespace Test;

use Artica\PHPChartJS\LabelsCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class LabelsCollectionTest
 *
 * @package Test
 */
class LabelsCollectionTest extends TestCase
{

    /**
     * @var LabelsCollection
     */
    private $labelsCollectionEmpty;

    /**
     * @var LabelsCollection
     */
    private $labelsCollection;

    /**
     * @var array
     */
    private $labelsArray = [
        'startingLabel',
        'label1',
        'label2',
        'endLabel',
    ];


    /**
     *
     */
    public function setUp(): void
    {
        $this->labelsCollectionEmpty = new LabelsCollection();
        $this->labelsCollection      = new LabelsCollection($this->labelsArray);
    }


    /**
     *
     */
    public function testJsonSerializeEmpty()
    {
        $expected = [];
        $result   = $this->labelsCollectionEmpty->jsonSerialize();
        self::assertSame($expected, $result);
    }


    /**
     *
     */
    public function testJsonSerialize()
    {
        $expected = $this->labelsArray;
        $result   = $this->labelsCollection->jsonSerialize();
        self::assertSame($expected, $result);
    }


}
