<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Box;

/**
 * Test for the Visual Console Box Item model.
 */
class BoxTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
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


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"aclGroupId":null,"borderColor":null,"borderWidth":0,"fillColor":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","parentId":null,"type":12,"width":0,"x":-666,"y":76}',
            (string) Box::fromArray(
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
