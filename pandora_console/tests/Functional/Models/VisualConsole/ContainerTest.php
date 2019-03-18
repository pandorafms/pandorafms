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
                    'id'   => 1,
                    'name' => 'foo',
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
                'id'   => 'bar',
                'name' => 'foo',
            ]
        );
        // Missing id.
        VisualConsole::fromArray(
            ['name' => 'foo']
        );
    }


    public function testCannotBeCreatedWithInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid name.
        VisualConsole::fromArray(
            [
                'id'   => 1,
                'name' => '',
            ]
        );
        // Missing name.
        VisualConsole::fromArray(
            ['id' => 1]
        );
    }


    public function testUserIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":1,"name":"foo"}',
            VisualConsole::fromArray(
                [
                    'id'   => 1,
                    'name' => 'foo',
                ]
            )
        );
    }


}
