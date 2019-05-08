<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Line;

/**
 * Test for the Visual Console Box Icon Item model.
 */
class LineTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid Id.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid type.
     *
     * @return void
     */
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


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"borderColor":"white","borderWidth":0,"endX":0,"endY":10,"id":1,"isOnTop":false,"startX":50,"startY":100,"type":13}',
            (string) Line::fromArray(
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
