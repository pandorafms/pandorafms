<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Clock;

/**
 * Test for the Visual Console Clock Item model.
 */
class ClockTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Clock::class,
            Clock::fromArray(
                [
                    'id'                => 69,
                    'type'              => CLOCK,
                    'label'             => null,
                    'labelPosition'     => 'up',
                    'isLinkEnabled'     => true,
                    'isOnTop'           => false,
                    'parentId'          => null,
                    'width'             => '0',
                    'height'            => '0',
                    'x'                 => -666,
                    'y'                 => 76,
                    'clockType'         => 'digital',
                    'clockFormat'       => 'time',
                    'clockTimezone'     => 'Europe/Madrid',
                    'showClockTimezone' => false,
                    'color'             => 'white',
                ]
            )
        );

        $this->assertInstanceOf(
            Clock::class,
            Clock::fromArray(
                [
                    'id'                => 1000,
                    'type'              => CLOCK,
                    'width'             => 100,
                    'height'            => 900,
                    'clockType'         => 'analogic',
                    'clockFormat'       => 'datetime',
                    'clockTimezone'     => 'Asia/Tokyo',
                    'showClockTimezone' => true,
                    'color'             => 'red',
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid clockTimezone.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidClockTimezone(): void
    {
        $this->expectException(Exception::class);
        // Invalid clockTimezone.
        Clock::fromArray(
            [
                'id'                => 69,
                'type'              => CLOCK,
                'label'             => null,
                'labelPosition'     => 'up',
                'isLinkEnabled'     => true,
                'isOnTop'           => false,
                'parentId'          => null,
                'width'             => '0',
                'height'            => '0',
                'x'                 => -666,
                'y'                 => 76,
                'clockType'         => 'digital',
                'clockFormat'       => 'time',
                'clockTimezone'     => 'Europe/Tokyo',
                'showClockTimezone' => false,
                'color'             => 'white',
            ]
        );

        // Invalid clockTimezone.
        Clock::fromArray(
            [
                'id'                => 69,
                'type'              => CLOCK,
                'label'             => null,
                'labelPosition'     => 'up',
                'isLinkEnabled'     => true,
                'isOnTop'           => false,
                'parentId'          => null,
                'width'             => '0',
                'height'            => '0',
                'x'                 => -666,
                'y'                 => 76,
                'clockType'         => 'digital',
                'clockFormat'       => 'time',
                'clockTimezone'     => 'Europe/Tokyo',
                'showClockTimezone' => false,
                'color'             => 'white',
            ]
        );

        // Missing clockTimezone.
        Clock::fromArray(
            [
                'id'                => 69,
                'type'              => CLOCK,
                'label'             => null,
                'labelPosition'     => 'up',
                'isLinkEnabled'     => true,
                'isOnTop'           => false,
                'parentId'          => null,
                'width'             => '0',
                'height'            => '0',
                'x'                 => -666,
                'y'                 => 76,
                'clockType'         => 'digital',
                'clockFormat'       => 'time',
                'showClockTimezone' => false,
                'color'             => 'white',
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
            '{"aclGroupId":null,"clockFormat":"time","clockTimezone":"Europe\/Madrid","clockTimezoneOffset":7200,"clockType":"digital","color":"white","height":0,"id":69,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","parentId":null,"showClockTimezone":false,"type":19,"width":0,"x":-666,"y":76}',
            (string) Clock::fromArray(
                [
                    'id'                => 69,
                    'type'              => CLOCK,
                    'label'             => null,
                    'labelPosition'     => 'up',
                    'isLinkEnabled'     => true,
                    'isOnTop'           => false,
                    'parentId'          => null,
                    'width'             => '0',
                    'height'            => '0',
                    'x'                 => -666,
                    'y'                 => 76,
                    'clockType'         => 'digital',
                    'clockFormat'       => 'time',
                    'clockTimezone'     => 'Europe/Madrid',
                    'showClockTimezone' => false,
                    'color'             => 'white',
                ]
            )
        );
    }


}
