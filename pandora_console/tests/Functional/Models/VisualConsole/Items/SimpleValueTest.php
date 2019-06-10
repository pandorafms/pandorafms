<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\SimpleValue;

/**
 * Test for the Visual Console Simple Value Item model.
 */
class SimpleValueTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            SimpleValue::class,
            SimpleValue::fromArray(
                [
                    'id'           => 3,
                    'type'         => SIMPLE_VALUE,
                    'width'        => '600',
                    'height'       => '500',
                    'valueType'    => 'string',
                    'value'        => 57,
                    'processValue' => 'avg',
                    'period'       => 12800,
                ]
            )
        );

        $this->assertInstanceOf(
            SimpleValue::class,
            SimpleValue::fromArray(
                [
                    'id'           => 14,
                    'type'         => SIMPLE_VALUE,
                    'width'        => '600',
                    'height'       => '500',
                    'valueType'    => 'image',
                    'value'        => 3598,
                    'processValue' => 'max',
                    'period'       => 9000,
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":500,"id":3,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"period":12800,"processValue":"avg","type":2,"value":57,"valueType":"string","width":600,"x":0,"y":0}',
            (string) SimpleValue::fromArray(
                [
                    'id'           => 3,
                    'type'         => SIMPLE_VALUE,
                    'width'        => '600',
                    'height'       => '500',
                    'valueType'    => 'string',
                    'value'        => 57,
                    'processValue' => 'avg',
                    'period'       => 12800,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":500,"id":3,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"processValue":"none","type":2,"value":57,"valueType":"string","width":600,"x":0,"y":0}',
            (string) SimpleValue::fromArray(
                [
                    'id'        => 3,
                    'type'      => SIMPLE_VALUE,
                    'width'     => '600',
                    'height'    => '500',
                    'valueType' => 'string',
                    'value'     => 57,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":500,"id":3,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":null,"linkedLayoutId":1,"linkedLayoutStatusType":"default","moduleId":null,"moduleName":null,"parentId":null,"processValue":"none","type":2,"value":57,"valueType":"string","width":600,"x":0,"y":0}',
            (string) SimpleValue::fromArray(
                [
                    'id'               => 3,
                    'type'             => SIMPLE_VALUE,
                    'width'            => '600',
                    'height'           => '500',
                    'valueType'        => 'string',
                    'value'            => 57,
                    'id_layout_linked' => 1,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"height":500,"id":3,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":3,"linkedLayoutId":2,"linkedLayoutStatusType":"default","metaconsoleId":5,"moduleId":null,"moduleName":null,"parentId":null,"processValue":"none","type":2,"value":57,"valueType":"string","width":600,"x":0,"y":0}',
            (string) SimpleValue::fromArray(
                [
                    'id'                    => 3,
                    'type'                  => SIMPLE_VALUE,
                    'width'                 => '600',
                    'height'                => '500',
                    'valueType'             => 'string',
                    'value'                 => 57,
                    'id_metaconsole'        => 5,
                    'linked_layout_node_id' => 3,
                    'linkedLayoutId'        => 2,
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":21,"agentName":null,"height":500,"id":3,"isLinkEnabled":false,"isOnTop":false,"label":null,"labelPosition":"down","linkedLayoutAgentId":15,"linkedLayoutId":3,"linkedLayoutStatusType":"default","metaconsoleId":2,"moduleId":385,"moduleName":"module_test","parentId":null,"processValue":"none","type":2,"value":57,"valueType":"string","width":600,"x":0,"y":0}',
            (string) SimpleValue::fromArray(
                [
                    'id'                    => 3,
                    'type'                  => SIMPLE_VALUE,
                    'width'                 => '600',
                    'height'                => '500',
                    'valueType'             => 'string',
                    'value'                 => 57,
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
