<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Tooltips\Callbacks;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class Tooltips
 *
 * @package Artica\PHPChartJS\Options
 */
class Tooltips implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Expr
     */
    private $custom;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var bool
     */
    private $intersect;

    /**
     * @var string
     */
    private $position;

    /**
     * @var Expr
     */
    private $itemSort;

    /**
     * @var Expr
     */
    private $filter;

    /**
     * @var string
     */
    private $backgroundColor;

    /**
     * @var string
     */
    private $titleFontFamily;

    /**
     * @var int
     */
    private $titleFontSize;

    /**
     * @var string
     */
    private $titleFontStyle;

    /**
     * @var string
     */
    private $titleFontColor;

    /**
     * @var int
     */
    private $titleSpacing;

    /**
     * @var int
     */
    private $titleMarginBottom;

    /**
     * @var string
     */
    private $bodyFontFamily;

    /**
     * @var int
     */
    private $bodyFontSize;

    /**
     * @var string
     */
    private $bodyFontStyle;

    /**
     * @var string
     */
    private $bodyFontColor;

    /**
     * @var int
     */
    private $bodySpacing;

    /**
     * @var string
     */
    private $footerFontFamily;

    /**
     * @var int
     */
    private $footerFontSize;

    /**
     * @var string
     */
    private $footerFontStyle;

    /**
     * @var string
     */
    private $footerFontColor;

    /**
     * @var int
     */
    private $footerSpacing;

    /**
     * @var int
     */
    private $footerMarginTop;

    /**
     * @var int
     */
    private $xPadding;

    /**
     * @var int
     */
    private $yPadding;

    /**
     * @var int
     */
    private $caretSize;

    /**
     * @var int
     */
    private $cornerRadius;

    /**
     * @var string
     */
    private $multiKeyBackground;

    /**
     * @var bool
     */
    private $displayColors;

    /**
     * @var Callbacks
     */
    private $callbacks;

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = boolval($enabled);

        return $this;
    }

    /**
     * @return \Laminas\Json\Expr
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * @param \Laminas\Json\Expr $custom
     *
     * @return $this
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = strval($mode);

        return $this;
    }

    /**
     * @return bool
     */
    public function isIntersect()
    {
        return $this->intersect;
    }

    /**
     * @param bool $intersect
     *
     * @return $this
     */
    public function setIntersect($intersect)
    {
        $this->intersect = boolval($intersect);

        return $this;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = strval($position);

        return $this;
    }

    /**
     * @return Expr
     */
    public function getItemSort()
    {
        return $this->itemSort;
    }

    /**
     * @param Expr $itemSort
     *
     * @return $this
     */
    public function setItemSort($itemSort)
    {
        $this->itemSort = new Expr(strval($itemSort));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param Expr $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = new Expr(strval($filter));

        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return $this
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = strval($backgroundColor);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitleFontFamily()
    {
        return $this->titleFontFamily;
    }

    /**
     * @param string $titleFontFamily
     *
     * @return $this
     */
    public function setTitleFontFamily($titleFontFamily)
    {
        $this->titleFontFamily = strval($titleFontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getTitleFontSize()
    {
        return $this->titleFontSize;
    }

    /**
     * @param int $titleFontSize
     *
     * @return $this
     */
    public function setTitleFontSize($titleFontSize)
    {
        $this->titleFontSize = intval($titleFontSize);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitleFontStyle()
    {
        return $this->titleFontStyle;
    }

    /**
     * @param string $titleFontStyle
     *
     * @return $this
     */
    public function setTitleFontStyle($titleFontStyle)
    {
        $this->titleFontStyle = strval($titleFontStyle);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitleFontColor()
    {
        return $this->titleFontColor;
    }

    /**
     * @param string $titleFontColor
     *
     * @return $this
     */
    public function setTitleFontColor($titleFontColor)
    {
        $this->titleFontColor = strval($titleFontColor);

        return $this;
    }

    /**
     * @return int
     */
    public function getTitleSpacing()
    {
        return $this->titleSpacing;
    }

    /**
     * @param int $titleSpacing
     *
     * @return $this
     */
    public function setTitleSpacing($titleSpacing)
    {
        $this->titleSpacing = intval($titleSpacing);

        return $this;
    }

    /**
     * @return int
     */
    public function getTitleMarginBottom()
    {
        return $this->titleMarginBottom;
    }

    /**
     * @param int $titleMarginBottom
     *
     * @return $this
     */
    public function setTitleMarginBottom($titleMarginBottom)
    {
        $this->titleMarginBottom = intval($titleMarginBottom);

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyFontFamily()
    {
        return $this->bodyFontFamily;
    }

    /**
     * @param string $bodyFontFamily
     *
     * @return $this
     */
    public function setBodyFontFamily($bodyFontFamily)
    {
        $this->bodyFontFamily = strval($bodyFontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getBodyFontSize()
    {
        return $this->bodyFontSize;
    }

    /**
     * @param int $bodyFontSize
     *
     * @return $this
     */
    public function setBodyFontSize($bodyFontSize)
    {
        $this->bodyFontSize = intval($bodyFontSize);

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyFontStyle()
    {
        return $this->bodyFontStyle;
    }

    /**
     * @param string $bodyFontStyle
     *
     * @return $this
     */
    public function setBodyFontStyle($bodyFontStyle)
    {
        $this->bodyFontStyle = strval($bodyFontStyle);

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyFontColor()
    {
        return $this->bodyFontColor;
    }

    /**
     * @param string $bodyFontColor
     *
     * @return $this
     */
    public function setBodyFontColor($bodyFontColor)
    {
        $this->bodyFontColor = strval($bodyFontColor);

        return $this;
    }

    /**
     * @return int
     */
    public function getBodySpacing()
    {
        return $this->bodySpacing;
    }

    /**
     * @param int $bodySpacing
     *
     * @return $this
     */
    public function setBodySpacing($bodySpacing)
    {
        $this->bodySpacing = intval($bodySpacing);

        return $this;
    }

    /**
     * @return string
     */
    public function getFooterFontFamily()
    {
        return $this->footerFontFamily;
    }

    /**
     * @param string $footerFontFamily
     *
     * @return $this
     */
    public function setFooterFontFamily($footerFontFamily)
    {
        $this->footerFontFamily = strval($footerFontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getFooterFontSize()
    {
        return $this->footerFontSize;
    }

    /**
     * @param int $footerFontSize
     *
     * @return $this
     */
    public function setFooterFontSize($footerFontSize)
    {
        $this->footerFontSize = intval($footerFontSize);

        return $this;
    }

    /**
     * @return string
     */
    public function getFooterFontStyle()
    {
        return $this->footerFontStyle;
    }

    /**
     * @param string $footerFontStyle
     *
     * @return $this
     */
    public function setFooterFontStyle($footerFontStyle)
    {
        $this->footerFontStyle = strval($footerFontStyle);

        return $this;
    }

    /**
     * @return string
     */
    public function getFooterFontColor()
    {
        return $this->footerFontColor;
    }

    /**
     * @param string $footerFontColor
     *
     * @return $this
     */
    public function setFooterFontColor($footerFontColor)
    {
        $this->footerFontColor = strval($footerFontColor);

        return $this;
    }

    /**
     * @return int
     */
    public function getFooterSpacing()
    {
        return $this->footerSpacing;
    }

    /**
     * @param int $footerSpacing
     *
     * @return $this
     */
    public function setFooterSpacing($footerSpacing)
    {
        $this->footerSpacing = intval($footerSpacing);

        return $this;
    }

    /**
     * @return int
     */
    public function getFooterMarginTop()
    {
        return $this->footerMarginTop;
    }

    /**
     * @param int $footerMarginTop
     *
     * @return $this
     */
    public function setFooterMarginTop($footerMarginTop)
    {
        $this->footerMarginTop = intval($footerMarginTop);

        return $this;
    }

    /**
     * @return int
     */
    public function getXPadding()
    {
        return $this->xPadding;
    }

    /**
     * @param int $xPadding
     *
     * @return $this
     */
    public function setXPadding($xPadding)
    {
        $this->xPadding = intval($xPadding);

        return $this;
    }

    /**
     * @return int
     */
    public function getYPadding()
    {
        return $this->yPadding;
    }

    /**
     * @param int $yPadding
     *
     * @return $this
     */
    public function setYPadding($yPadding)
    {
        $this->yPadding = intval($yPadding);

        return $this;
    }

    /**
     * @return int
     */
    public function getCaretSize()
    {
        return $this->caretSize;
    }

    /**
     * @param int $caretSize
     *
     * @return $this
     */
    public function setCaretSize($caretSize)
    {
        $this->caretSize = intval($caretSize);

        return $this;
    }

    /**
     * @return int
     */
    public function getCornerRadius()
    {
        return $this->cornerRadius;
    }

    /**
     * @param int $cornerRadius
     *
     * @return $this
     */
    public function setCornerRadius($cornerRadius)
    {
        $this->cornerRadius = intval($cornerRadius);

        return $this;
    }

    /**
     * @return string
     */
    public function getMultiKeyBackground()
    {
        return $this->multiKeyBackground;
    }

    /**
     * @param string $multiKeyBackground
     *
     * @return $this
     */
    public function setMultiKeyBackground($multiKeyBackground)
    {
        $this->multiKeyBackground = strval($multiKeyBackground);

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisplayColors()
    {
        return $this->displayColors;
    }

    /**
     * @param bool $displayColors
     *
     * @return $this
     */
    public function setDisplayColors($displayColors)
    {
        $this->displayColors = boolval($displayColors);

        return $this;
    }

    /**
     * @return Callbacks
     */
    public function callbacks()
    {
        if (is_null($this->callbacks)) {
            $this->callbacks = new Callbacks();
        }

        return $this->callbacks;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
