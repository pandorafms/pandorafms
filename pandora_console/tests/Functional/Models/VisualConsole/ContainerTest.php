<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Container as VisualConsole;

/**
 * Test for the Visual Console Container.
 */
class ContainerTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid id.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid name.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid group id.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidGroupId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid group id.
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


    /**
     * Test if the instance is not created when using a invalid width.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid width.
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


    /**
     * Test if the instance is not created when using a invalid height.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidHeigth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid height.
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


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"backgroundColor":null,"backgroundURL":null,"groupId":0,"height":768,"id":1,"isFavorite":false,"name":"foo","width":1024}',
            (string) VisualConsole::fromArray(
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


    /**
     * Test if the item's instance is returned properly.
     *
     * @return void
     */
    public function testItemClassIsReturned(): void
    {
        $this->assertEquals(
            VisualConsole::getItemClass(STATIC_GRAPH),
            Models\VisualConsole\Items\StaticGraph::class
        );

        $this->assertEquals(
            VisualConsole::getItemClass(COLOR_CLOUD),
            Models\VisualConsole\Items\ColorCloud::class
        );

        $this->assertEquals(
            VisualConsole::getItemClass(LABEL),
            Models\VisualConsole\Items\Label::class
        );
    }


}
