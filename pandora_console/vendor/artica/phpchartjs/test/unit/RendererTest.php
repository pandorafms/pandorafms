<?php

namespace Test;

use Artica\PHPChartJS\Chart;
use Artica\PHPChartJS\Chart\Bar;
use Artica\PHPChartJS\DataSet;
use Artica\PHPChartJS\Renderer\Html;
use Artica\PHPChartJS\Renderer\Json;
use PHPUnit\Framework\TestCase;

/**
 * Class RendererTest
 *
 * @package Test
 */
class RendererTest extends TestCase
{
    /**
     * @var Chart
     */
    private $chart;

    /**
     *
     */
    public function setUp(): void
    {
        $chart = new Bar();
        $chart->setId('myChart')
              ->addLabel('Label 1')->addLabel('Label 2')
              ->setTitle('My beautiful chart')
              ->setHeight(320)
              ->setWidth(480);

        /** @var DataSet $dataSet */
        $chart->addDataSet($dataSet = $chart->createDataSet());
        $dataSet->setLabel('My First Dataset');

        $chart->options()->getTitle()->setText('My cool graph');
        $chart->options()->getLegend()->setDisplay(false);

        $this->chart = $chart;
    }

    /**
     * Test and validate generated JSON. See http://www.ietf.org/rfc/rfc4627.txt and http://json.org/.
     * JavaScript functions will not be recognized and will result in failures of this test.
     */
    public function testJson()
    {
        $renderer = new Json($this->chart);
        $json     = $renderer->render();
        $regex    = <<<REGEX
/(?(DEFINE)
 (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )    
 (?<boolean>   true | false | null )
 (?<string>    " ([^"\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
 (?<array>     \[  (?:  (?&json)  (?: , (?&json)  )*  )?  \s* \] )
 (?<pair>      \s* (?&string) \s* : (?&json)  )
 (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
 (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) \s* )
)
\A (?&json) \Z/six
REGEX;

        $result = preg_match($regex, $json, $matches);

        $this->assertEquals(1, $result, 'Validate JSON output');
    }

    /**
     *
     */
    public function testHtml()
    {
        $renderer = new Html($this->chart);

        $this->assertTrue(is_string($renderer->render()), 'Validate HTML output');
    }
}
