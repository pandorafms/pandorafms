<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Label;

/**
 * Test for the Visual Console label Item model.
 */
class LabelTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Label::class,
            Label::fromArray(
                [
                    'id'     => 3,
                    'type'   => LABEL,
                    'width'  => '600',
                    'height' => '500',
                    'label'  => 'test',
                ]
            )
        );
    }


    /**
     * Test if the instance is not created when using a invalid label.
     *
     * @return void
     */
    public function testCannotBeCreatedWithInvalidLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid id.
        Label::fromArray(
            [
                'id'     => 3,
                'type'   => LABEL,
                'width'  => '600',
                'height' => '500',
                'label'  => null,
            ]
        );
        // Missing id.
        Label::fromArray(
            [
                'id'     => 3,
                'type'   => LABEL,
                'width'  => '600',
                'height' => '500',
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
            '{"aclGroupId":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"test","labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","parentId":null,"type":4,"width":0,"x":-666,"y":76}',
            (string) Label::fromArray(
                [
                    'id'            => 7,
                    'type'          => LABEL,
                    'label'         => 'test',
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
            '{"aclGroupId":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"test_pandora","labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","parentId":null,"type":4,"width":0,"x":-666,"y":76}',
            (string) Label::fromArray(
                [
                    'id'               => 7,
                    'type'             => LABEL,
                    'label'            => 'test_pandora',
                    'labelPosition'    => 'up',
                    'isLinkEnabled'    => true,
                    'isOnTop'          => false,
                    'parentId'         => null,
                    'width'            => '0',
                    'height'           => '0',
                    'x'                => -666,
                    'y'                => 76,
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"test_pandora","labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"parentId":null,"type":4,"width":0,"x":-666,"y":76}',
            (string) Label::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => LABEL,
                    'label'                 => 'test_pandora',
                    'labelPosition'         => 'up',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                ]
            )
        );
    }


}
