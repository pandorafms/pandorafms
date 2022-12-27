<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class LineOptions
 *
 * @package Artica\PHPChartJS\Options
 */
class Title implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var boolean
     */
    private $display;

    /**
     * @var string
     */
    private $position;

    /**
     * @var string
     */
    private $color;

    /**
     * @var boolean
     */
    private $fullWidth;

    /**
     * @var integer
     */
    private $padding;

    /**
     * @var string
     */
    private $text;

    /**
     * @var Fonts
     */
    private $font;


    /**
     * @return boolean
     */
    public function isDisplay()
    {
        return $this->display;
    }


    /**
     * @param boolean $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = boolval($display);

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
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }


    /**
     * @param string $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        $this->color = strval($color);

        return $this;
    }


    /**
     * @return boolean
     */
    public function isFullWidth()
    {
        return $this->fullWidth;
    }


    /**
     * @param boolean $fullWidth
     *
     * @return $this
     */
    public function setFullWidth($fullWidth)
    {
        $this->fullWidth = boolval($fullWidth);

        return $this;
    }


    /**
     * @return integer
     */
    public function getPadding()
    {
        return $this->padding;
    }


    /**
     * @param integer $padding
     *
     * @return $this
     */
    public function setPadding($padding)
    {
        $this->padding = intval($padding);

        return $this;
    }


    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }


    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = strval($text);

        return $this;
    }


    /**
     * Return Font.
     *
     * @return Font
     */
    public function getFonts()
    {
        if (isset($this->font) === false) {
            $this->font = new Fonts();
        }

        return $this->font;
    }


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }


}
