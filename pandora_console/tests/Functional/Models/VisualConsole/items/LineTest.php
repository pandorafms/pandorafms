<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Line;

/**
 * Test class
 */
class LineTest extends TestCase
{


    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Line::class,
            Line::fromArray(
                [
                    'id'          => 10,
                    'type'        => LINE_ITEM,
                    'startX'      => 50,
                    'startY'      => 100,
                    'endX'        => 0,
                    'endY'        => 10,
                    'isOnTop'     => false,
                    'borderWidth' => 0,
                    'borderColor' => 'white',
                ]
            )
        );

        $this->assertInstanceOf(
            Line::class,
            Line::fromArray(
                [
                    'id'          => 10,
                    'type'        => LINE_ITEM,
                    'startX'      => 50,
                    'endY'        => 10,
                    'borderColor' => 'black',
                ]
            )
        );
    }


    public function testCannotBeCreatedWithInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        Line::fromArray(
            [
                'id'          => 'foo',
                'type'        => LINE_ITEM,
                'startX'      => 50,
                'startY'      => 100,
                'endX'        => 0,
                'endY'        => 10,
                'isOnTop'     => false,
                'borderWidth' => 0,
                'borderColor' => 'white',
            ]
        );
        // Missing id.
        Line::fromArray(
            [
                'type'        => LINE_ITEM,
                'startX'      => 50,
                'startY'      => 100,
                'endX'        => 0,
                'endY'        => 10,
                'isOnTop'     => false,
                'borderWidth' => 0,
                'borderColor' => 'white',
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidtype(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid type.
        Line::fromArray(
            [
                'id'          => 13,
                'type'        => 'test',
                'startX'      => 50,
                'startY'      => 100,
                'endX'        => 0,
                'endY'        => 10,
                'isOnTop'     => false,
                'borderWidth' => 0,
                'borderColor' => 'white',
            ]
        );
        // Missing type.
        Line::fromArray(
            [
                'id'          => 13,
                'startX'      => 50,
                'startY'      => 100,
                'endX'        => 0,
                'endY'        => 10,
                'isOnTop'     => true,
                'borderWidth' => 0,
                'borderColor' => 'white',
            ]
        );
    }


    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":1,"type":13,"startX":50,"startY":100,"endX":0,"endY":10,"isOnTop":false,"borderWidth":0,"borderColor":"white"}',
            Line::fromArray(
                [
                    'id'          => 1,
                    'type'        => LINE_ITEM,
                    'startX'      => 50,
                    'startY'      => 100,
                    'endX'        => 0,
                    'endY'        => 10,
                    'isOnTop'     => false,
                    'borderWidth' => 0,
                    'borderColor' => 'white',
                ]
            )
        );
    }


}
