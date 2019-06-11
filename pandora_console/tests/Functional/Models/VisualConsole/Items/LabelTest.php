<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Label;

/**
 * Test for the Visual Console Label Item model.
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
                    'id'     => 69,
                    'type'   => LABEL,
                    'width'  => '0',
                    'height' => '0',
                    'label'  => 'Label',
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
        // Missing label.
        Label::fromArray(
            [
                'id'            => 7,
                'type'          => LABEL,
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
        // Empty label.
        Label::fromArray(
            [
                'id'            => 7,
                'type'          => LABEL,
                'label'         => '',
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
            '{"aclGroupId":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"Label","labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","parentId":null,"type":4,"width":0,"x":-666,"y":76}',
            (string) Label::fromArray(
                [
                    'id'            => 7,
                    'type'          => LABEL,
                    'label'         => 'Label',
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

        // With a linked layout.
        $this->assertEquals(
            '{"aclGroupId":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"Label","labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","parentId":null,"type":4,"width":0,"x":-666,"y":76}',
            (string) Label::fromArray(
                [
                    'id'               => 7,
                    'type'             => LABEL,
                    'label'            => 'Label',
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

    }


}
