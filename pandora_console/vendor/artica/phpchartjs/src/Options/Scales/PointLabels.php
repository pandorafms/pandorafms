<?php

namespace Artica\PHPChartJS\Options\Scales;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Artica\PHPChartJS\Options\Fonts;

/**
 * Class Pointlabels
 *
 * @package Artica\PHPChartJS\Options\Scales
 */
class Pointlabels implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var string
     */
    private $backdropColor;

    /**
     * @var int
     */
    private $backdropPadding;

    /**
     * @var int
     */
    private $borderRadius;

    /**
     * @var string
     */
    private $display;

    /**
     * @var string
     */
    private $color;

    /**
     * @var Fonts
     */
    private $font;

    /**
     * @var int
     */
    private $padding;

    /**
     * @var bool
     */
    private $centerPointLabels;

    /**
     * Get the value of backdropColor
     *
     * @return  string
     */ 
    public function getBackdropColor()
    {
        return $this->backdropColor;
    }

    /**
     * Set the value of backdropColor
     *
     * @param  string  $backdropColor
     *
     * @return  self
     */ 
    public function setBackdropColor(string $backdropColor)
    {
        $this->backdropColor = $backdropColor;

        return $this;
    }

    /**
     * Get the value of backdropPadding
     *
     * @return  int
     */ 
    public function getBackdropPadding()
    {
        return $this->backdropPadding;
    }

    /**
     * Set the value of backdropPadding
     *
     * @param  int  $backdropPadding
     *
     * @return  self
     */ 
    public function setBackdropPadding(int $backdropPadding)
    {
        $this->backdropPadding = $backdropPadding;

        return $this;
    }

    /**
     * Get the value of borderRadius
     *
     * @return  int
     */ 
    public function getBorderRadius()
    {
        return $this->borderRadius;
    }

    /**
     * Set the value of borderRadius
     *
     * @param  int  $borderRadius
     *
     * @return  self
     */ 
    public function setBorderRadius(int $borderRadius)
    {
        $this->borderRadius = $borderRadius;

        return $this;
    }

    /**
     * Get the value of display
     *
     * @return  string
     */ 
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set the value of display
     *
     * @param  string  $display
     *
     * @return  self
     */ 
    public function setDisplay(string $display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get the value of color
     *
     * @return  string
     */ 
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the value of color
     *
     * @param  string  $color
     *
     * @return  self
     */ 
    public function setColor(string $color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get the value of font
     *
     * @return  string
     */ 
    public function getFonts()
    {
        if (isset($this->font) === false) {
            $this->font = new Fonts();
        }

        return $this->font;
    }

    /**
     * Set the value of font
     *
     * @param  string  $font
     *
     * @return  self
     */ 
    public function setFonts(string $font)
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get the value of padding
     *
     * @return  int
     */ 
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * Set the value of padding
     *
     * @param  int  $padding
     *
     * @return  self
     */ 
    public function setPadding(int $padding)
    {
        $this->padding = $padding;

        return $this;
    }

    /**
     * Get the value of centerPointLabels
     *
     * @return  bool
     */ 
    public function getCenterPointLabels()
    {
        return $this->centerPointLabels;
    }

    /**
     * Set the value of centerPointLabels
     *
     * @param  bool  $centerPointLabels
     *
     * @return  self
     */ 
    public function setCenterPointLabels(bool $centerPointLabels)
    {
        $this->centerPointLabels = $centerPointLabels;

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
