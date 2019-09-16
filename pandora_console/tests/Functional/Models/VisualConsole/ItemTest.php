<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Item as ItemConsole;

/**
 * Test for the Visual Console Item model.
 */
class ItemTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            ItemConsole::class,
            ItemConsole::fromArray(
                [
                    'id'            => 1,
                    'type'          => 5,
                    'label'         => 'test',
                    'labelPosition' => 'down',
                    'isLinkEnabled' => false,
                    'isOnTop'       => true,
                    'parentId'      => 0,
                    'aclGroupId'    => 12,
                    'width'         => 800,
                    'height'        => 600,
                    'x'             => 0,
                    'y'             => 0,
                ]
            )
        );

        $this->assertInstanceOf(
            ItemConsole::class,
            ItemConsole::fromArray(
                [
                    'id'     => 1,
                    'type'   => 5,
                    'width'  => 0,
                    'height' => 0,
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid id.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        ItemConsole::fromArray(
            [
                'id'            => 'foo',
                'type'          => 5,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 800,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
        // Missing id.
        ItemConsole::fromArray(
            [
                'type'          => 5,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 800,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
    }


    /**
     * Test if the instance is not created when using a invalid type.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        ItemConsole::fromArray(
            [
                'id'            => 15,
                'type'          => 'clock',
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 800,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
        // Missing id.
        ItemConsole::fromArray(
            [
                'id'            => 6,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 800,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
    }


    /**
     * Test if the instance is not created when using a invalid width.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        ItemConsole::fromArray(
            [
                'id'            => 15,
                'type'          => 3,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => -1,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
        // Missing id.
        ItemConsole::fromArray(
            [
                'id'            => 15,
                'type'          => 3,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'height'        => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
    }


    /**
     * Test if the instance is not created when using a invalid height.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidHeight(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        ItemConsole::fromArray(
            [
                'id'            => 15,
                'type'          => 3,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 800,
                'height'        => -1,
                'x'             => 0,
                'y'             => 0,
            ]
        );
        // Missing id.
        ItemConsole::fromArray(
            [
                'id'            => 15,
                'type'          => 3,
                'label'         => 'test',
                'labelPosition' => 'down',
                'isLinkEnabled' => false,
                'isOnTop'       => true,
                'parentId'      => 0,
                'aclGroupId'    => 12,
                'width'         => 600,
                'x'             => 0,
                'y'             => 0,
            ]
        );
    }


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testItemIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"aclGroupId":12,"height":600,"id":15,"isLinkEnabled":false,"isOnTop":true,"label":"test","labelPosition":"down","parentId":0,"type":3,"width":800,"x":0,"y":0}',
            (string) ItemConsole::fromArray(
                [
                    'id'            => 15,
                    'type'          => 3,
                    'label'         => 'test',
                    'labelPosition' => 'down',
                    'isLinkEnabled' => false,
                    'isOnTop'       => true,
                    'parentId'      => 0,
                    'aclGroupId'    => 12,
                    'width'         => 800,
                    'height'        => 600,
                    'x'             => 0,
                    'y'             => 0,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":12,"height":600,"id":15,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","parentId":0,"type":3,"width":800,"x":0,"y":0}',
            (string) ItemConsole::fromArray(
                [
                    'id'            => 15,
                    'type'          => 3,
                    'label'         => '',
                    'labelPosition' => 'test',
                    'parentId'      => 0,
                    'aclGroupId'    => 12,
                    'width'         => 800,
                    'height'        => 600,
                    'x'             => 0,
                    'y'             => 0,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":69,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ItemConsole::fromArray(
                [
                    'id'            => 69,
                    'type'          => 20,
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
