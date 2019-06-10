<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\ColorCloud;

/**
 * Test for the Visual Console color cloud item model.
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
                    'id'            => 345,
                    'type'          => COLOR_CLOUD,
                    'label'         => null,
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                    'defaultColor'  => '#FFF',
                    'colorRanges'   => [],
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
                    'label'       => 'eyJkZWZhdWx0X2NvbG9yIjoiI0ZGRiIsImNvbG9yX3JhbmdlcyI6W3siY29sb3IiOiIjMDAwIiwiZnJvbV92YWx1ZSI6MTAuMDUsInRvX3ZhbHVlIjoxMDAuMH1dfQ==',
                    'colorRanges' => [
                        [
                            'color'     => '#000',
                            'fromValue' => 10.05,
                            'toValue'   => 100.0,
                        ],
                    ],
                    'color'       => '#000',
                ]
            )
        );

        $this->assertInstanceOf(
            ColorCloud::class,
            ColorCloud::fromArray(
                [
                    'id'     => 1000,
                    'type'   => COLOR_CLOUD,
                    'width'  => 100,
                    'height' => 900,
                    'label'  => 'eyJkZWZhdWx0X2NvbG9yIjoiI0ZGRiIsImNvbG9yX3JhbmdlcyI6W3siY29sb3IiOiIjMDAwIiwiZnJvbV92YWx1ZSI6MTAuMDUsInRvX3ZhbHVlIjoxMDAuMH1dfQ==',
                ]
            )
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#000","colorRanges":[{"color":"#000","fromValue":10.05,"toValue":100}],"defaultColor":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'            => 7,
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
                    'defaultColor'  => '#FFF',
                    'colorRanges'   => [
                        [
                            'color'     => '#000',
                            'fromValue' => 10.05,
                            'toValue'   => 100.0,
                        ],
                    ],
                    'color'         => '#000',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":null,"colorRanges":[],"defaultColor":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'            => 7,
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
                    'defaultColor'  => '#FFF',
                    'colorRanges'   => [],
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#000","colorRanges":[{"color":"#000","fromValue":10.05,"toValue":100}],"defaultColor":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => COLOR_CLOUD,
                    'label'                 => 'eyJkZWZhdWx0X2NvbG9yIjoiI0ZGRiIsImNvbG9yX3JhbmdlcyI6W3siY29sb3IiOiIjMDAwIiwiZnJvbV92YWx1ZSI6MTAuMDUsInRvX3ZhbHVlIjoxMDAuMH1dfQ==',
                    'labelPosition'         => 'up',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'color'                 => '#000',
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#000","colorRanges":[{"color":"#000","fromValue":10.05,"toValue":100}],"defaultColor":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'               => 7,
                    'type'             => COLOR_CLOUD,
                    'label'            => 'eyJkZWZhdWx0X2NvbG9yIjoiI0ZGRiIsImNvbG9yX3JhbmdlcyI6W3siY29sb3IiOiIjMDAwIiwiZnJvbV92YWx1ZSI6MTAuMDUsInRvX3ZhbHVlIjoxMDAuMH1dfQ==',
                    'labelPosition'    => 'up',
                    'isLinkEnabled'    => true,
                    'isOnTop'          => false,
                    'parentId'         => null,
                    'width'            => '0',
                    'height'           => '0',
                    'x'                => -666,
                    'y'                => 76,
                    'defaultColor'     => '#FFF',
                    'color'            => '#000',
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#000","colorRanges":[{"color":"#000","fromValue":10.05,"toValue":100}],"defaultColor":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":2,"linkedLayoutStatusType":"service","linkedLayoutStatusTypeCriticalThreshold":80,"linkedLayoutStatusTypeWarningThreshold":50,"moduleId":null,"moduleName":null,"parentId":null,"type":20,"width":0,"x":-666,"y":76}',
            (string) ColorCloud::fromArray(
                [
                    'id'                                       => 7,
                    'type'                                     => COLOR_CLOUD,
                    'label'                                    => 'eyJkZWZhdWx0X2NvbG9yIjoiI0ZGRiIsImNvbG9yX3JhbmdlcyI6W3siY29sb3IiOiIjMDAwIiwiZnJvbV92YWx1ZSI6MTAuMDUsInRvX3ZhbHVlIjoxMDAuMH1dfQ==',
                    'labelPosition'                            => 'up',
                    'isLinkEnabled'                            => true,
                    'isOnTop'                                  => false,
                    'parentId'                                 => null,
                    'width'                                    => '0',
                    'height'                                   => '0',
                    'x'                                        => -666,
                    'y'                                        => 76,
                    'colorRanges'                              => [
                        [
                            'color'     => '#000',
                            'fromValue' => 10.05,
                            'toValue'   => 100.0,
                        ],
                    ],
                    'color'                                    => '#000',
                    'linkedLayoutId'                           => 2,
                    'linked_layout_status_type'                => 'service',
                    'linkedLayoutStatusTypeWarningThreshold'   => 50,
                    'linked_layout_status_as_service_critical' => 80,
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid dynamic data.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidDynamicData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid dynamic data.
        ColorCloud::fromArray(
            [
                'id'            => 3,
                'type'          => COLOR_CLOUD,
                'label'         => null,
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '330',
                'height'        => '0',
                'x'             => 511,
                'y'             => 76,
            ]
        );
        // Missing dynamic data.
        ColorCloud::fromArray(
            [
                'id'            => 3,
                'type'          => COLOR_CLOUD,
                'label'         => null,
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '330',
                'height'        => '0',
                'x'             => 511,
                'y'             => 76,
            ]
        );
    }


}
