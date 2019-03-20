<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Item as ItemConsole;

/**
 * Test class
 */
class ItemTest extends TestCase
{


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


    public function testItemIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":15,"type":3,"label":"test","labelPosition":"down","isLinkEnabled":false,"isOnTop":true,"parentId":0,"aclGroupId":12,"width":800,"height":600,"x":0,"y":0}',
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
                    'height'        => 600,
                    'x'             => 0,
                    'y'             => 0,
                ]
            )
        );

        $this->assertEquals(
            '{"id":15,"type":3,"label":null,"labelPosition":"down","isLinkEnabled":false,"isOnTop":false,"parentId":0,"aclGroupId":12,"width":800,"height":600,"x":0,"y":0}',
            ItemConsole::fromArray(
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
            '{"id":69,"type":20,"label":null,"labelPosition":"up","isLinkEnabled":true,"isOnTop":false,"parentId":null,"aclGroupId":null,"width":0,"height":0,"x":-666,"y":76}',
            ItemConsole::fromArray(
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
