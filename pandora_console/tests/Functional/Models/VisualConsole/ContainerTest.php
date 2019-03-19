<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Container as VisualConsole;

/**
 * Test class
 */
class ContainerTest extends TestCase
{


    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            VisualConsole::class,
            VisualConsole::fromArray(
                [
                    'id'              => 1,
                    'name'            => 'foo',
                    'groupId'         => 1,
                    'backgroundURL'   => 'aaa',
                    'backgroundColor' => 'bbb',
                    'width'           => 800,
                    'height'          => 800,
                ]
            )
        );

        $this->assertInstanceOf(
            VisualConsole::class,
            VisualConsole::fromArray(
                [
                    'id'               => 69,
                    'name'             => 'New visual console',
                    'groupId'          => 0,
                    'background'       => 'globalmap.jpg',
                    'background_color' => 'white',
                    'is_favourite'     => 1,
                    'width'            => 100,
                    'height'           => 200,
                ]
            )
        );

        $this->assertInstanceOf(
            VisualConsole::class,
            VisualConsole::fromArray(
                [
                    'id'      => 1030,
                    'name'    => 'console2',
                    'groupId' => 12,
                    'width'   => 1024,
                    'height'  => 768,
                ]
            )
        );
    }


    public function testCannotBeCreatedWithInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        VisualConsole::fromArray(
            [
                'id'           => 'bar',
                'name'         => 'foo',
                'groupId'      => 0,
                'is_favourite' => 1,
                'width'        => 1024,
                'height'       => 768,
            ]
        );
        // Missing id.
        VisualConsole::fromArray(
            [
                'name'    => 'foo',
                'groupId' => 0,
                'width'   => 1024,
                'height'  => 768,
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Empty name.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => '',
                'groupId' => 0,
                'width'   => 1024,
                'height'  => 768,
            ]
        );
        // Missing name.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'groupId' => 8,
                'width'   => 1024,
                'height'  => 768,
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidGroupId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // invalid group id.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => 'test',
                'groupId' => 'Hi',
                'width'   => 1024,
                'height'  => 768,
            ]
        );

        // Missing group id.
        VisualConsole::fromArray(
            [
                'id'     => 1,
                'name'   => 'test',
                'width'  => 1024,
                'height' => 768,
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // invalid width.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => 'test',
                'groupId' => 10,
                'width'   => 0,
                'height'  => 768,
            ]
        );

        // Missing width.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => 'test',
                'groupId' => 10,
                'height'  => 768,
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidHeigth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // invalid height.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => 'test',
                'groupId' => 10,
                'width'   => 1024,
                'height'  => -1,
            ]
        );

        // Missing height.
        VisualConsole::fromArray(
            [
                'id'      => 1,
                'name'    => 'test',
                'groupId' => 10,
                'width'   => 1024,
            ]
        );
    }


    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":1,"name":"foo","groupId":0,"backgroundURL":null,"backgroundColor":null,"isFavorite":false,"width":1024,"height":768}',
            VisualConsole::fromArray(
                [
                    'id'      => 1,
                    'name'    => 'foo',
                    'groupId' => 0,
                    'width'   => 1024,
                    'height'  => 768,
                ]
            )
        );
    }


}
