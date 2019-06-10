<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\EventsHistory;

/**
 * Test for the Visual Console events history Item model.
 */
class EventsHistoryTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            EventsHistory::class,
            EventsHistory::fromArray(
                [
                    'id'      => 3,
                    'type'    => AUTO_SLA_GRAPH,
                    'width'   => '600',
                    'height'  => '500',
                    'maxTime' => null,
                    'html'    => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertInstanceOf(
            EventsHistory::class,
            EventsHistory::fromArray(
                [
                    'id'          => 14,
                    'type'        => AUTO_SLA_GRAPH,
                    'width'       => '600',
                    'height'      => '500',
                    'maxTime'     => 12800,
                    'encodedHtml' => 'PGgxPkZvbzwvaDE+',
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxTime":null,"moduleId":null,"moduleName":null,"parentId":null,"type":14,"width":0,"x":-666,"y":76}',
            (string) EventsHistory::fromArray(
                [
                    'id'            => 7,
                    'type'          => AUTO_SLA_GRAPH,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                    'maxTime'       => null,
                    'html'          => '<h1>Foo</h1>',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxTime":12800,"moduleId":null,"moduleName":null,"parentId":null,"type":14,"width":0,"x":-666,"y":76}',
            (string) EventsHistory::fromArray(
                [
                    'id'            => 7,
                    'type'          => AUTO_SLA_GRAPH,
                    'label'         => null,
                    'labelPosition' => 'up',
                    'isLinkEnabled' => true,
                    'isOnTop'       => false,
                    'parentId'      => null,
                    'width'         => '0',
                    'height'        => '0',
                    'x'             => -666,
                    'y'             => 76,
                    'maxTime'       => 12800,
                    'encodedHtml'   => 'PGgxPkZvbzwvaDE+',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","maxTime":null,"moduleId":null,"moduleName":null,"parentId":null,"type":14,"width":0,"x":-666,"y":76}',
            (string) EventsHistory::fromArray(
                [
                    'id'               => 7,
                    'type'             => AUTO_SLA_GRAPH,
                    'label'            => null,
                    'labelPosition'    => 'up',
                    'isLinkEnabled'    => true,
                    'isOnTop'          => false,
                    'parentId'         => null,
                    'width'            => '0',
                    'height'           => '0',
                    'x'                => -666,
                    'y'                => 76,
                    'maxTime'          => null,
                    'encodedHtml'      => 'PGgxPkZvbzwvaDE+',
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","maxTime":12800,"metaconsoleId":5,"moduleId":null,"moduleName":null,"parentId":null,"type":14,"width":0,"x":-666,"y":76}',
            (string) EventsHistory::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => AUTO_SLA_GRAPH,
                    'label'                 => null,
                    'labelPosition'         => 'up',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'maxTime'               => 12800,
                    'encodedHtml'           => 'PGgxPkZvbzwvaDE+',
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":21,"agentName":null,"encodedHtml":"PGgxPkZvbzwvaDE+","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelPosition":"up","linkedLayoutAgentId":15,"linkedLayoutId":3,"linkedLayoutStatusType":"default","maxTime":12800,"metaconsoleId":2,"moduleId":385,"moduleName":"module_test","parentId":null,"type":14,"width":0,"x":-666,"y":76}',
            (string) EventsHistory::fromArray(
                [
                    'id'                    => 7,
                    'type'                  => AUTO_SLA_GRAPH,
                    'label'                 => null,
                    'labelPosition'         => 'up',
                    'isLinkEnabled'         => true,
                    'isOnTop'               => false,
                    'parentId'              => null,
                    'width'                 => '0',
                    'height'                => '0',
                    'x'                     => -666,
                    'y'                     => 76,
                    'maxTime'               => 12800,
                    'encodedHtml'           => 'PGgxPkZvbzwvaDE+',
                    'id_metaconsole'        => 2,
                    'linked_layout_node_id' => 15,
                    'linkedLayoutId'        => 3,
                    'agentId'               => 21,
                    'moduleId'              => 385,
                    'moduleName'            => 'module_test',
                ]
            )
        );
    }


}
