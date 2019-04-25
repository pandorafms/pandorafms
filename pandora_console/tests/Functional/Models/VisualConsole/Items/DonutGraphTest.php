<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\DonutGraph;

/**
 * Test for the Visual Console Donut Graph Item model.
 */
class DonutGraphTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            DonutGraph::class,
            DonutGraph::fromArray(
                [
                    'id'                    => 3,
                    'type'                  => DONUT_GRAPH,
                    'width'                 => '600',
                    'height'                => '500',
                    'legendBackgroundColor' => '#33CCFF',
                    'html'                  => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertInstanceOf(
            DonutGraph::class,
            DonutGraph::fromArray(
                [
                    'id'           => 14,
                    'type'         => DONUT_GRAPH,
                    'width'        => '600',
                    'height'       => '500',
                    'border_color' => '#000000',
                    'encodedHtml'  => 'PGgxPkZvbzwvaDE+',
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","legendBackgroundColor":"#33CCFF","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":17,"width":0,"x":-666,"y":76}',
            (string) DonutGraph::fromArray(
                [
                    'id'            => 7,
                    'type'          => DONUT_GRAPH,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                    'border_color'  => '#33CCFF',
                    'html'          => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"left","legendBackgroundColor":"#000000","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"type":17,"width":0,"x":-666,"y":76}',
            (string) DonutGraph::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => DONUT_GRAPH,
                    'label'                 => null,
                    'labelPosition'         => 'left',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'legendBackgroundColor' => '#000000',
                    'encodedHtml'           => 'PGgxPkZvbzwvaDE+',
                ]
            )
        );
    }


}
