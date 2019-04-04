<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\StaticGraph;

/**
 * Test for the Visual Console static graph Item model.
 */
class StaticGraphTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            StaticGraph::class,
            StaticGraph::fromArray(
                [
                    'id'                   => 345,
                    'type'                 => STATIC_GRAPH,
                    'label'                => null,
                    'labelPosition'        => 'up',
                    'isLinkEnabled'        => true,
                    'isOnTop'              => false,
                    'parentId'             => null,
                    'width'                => '0',
                    'height'               => '0',
                    'x'                    => -666,
                    'y'                    => 76,
                    'imageSrc'             => 'aaaaa',
                    'showLastValueTooltip' => 'enabled',
                ]
            )
        );

        $this->assertInstanceOf(
            StaticGraph::class,
            StaticGraph::fromArray(
                [
                    'id'              => 1000,
                    'type'            => STATIC_GRAPH,
                    'width'           => 100,
                    'height'          => 900,
                    'image'           => 'test.jpg',
                    'show_last_value' => 2,
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"showLastValueTooltip":"default","statusImageSrc":null,"type":0,"width":0,"x":-666,"y":76}',
            (string) StaticGraph::fromArray(
                [
                    'id'            => 7,
                    'type'          => STATIC_GRAPH,
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

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"showLastValueTooltip":"disabled","statusImageSrc":null,"type":0,"width":0,"x":-666,"y":76}',
            (string) StaticGraph::fromArray(
                [
                    'id'                   => 7,
                    'type'                 => STATIC_GRAPH,
                    'label'                => null,
                    'labelPosition'        => 'up',
                    'isLinkEnabled'        => true,
                    'isOnTop'              => false,
                    'parentId'             => null,
                    'width'                => '0',
                    'height'               => '0',
                    'x'                    => -666,
                    'y'                    => 76,
                    'image'                => 'image.jpg',
                    'showLastValueTooltip' => 'disabled',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"moduleId":null,"moduleName":null,"parentId":null,"showLastValueTooltip":"default","statusImageSrc":"image.bad.jpg","type":0,"width":0,"x":-666,"y":76}',
            (string) StaticGraph::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => STATIC_GRAPH,
                    'label'                 => null,
                    'labelPosition'         => 'up',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'imageSrc'              => 'image.jpg',
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                    'statusImageSrc'        => 'image.bad.jpg',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"showLastValueTooltip":"default","statusImageSrc":null,"type":0,"width":0,"x":-666,"y":76}',
            (string) StaticGraph::fromArray(
                [
                    'id'               => 7,
                    'type'             => STATIC_GRAPH,
                    'label'            => null,
                    'labelPosition'    => 'up',
                    'isLinkEnabled'    => true,
                    'isOnTop'          => false,
                    'parentId'         => null,
                    'width'            => '0',
                    'height'           => '0',
                    'x'                => -666,
                    'y'                => 76,
                    'image'            => 'image.jpg',
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":2,"linkedLayoutStatusType":"service","linkedLayoutStatusTypeCriticalThreshold":80,"linkedLayoutStatusTypeWarningThreshold":50,"moduleId":null,"moduleName":null,"parentId":null,"showLastValueTooltip":"default","statusImageSrc":"image.bad.jpg","type":0,"width":0,"x":-666,"y":76}',
            (string) StaticGraph::fromArray(
                [
                    'id'                                       => 7,
                    'type'                                     => STATIC_GRAPH,
                    'label'                                    => null,
                    'labelPosition'                            => 'up',
                    'isLinkEnabled'                            => true,
                    'isOnTop'                                  => false,
                    'parentId'                                 => null,
                    'width'                                    => '0',
                    'height'                                   => '0',
                    'x'                                        => -666,
                    'y'                                        => 76,
                    'image'                                    => 'image.jpg',
                    'linkedLayoutId'                           => 2,
                    'linked_layout_status_type'                => 'service',
                    'linkedLayoutStatusTypeWarningThreshold'   => 50,
                    'linked_layout_status_as_service_critical' => 80,
                    'statusImageSrc'                           => 'image.bad.jpg',
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid image src.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidImageSrc(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid imageSrc.
        StaticGraph::fromArray(
            [
                'id'                   => 3,
                'type'                 => STATIC_GRAPH,
                'label'                => null,
                'isLinkEnabled'        => true,
                'isOnTop'              => false,
                'parentId'             => null,
                'width'                => '330',
                'height'               => '0',
                'x'                    => 511,
                'y'                    => 76,
                'imageSrc'             => 45,
                'showLastValueTooltip' => 'disabled',
            ]
        );

        // Missing imageSrc.
        StaticGraph::fromArray(
            [
                'id'                   => 3,
                'type'                 => STATIC_GRAPH,
                'label'                => null,
                'isLinkEnabled'        => true,
                'isOnTop'              => false,
                'parentId'             => null,
                'width'                => '330',
                'height'               => '0',
                'x'                    => 511,
                'y'                    => 76,
                'showLastValueTooltip' => 'enabled',
            ]
        );
    }


}
