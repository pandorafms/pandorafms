<?php

namespace Artica\PHPChartJS\Options\Legend;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class PieLegend
 *
 * @package Artica\PHPChartJS\Options\Legend
 */
class Labels implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var int
     */
    private $boxWidth;

    /**
     * @var int
     */
    private $fontSize;

    /**
     * @var string
     */
    private $fontStyle;

    /**
     * @var string
     */
    private $fontColor;

    /**
     * @var string
     */
    private $fontFamily;

    /**
     * @var int
     */
    private $padding;

    /**
     * @var Expr
     */
    private $generateLabels;

    /**
     * @var bool
     */
    private $usePointStyle;

    /**
     * @return int
     */
    public function getBoxWidth()
    {
        return $this->boxWidth;
    }

    /**
     * @param int $boxWidth
     *
     * @return Labels
     */
    public function setBoxWidth($boxWidth)
    {
        $this->boxWidth = intval($boxWidth);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * @param int $fontSize
     *
     * @return Labels
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = intval($fontSize);

        return $this;
    }

    /**
     * @return string
     */
    public function getFontStyle()
    {
        return $this->fontStyle;
    }

    /**
     * @param string $fontStyle
     *
     * @return Labels
     */
    public function setFontStyle($fontStyle)
    {
        $this->fontStyle = strval($fontStyle);

        return $this;
    }

    /**
     * @return string
     */
    public function getFontColor()
    {
        return $this->fontColor;
    }

    /**
     * @param string $fontColor
     *
     * @return Labels
     */
    public function setFontColor($fontColor)
    {
        $this->fontColor = strval($fontColor);

        return $this;
    }

    /**
     * @return string
     */
    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    /**
     * @param string $fontFamily
     *
     * @return Labels
     */
    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = strval($fontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @param int $padding
     *
     * @return Labels
     */
    public function setPadding($padding)
    {
        $this->padding = intval($padding);

        return $this;
    }

    /**
     * @return Expr
     */
    public function getGenerateLabels()
    {
        return $this->generateLabels;
    }

    /**
     * @param string $generateLabels
     *
     * @return Labels
     */
    public function setGenerateLabels($generateLabels)
    {
        $this->generateLabels = new Expr(strval($generateLabels));

        return $this;
    }

    /**
     * @return bool
     */
    public function isUsePointStyle()
    {
        return $this->usePointStyle;
    }

    /**
     * @param bool $usePointStyle
     *
     * @return Labels
     */
    public function setUsePointStyle($usePointStyle)
    {
        $this->usePointStyle = ! ! $usePointStyle;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
