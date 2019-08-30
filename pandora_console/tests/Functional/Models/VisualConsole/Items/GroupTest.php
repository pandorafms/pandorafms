<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Group;

/**
 * Test for the Visual Console Box Group Item model.
 */
class GroupTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Group::class,
            Group::fromArray(
                [
                    'id'             => 13,
                    'type'           => GROUP_ITEM,
                    'width'          => '600',
                    'height'         => '500',
                    'imageSrc'       => 'image.jpg',
                    'groupId'        => 12,
                    'statusImageSrc' => 'image.bad.jpg',
                ]
            )
        );

        $this->assertInstanceOf(
            Group::class,
            Group::fromArray(
                [
                    'id'             => 1004,
                    'type'           => GROUP_ITEM,
                    'width'          => '600',
                    'height'         => '500',
                    'image'          => 'test_image.png',
                    'id_group'       => 0,
                    'statusImageSrc' => 'test_image.bad.jpg',
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
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
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
                'groupId'       => 0,
            ]
        );
        // Missing imageSrc.
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'id_group'      => 11,
            ]
        );
    }


    /**
     * Test if the instance is not created when using a invalid group Id.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidGroupId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid groupId.
        Group::fromArray(
            [
                'id'             => 7,
                'type'           => GROUP_ITEM,
                'label'          => null,
                'labelPosition'  => 'up',
                'isLinkEnabled'  => true,
                'isOnTop'        => false,
                'parentId'       => null,
                'width'          => '0',
                'height'         => '0',
                'x'              => -666,
                'y'              => 76,
                'imageSrc'       => 'test.jpg',
                'groupId'        => 'bar',
                'statusImageSrc' => 'image.bad.jpg',
            ]
        );
        // Missing groupId.
        Group::fromArray(
            [
                'id'             => 7,
                'type'           => GROUP_ITEM,
                'label'          => null,
                'labelPosition'  => 'up',
                'isLinkEnabled'  => true,
                'isOnTop'        => false,
                'parentId'       => null,
                'width'          => '0',
                'height'         => '0',
                'x'              => -666,
                'y'              => 76,
                'imageSrc'       => 'test.jpg',
                'statusImageSrc' => 'image.bad.jpg',
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
            '{"aclGroupId":null,"groupId":12,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","parentId":null,"statusImageSrc":"image.bad.jpg","type":11,"width":0,"x":-666,"y":76}',
            (string) Group::fromArray(
                [
                    'id'             => 7,
                    'type'           => GROUP_ITEM,
                    'label'          => null,
                    'labelPosition'  => 'up',
                    'isLinkEnabled'  => true,
                    'isOnTop'        => false,
                    'parentId'       => null,
                    'width'          => '0',
                    'height'         => '0',
                    'x'              => -666,
                    'y'              => 76,
                    'imageSrc'       => 'image.jpg',
                    'groupId'        => 12,
                    'statusImageSrc' => 'image.bad.jpg',
                ]
            )
        );

        // With a linked layout.
        $this->assertEquals(
            '{"aclGroupId":null,"groupId":12,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","parentId":null,"statusImageSrc":"image.bad.jpg","type":11,"width":0,"x":-666,"y":76}',
            (string) Group::fromArray(
                [
                    'id'               => 7,
                    'type'             => GROUP_ITEM,
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
                    'groupId'          => 12,
                    'statusImageSrc'   => 'image.bad.jpg',
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"groupId":12,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"parentId":null,"statusImageSrc":"image.bad.jpg","type":11,"width":0,"x":-666,"y":76}',
            (string) Group::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => GROUP_ITEM,
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
                    'groupId'               => 12,
                    'statusImageSrc'        => 'image.bad.jpg',
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                ]
            )
        );
        $this->assertEquals(
            '{"aclGroupId":null,"groupId":12,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"weight","linkedLayoutStatusTypeWeight":80,"metaconsoleId":5,"parentId":null,"statusImageSrc":"image.bad.jpg","type":11,"width":0,"x":-666,"y":76}',
            (string) Group::fromArray(
                [
                    'id'                           => 7,
                    'type'                         => GROUP_ITEM,
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
                    'groupId'                      => 12,
                    'statusImageSrc'               => 'image.bad.jpg',
                    'id_metaconsole'               => 5,
                    'linked_layout_node_id'        => 3,
                    'linkedLayoutId'               => 2,
                    'linkedLayoutStatusType'       => 'weight',
                    'linkedLayoutStatusTypeWeight' => 80,
                ]
            )
        );
        $this->assertEquals(
            '{"aclGroupId":null,"groupId":12,"height":0,"id":7,"imageSrc":"image.jpg","isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":2,"linkedLayoutStatusType":"service","linkedLayoutStatusTypeCriticalThreshold":80,"linkedLayoutStatusTypeWarningThreshold":50,"parentId":null,"statusImageSrc":"image.bad.jpg","type":11,"width":0,"x":-666,"y":76}',
            (string) Group::fromArray(
                [
                    'id'                                       => 7,
                    'type'                                     => GROUP_ITEM,
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
                    'groupId'                                  => 12,
                    'statusImageSrc'                           => 'image.bad.jpg',
                    'linkedLayoutId'                           => 2,
                    'linked_layout_status_type'                => 'service',
                    'linkedLayoutStatusTypeWarningThreshold'   => 50,
                    'linked_layout_status_as_service_critical' => 80,
                ]
            )
        );
    }


}
