<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\items\Box;

/**
 * Test class
 */
class BoxTest extends TestCase
{


    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Box::class,
            Box::fromArray(
                [
                    'id'            => 69,
                    'type'          => 12,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                ]
            )
        );

        $this->assertInstanceOf(
            Box::class,
            Box::fromArray(
                [
                    'id'     => 1000,
                    'type'   => 8,
                    'name'   => 'test',
                    'width'  => 100,
                    'height' => 900,
                ]
            )
        );
    }


    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":7,"type":12,"label":null,"labelPosition":"up","isLinkEnabled":true,"isOnTop":false,"parentId":null,"aclGroupId":null,"width":0,"height":0,"x":-666,"y":76,"borderWidth":0,"borderColor":null,"fillColor":null}',
            Box::fromArray(
                [
                    'id'            => 7,
                    'type'          => 10,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                ]
            )
        );
    }


}
