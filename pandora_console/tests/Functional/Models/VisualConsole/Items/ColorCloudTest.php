<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\ColorCloud;

/**
 * Test for the Visual Console color cloud Item model.
 */
class ColorCloudTest extends TestCase
{


     /**
      * Test if the instance is created using a valid data structure.
      *
      * @return void
      */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            ColorCloud::class,
            ColorCloud::fromArray(
                [
                    'id'            => 69,
                    'type'          => COLOR_CLOUD,
                    'label'         => '{"default_color":"#47b042","color_ranges":[{"from_value":20,"to_value":60,"color":"#d0da27"},{"from_value":61,"to_value":100,"color":"#ec1f1f"}]}',
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
            ColorCloud::class,
            ColorCloud::fromArray(
                [
                    'id'          => 1000,
                    'type'        => COLOR_CLOUD,
                    'width'       => 100,
                    'height'      => 900,
                    'color'       => '#47b042',
                    'colorRanges' => [
                        [
                            'fromValue' => 50,
                            'toValue'   => 90,
                            'color'     => '#d0da27',
                        ],
                        [
                            'fromValue' => 910,
                            'toValue'   => 100,
                            'color'     => '#ec1f1f',
                        ],
                    ],
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid color.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid color.
        ColorCloud::fromArray(
            [
                'id'            => 69,
                'type'          => COLOR_CLOUD,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'color'         => '',
            ]
        );

        // Invalid color.
        ColorCloud::fromArray(
            [
                'id'            => 69,
                'type'          => COLOR_CLOUD,
                'label'         => '{"default_col":"#47b042","color_ranges":[]}',
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

        // Missing color.
        ColorCloud::fromArray(
            [
                'id'            => 69,
                'type'          => COLOR_CLOUD,
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


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#47b042","colorRanges":[{"fromValue":20,"toValue":60,"color":"#d0da27"},{"fromValue":61,"toValue":100,"color":"#ec1f1f"}],"height":0,"id":69,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'            => 69,
                    'type'          => COLOR_CLOUD,
                    'label'         => '{"default_color":"#47b042","color_ranges":[{"from_value":20,"to_value":60,"color":"#d0da27"},{"from_value":61,"to_value":100,"color":"#ec1f1f"}]}',
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

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#47b042","colorRanges":[{"fromValue":50,"toValue":90,"color":"#d0da27"},{"fromValue":910,"toValue":100,"color":"#ec1f1f"}],"height":900,"id":1000,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":100,"x":0,"y":0}',
            (string) ColorCloud::fromArray(
                [
                    'id'          => 1000,
                    'type'        => COLOR_CLOUD,
                    'width'       => 100,
                    'height'      => 900,
                    'color'       => '#47b042',
                    'colorRanges' => [
                        [
                            'fromValue' => 50,
                            'toValue'   => 90,
                            'color'     => '#d0da27',
                        ],
                        [
                            'fromValue' => 910,
                            'toValue'   => 100,
                            'color'     => '#ec1f1f',
                        ],
                    ],
                ]
            )
        );
    }


}
