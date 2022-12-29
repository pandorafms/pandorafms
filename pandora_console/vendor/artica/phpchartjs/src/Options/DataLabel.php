<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class DataLabel
 *
 * @package Artica\PHPChartJS\Options
 */
class DataLabel implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var string
     */
    private $display;

    /**
     * @var boolean
     */
    private $clip;

    /**
     * @var boolean
     */
    private $clamp;

    /**
     * @var string
     */
    private $color;

    /**
     * @var integer
     */
    private $offset;

    /**
     * @var string
     */
    private $anchor;

    /**
     * @var string
     */
    private $align;

    /**
     * @var Expr
     */
    private $formatter;

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
     * @param string $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = strval($display);

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
     * @return string
     */
    public function getAnchor()
    {
        return $this->anchor;
    }


    /**
     * @param string $anchor
     *
     * @return $this
     */
    public function setAnchor($anchor)
    {
        $this->anchor = strval($anchor);

        return $this;
    }


    /**
     * @return integer
     */
    public function getOffset()
    {
        return $this->offset;
    }


    /**
     * @param integer $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = intval($offset);

        return $this;
    }


    /**
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }


    /**
     * @param string $align
     *
     * @return $this
     */
    public function setAlign($align)
    {
        $this->align = strval($align);

        return $this;
    }


    /**
     * @return Expr
     */
    public function getFormatter()
    {
        return $this->formatter;
    }


    /**
     * @param string $formatter
     *
     * @return $this
     */
    public function setFormatter($formatter)
    {
        $this->formatter = new Expr(strval($formatter));

        return $this;
    }


    /**
     * @return boolean
     */
    public function getClamp()
    {
        return $this->clamp;
    }


    /**
     * @param boolean $clamp
     *
     * @return $this
     */
    public function setClamp($clamp)
    {
        $this->clamp = boolval($clamp);

        return $this;
    }


    /**
     * @return boolean
     */
    public function getClip()
    {
        return $this->clip;
    }


    /**
     * @param boolean $clip
     *
     * @return $this
     */
    public function setClip($clip)
    {
        $this->clip = boolval($clip);

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
