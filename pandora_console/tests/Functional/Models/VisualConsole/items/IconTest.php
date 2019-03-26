<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Icon;

/**
 * Test for the Visual Console Box Icon Item model.
 */
class IconTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
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


    /**
     * Test if the instance is not created when using a invalid image src.
     *
     * @return void
     */
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


    /**
     * Test if the model has a valid JSON representation.
     *
     * @return void
     */
    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","parentId":null,"type":5,"width":0,"x":-666,"y":76}',
            (string) Icon::fromArray(
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

        // With a linked layout.
        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","parentId":null,"type":5,"width":0,"x":-666,"y":76}',
            (string) Icon::fromArray(
                [
                    'id'               => 7,
                    'type'             => ICON,
                    'label'            => null,
                    'labelPosition'    => 'up',
                    'isLinkEnabled'    => true,
                    'isOnTop'          => false,
                    'parentId'         => null,
                    'width'            => '0',
                    'height'           => '0',
                    'x'                => -666,
                    'y'                => 76,
                    'imageSrc'         => 'image.jpg',
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"parentId":null,"type":5,"width":0,"x":-666,"y":76}',
            (string) Icon::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => ICON,
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
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"weight","linkedLayoutStatusTypeWeight":80,"metaconsoleId":5,"parentId":null,"type":5,"width":0,"x":-666,"y":76}',
            (string) Icon::fromArray(
                [
                    'id'                           => 7,
                    'type'                         => ICON,
                    'label'                        => null,
                    'labelPosition'                => 'up',
                    'isLinkEnabled'                => true,
                    'isOnTop'                      => false,
                    'parentId'                     => null,
                    'width'                        => '0',
                    'height'                       => '0',
                    'x'                            => -666,
                    'y'                            => 76,
                    'imageSrc'                     => 'image.jpg',
                    'id_metaconsole'               => 5,
                    'linked_layout_node_id'        => 3,
                    'linkedLayoutId'               => 2,
                    'linkedLayoutStatusType'       => 'weight',
                    'linkedLayoutStatusTypeWeight' => 80,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"service","linkedLayoutStatusTypeCriticalThreshold":80,"linkedLayoutStatusTypeWarningThreshold":50,"metaconsoleId":5,"parentId":null,"type":5,"width":0,"x":-666,"y":76}',
            (string) Icon::fromArray(
                [
                    'id'                                       => 7,
                    'type'                                     => ICON,
                    'label'                                    => null,
                    'labelPosition'                            => 'up',
                    'isLinkEnabled'                            => true,
                    'isOnTop'                                  => false,
                    'parentId'                                 => null,
                    'width'                                    => '0',
                    'height'                                   => '0',
                    'x'                                        => -666,
                    'y'                                        => 76,
                    'imageSrc'                                 => 'image.jpg',
                    'id_metaconsole'                           => 5,
                    'linked_layout_node_id'                    => 3,
                    'linkedLayoutId'                           => 2,
                    'linked_layout_status_type'                => 'service',
                    'linkedLayoutStatusTypeWarningThreshold'   => 50,
                    'linked_layout_status_as_service_critical' => 80,
                ]
            )
        );

    }


}
