<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\Items\Percentile;

/**
 * Test for the Visual Console Percentile Item model.
 */
class PercentileTest extends TestCase
{


    /**
     * Test if the instance is created using a valid data structure.
     *
     * @return void
     */
    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Percentile::class,
            Percentile::fromArray(
                [
                    'id'        => 3,
                    'type'      => PERCENTILE_BAR,
                    'width'     => '600',
                    'height'    => '500',
                    'maxTime'   => null,
                    'valueType' => 'value',
                    'value'     => '123ms',
                ]
            )
        );

        $this->assertInstanceOf(
            Percentile::class,
            Percentile::fromArray(
                [
                    'id'        => 14,
                    'type'      => PERCENTILE_BUBBLE,
                    'width'     => '600',
                    'height'    => '500',
                    'maxTime'   => 12800,
                    'valueType' => 'image',
                    'value'     => 'data:image;asdasoih==',
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
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelColor":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxValue":0,"minValue":null,"moduleId":null,"moduleName":null,"parentId":null,"percentileType":"progress-bar","type":3,"unit":null,"value":null,"valueType":"percent","width":0,"x":-666,"y":76}',
            (string) Percentile::fromArray(
                [
                    'id'            => 7,
                    'type'          => PERCENTILE_BAR,
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
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelColor":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxValue":0,"minValue":null,"moduleId":null,"moduleName":null,"parentId":null,"percentileType":"bubble","type":3,"unit":null,"value":null,"valueType":"percent","width":0,"x":-666,"y":76}',
            (string) Percentile::fromArray(
                [
                    'id'            => 7,
                    'type'          => PERCENTILE_BUBBLE,
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
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":null,"height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelColor":null,"labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxValue":0,"minValue":null,"moduleId":null,"moduleName":null,"parentId":null,"percentileType":"circular-progress-bar","type":3,"unit":null,"value":1,"valueType":"value","width":0,"x":-666,"y":76}',
            (string) Percentile::fromArray(
                [
                    'id'            => 7,
                    'type'          => CIRCULAR_PROGRESS_BAR,
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
                    'valueType'     => 'value',
                    'value'         => '1',
                ]
            )
        );

        $this->assertEquals(
            '{"aclGroupId":null,"agentId":null,"agentName":null,"color":"#FFF","height":0,"id":7,"isLinkEnabled":true,"isOnTop":false,"label":null,"labelColor":"#000","labelPosition":"up","linkedLayoutAgentId":null,"linkedLayoutId":null,"linkedLayoutStatusType":"default","maxValue":0,"minValue":null,"moduleId":null,"moduleName":null,"parentId":null,"percentileType":"circular-progress-bar","type":3,"unit":null,"value":80,"valueType":"percent","width":0,"x":-666,"y":76}',
            (string) Percentile::fromArray(
                [
                    'id'            => 7,
                    'type'          => CIRCULAR_PROGRESS_BAR,
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
                    'valueType'     => 'percent',
                    'value'         => '80',
                    'color'         => '#FFF',
                    'labelColor'    => '#000',
                ]
            )
        );
    }


}
