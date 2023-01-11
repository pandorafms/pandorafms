<?php

namespace Test\Options;

use Artica\PHPChartJS\Options\Tooltips;
use PHPUnit\Framework\TestCase;
use Test\TestUtils;

/**
 * Class TooltipsTest
 *
 * @package Test\Options
 */
class TooltipsTest extends TestCase
{
    /**
     * @var Tooltips
     */
    private $tooltips;

    /**
     * @var array
     */
    public $data_types = [
        'enabled'            => false, /* bool */
        'custom'             => '', /* Expr */
        'mode'               => '', /* string */
        'intersect'          => false, /* bool */
        'position'           => '', /* string */
        'itemSort'           => '', /* Expr */
        'filter'             => '', /* Expr */
        'backgroundColor'    => '', /* string */
        'titleFontFamily'    => '', /* string */
        'titleFontSize'      => 1, /* int */
        'titleFontStyle'     => '', /* string */
        'titleFontColor'     => '', /* string */
        'titleSpacing'       => '', /* int */
        'titleMarginBottom'  => '', /* int */
        'bodyFontFamily'     => '', /* string */
        'bodyFontSize'       => '', /* int */
        'bodyFontStyle'      => '', /* string */
        'bodyFontColor'      => '', /* string */
        'bodySpacing'        => '', /* int */
        'footerFontFamily'   => '', /* string */
        'footerFontSize'     => 1, /* int */
        'footerFontStyle'    => '', /* string */
        'footerFontColor'    => '', /* string */
        'footerSpacing'      => 1, /* int */
        'footerMarginTop'    => 1, /* int */
        'xPadding'           => 1, /* int */
        'yPadding'           => 1, /* int */
        'caretSize'          => 1, /* int */
        'cornerRadius'       => 1, /* int */
        'multiKeyBackground' => '', /* string */
        'displayColors'      => false, /* bool */
    ];

    /**
     * @var array
     */
    public $input_data = [
        'enabled'            => true, /* bool */
        'custom'             => 'custom', /* Expr */
        'mode'               => 'mode', /* string */
        'intersect'          => true, /* bool */
        'position'           => 'position', /* string */
        'itemSort'           => 'itemSort', /* Expr */
        'filter'             => 'filter', /* Expr */
        'backgroundColor'    => 'backgroundColor', /* string */
        'titleFontFamily'    => 'titleFontFamily', /* string */
        'titleFontSize'      => 2, /* int */
        'titleFontStyle'     => 'titleFontStyle', /* string */
        'titleFontColor'     => 'titleFontColor', /* string */
        'titleSpacing'       => 3, /* int */
        'titleMarginBottom'  => 4, /* int */
        'bodyFontFamily'     => 'bodyFontFamily', /* string */
        'bodyFontSize'       => 5, /* int */
        'bodyFontStyle'      => 'bodyFontStyle', /* string */
        'bodyFontColor'      => 'bodyFontColor', /* string */
        'bodySpacing'        => 6, /* int */
        'footerFontFamily'   => 'footerFontFamily', /* string */
        'footerFontSize'     => 7, /* int */
        'footerFontStyle'    => 'footerFontStyle', /* string */
        'footerFontColor'    => 'footerFontColor', /* string */
        'footerSpacing'      => 8, /* int */
        'footerMarginTop'    => 9, /* int */
        'xPadding'           => 10, /* int */
        'yPadding'           => 11, /* int */
        'caretSize'          => 12, /* int */
        'cornerRadius'       => 13, /* int */
        'multiKeyBackground' => 'multiKeyBackground', /* string */
        'displayColors'      => true, /* bool */
    ];

