<?php

namespace Test\Options\Tooltips;

use Artica\PHPChartJS\Options\Tooltips\Callbacks;
use Laminas\Json\Expr;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class CallbacksTest
 *
 * @package Test\Options\Tooltips
 */
class CallbacksTest extends TestCase
{
    /**
     * @var Callbacks
     */
    private $callbacks;

    /**
     * @var array
     */
    private $data_types = [
        'beforeTitle'  => '',
        'title'        => '',
        'afterTitle'   => '',
        'beforeLabel'  => '',
        'label'        => '',
        'labelColor'   => '',
        'afterLabel'   => '',
        'afterBody'    => '',
        'beforeFooter' => '',
        'footer'       => '',
        'afterFooter'  => '',
        'dataPoints'   => '',
    ];

    /**
     * @var array
     */
    private $input_data = [
        'beforeTitle'  => "function() { alert( 'Hello' ); }",
        'title'        => "function() { alert( 'Hello' ); }",
        'afterTitle'   => "function() { alert( 'Hello' ); }",
        'beforeLabel'  => "function() { alert( 'Hello' ); }",
        'label'        => "function() { alert( 'Hello' ); }",
        'labelColor'   => "function() { alert( 'Hello' ); }",
        'afterLabel'   => "function() { alert( 'Hello' ); }",
        'afterBody'    => "function() { alert( 'Hello' ); }",
        'beforeFooter' => "function() { alert( 'Hello' ); }",
        'footer'       => "function() { alert( 'Hello' ); }",
        'afterFooter'  => "function() { alert( 'Hello' ); }",
        'dataPoints'   => "function() { alert( 'Hello' ); }",
    ];

    /**
     * @var array
     */
    private $empty_data = [
        'beforeTitle'  => null,
        'title'        => null,
        'afterTitle'   => null,
        'beforeLabel'  => null,
        'label'        => null,
        'labelColor'   => null,
        'afterLabel'   => null,
        'afterBody'    => null,
        'beforeFooter' => null,
        'footer'       => null,
        'afterFooter'  => null,
        'dataPoints'   => null,
    ];

    private $initial_data = [];

    /**
     *
     */
    public function setUp(): void
    {
        $this->callbacks = new Callbacks();

        // Re-initialize Expr properties
        $keys = array_keys($this->empty_data);
        foreach ($keys as $key) {
            $this->initial_data[$key] = new Expr('');
        }
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->callbacks, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSet()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->callbacks, $this->input_data);
        $result = TestUtils::getAttributes($this->callbacks, $this->data_types);
        array_walk(
            $result,
            function (&$value) {
                $value = strval($value);
            }
        );
        self::assertSame($expected, $result);
    }

    public function testJsonSerializeEmpty()
    {
        $expected = [];
        $result   = $this->callbacks->jsonSerialize();
        self::assertSame($expected, $result);
    }
}
