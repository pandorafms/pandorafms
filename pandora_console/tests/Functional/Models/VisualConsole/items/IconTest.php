<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\items\Icon;

/**
 * Test class
 */
class IconTest extends TestCase
{


    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Icon::class,
            Icon::fromArray(
                [
                    'id'       => 69,
                    'type'     => ICON,
                    'width'    => '0',
                    'height'   => '0',
                    'imageSrc' => 'image.jpg',
                ]
            )
        );
    }


    public function testCannotBeCreatedWithInvalidImageSrc(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid imageSrc.
        Icon::fromArray(
            [
                'id'            => 7,
                'type'          => ICON,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'imageSrc'      => '',
            ]
        );
        // Missing imageSrc.
        Icon::fromArray(
            [
                'id'            => 7,
                'type'          => ICON,
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
        );
    }


    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":7,"type":5,"label":null,"labelPosition":"up","isLinkEnabled":true,"isOnTop":false,"parentId":null,"aclGroupId":null,"width":0,"height":0,"x":-666,"y":76,"imageSrc":"image.jpg"}',
            Icon::fromArray(
                [
                    'id'            => 7,
                    'type'          => ICON,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                    'imageSrc'      => 'image.jpg',
                ]
            )
        );
    }


}
