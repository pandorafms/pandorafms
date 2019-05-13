<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\BarsGraph;

/**
 * Test for the Visual Console Bars Graph Item model.
 */
class BarsGraphTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            BarsGraph::class,
            BarsGraph::fromArray(
                [
                    'id'              => 7,
                    'type'            => BARS_GRAPH,
                    'width'           => '600',
                    'height'          => '500',
                    'typeGraph'       => 'horizontal',
                    'backgroundColor' => 'white',
                    'gridColor'       => '#33CCFF',
                    'encodedHtml'     => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertInstanceOf(
            BarsGraph::class,
            BarsGraph::fromArray(
                [
                    'id'           => 23,
                    'type'         => BARS_GRAPH,
                    'width'        => '800',
                    'height'       => '600',
                    'type_graph'   => 'vertical',
                    'image'        => 'transparent',
                    'border_color' => '#33CCFF',
                    'html'         => '<h1>Foo</h1>',
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"backgroundColor":"transparent","encodedHtml":"PGgxPkZvbzwvaDE+","gridColor":"#33CCFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","moduleId":null,"moduleName":null,"parentId":null,"type":18,"typeGraph":"vertical","width":0,"x":-666,"y":76}',
            (string) BarsGraph::fromArray(
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
                    'type_graph'    => 'vertical',
                    'image'         => 'transparent',
                    'border_color'  => '#33CCFF',
                    'html'          => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"backgroundColor":"white","encodedHtml":"PGgxPkZvbzwvaDE+","gridColor":"#33CCFF","height":300,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":"test","labelPosition":"left","moduleId":null,"moduleName":null,"parentId":null,"type":18,"typeGraph":"horizontal","width":300,"x":-666,"y":76}',
            (string) BarsGraph::fromArray(
                [
                    'id'              => 7,
                    'type'            => DONUT_GRAPH,
                    'label'           => 'test',
                    'labelPosition'   => 'left',
                    'isLinkEnabled'   => true,
                    'isOnTop'         => false,
                    'parentId'        => null,
                    'width'           => '300',
                    'height'          => '300',
                    'x'               => -666,
                    'y'               => 76,
                    'typeGraph'       => 'horizontal',
                    'backgroundColor' => 'white',
                    'gridColor'       => '#33CCFF',
                    'encodedHtml'     => 'PGgxPkZvbzwvaDE+',
                ]
            )
        );
    }


}
