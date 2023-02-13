<?php

namespace Artica\PHPChartJS\Options\Scales;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class ScaleLabel
 *
 * @package Artica\PHPChartJS\Options\Scales
 */
class ScaleLabel implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var bool
     */
    private $display;

    /**
     * @var string
     */
    private $labelString;

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
    private $fontSize;

    /**
     * @var string
     */
    private $fontStyle;

    /**
     * @return bool
     */
    public function isDisplay()
    {
        return $this->display;
    }

    /**
     * @param bool $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = ! ! $display;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelString()
    {
        return $this->labelString;
    }

    /**
     * @param string $labelString
     *
     * @return $this
     */
    public function setLabelString($labelString)
    {
        $this->labelString = strval($labelString);

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
     * @return $this
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
     * @return $this
     */
    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = strval($fontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * @param int $fontSize
     *
     * @return $this
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
     * @return $this
     */
    public function setFontStyle($fontStyle)
    {
        $this->fontStyle = strval($fontStyle);

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