    /**
     * @var array
     */
    public $input_data_no_expressions = [
        'enabled'            => true, /* bool */
        'custom'             => null, /* Expr */
        'mode'               => 'mode', /* string */
        'intersect'          => true, /* bool */
        'position'           => 'position', /* string */
        'itemSort'           => null, /* Expr */
        'filter'             => null, /* Expr */
        'backgroundColor'    => 'backgroundColor', /* string */
        'titleFontFamily'    => 'titleFontFamily', /* string */
        'titleFontSize'      => 2, /* int */
        'titleFontStyle'     => 'titleFontStyle', /* string */
        'titleFontColor'     => 'titleFontColor', /* string */
        'titleSpacing'       => 3, /* int */
        'titleMarginBottom'  => 4, /* int */
        'bodyFontFamily'     => 'bodyFontFamily', /* string */
        'bodyFontSize'       => 5, /* int */
        'bodyFontStyle'      => 'bodyFontStyle', /* string */
        'bodyFontColor'      => 'bodyFontColor', /* string */
        'bodySpacing'        => 6, /* int */
        'footerFontFamily'   => 'footerFontFamily', /* string */
        'footerFontSize'     => 7, /* int */
        'footerFontStyle'    => 'footerFontStyle', /* string */
        'footerFontColor'    => 'footerFontColor', /* string */
        'footerSpacing'      => 8, /* int */
        'footerMarginTop'    => 9, /* int */
        'xPadding'           => 10, /* int */
        'yPadding'           => 11, /* int */
        'caretSize'          => 12, /* int */
        'cornerRadius'       => 13, /* int */
        'multiKeyBackground' => 'multiKeyBackground', /* string */
        'displayColors'      => true, /* bool */
    ];

    /**
     * @var array
     */
    public $empty_data = [
        'enabled'            => null, /* bool */
        'custom'             => null, /* Expr */
        'mode'               => null, /* string */
        'intersect'          => null, /* bool */
        'position'           => null, /* string */
        'itemSort'           => null, /* Expr */
        'filter'             => null, /* Expr */
        'backgroundColor'    => null, /* string */
        'titleFontFamily'    => null, /* string */
        'titleFontSize'      => null, /* int */
        'titleFontStyle'     => null, /* string */
        'titleFontColor'     => null, /* string */
        'titleSpacing'       => null, /* int */
        'titleMarginBottom'  => null, /* int */
        'bodyFontFamily'     => null, /* string */
        'bodyFontSize'       => null, /* int */
        'bodyFontStyle'      => null, /* string */
        'bodyFontColor'      => null, /* string */
        'bodySpacing'        => null, /* int */
        'footerFontFamily'   => null, /* string */
        'footerFontSize'     => null, /* int */
        'footerFontStyle'    => null, /* string */
        'footerFontColor'    => null, /* string */
        'footerSpacing'      => null, /* int */
        'footerMarginTop'    => null, /* int */
        'xPadding'           => null, /* int */
        'yPadding'           => null, /* int */
        'caretSize'          => null, /* int */
        'cornerRadius'       => null, /* int */
        'multiKeyBackground' => null, /* string */
        'displayColors'      => null, /* bool */
    ];

    /**
     *
     */
    public function setUp(): void
    {
        $this->tooltips = new Tooltips();
    }

    /**
     *
     */
    public function testEmpty()
    {
        $expected = $this->empty_data;
        $result   = TestUtils::getAttributes($this->tooltips, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testGetAndSet()
    {
        $expected = $this->input_data;
        TestUtils::setAttributes($this->tooltips, $this->input_data);
        $result = TestUtils::getAttributes($this->tooltips, $this->data_types);
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testCallbackInstance()
    {
        $result = $this->tooltips->callbacks();
        self::assertInstanceOf(Tooltips\Callbacks::class, $result);
    }

    /**
     *
     */
    public function testJsonSerializeNoExpressions()
    {
        $expected = TestUtils::removeNullsFromArray($this->input_data_no_expressions);
        TestUtils::setAttributes($this->tooltips, $expected);
        $result = $this->tooltips->jsonSerialize();
        self::assertSame($expected, $result);
    }

    /**
     *
     */
    public function testCallbackEmpty()
    {
        $expected = new Tooltips\Callbacks();
        $result   = $this->tooltips->callbacks();
        self::assertEquals($expected, $result);
    }
}
