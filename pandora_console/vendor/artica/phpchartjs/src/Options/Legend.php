<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Labels;
use Artica\PHPChartJS\Options\Legend\Labels as LegendLabels;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class Legend
 *
 * @package Artica\PHPChartJS\Options
 */
class Legend implements ArraySerializableInterface, JsonSerializable
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
     * @var boolean
     */
    private $fullWidth;

    /**
     * @var Expr
     */
    private $onClick;

    /**
     * @var Expr
     */
    private $onHover;

    /**
     * @var Labels
     */
    private $labels;

    /**
     * @var boolean
     */
    private $reverse;

    /**
     * Align.
     *
     * @var string
     */
    protected string $align;


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
     * @return Expr
     */
    public function getOnClick()
    {
        return $this->onClick;
    }


    /**
     * @param string $onClick
     *
     * @return $this
     */
    public function setOnClick($onClick)
    {
        $this->onClick = new Expr(strval($onClick));

        return $this;
    }


    /**
     * @return Expr
     */
    public function getOnHover()
    {
        return $this->onHover;
    }


    /**
     * @param string $onHover
     *
     * @return $this
     */
    public function setOnHover($onHover)
    {
        $this->onHover = new Expr(strval($onHover));

        return $this;
    }


    /**
     * @return Labels
     */
    public function labels()
    {
        if (is_null($this->labels)) {
            $this->labels = new LegendLabels();
        }

        return $this->labels;
    }


    /**
     * @return boolean
     */
    public function isReverse()
    {
        return $this->reverse;
    }


    /**
     * @param boolean $reverse
     *
     * @return $this
     */
    public function setReverse($reverse)
    {
        $this->reverse = boolval($reverse);

        return $this;
    }


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }


    /**
     * Get align.
     *
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }


    /**
     * Set align.
     *
     * @param string $align Align.
     *
     * @return $this
     */
    public function setAlign(string $align)
    {
        $this->align = $align;

        return $this;
    }


}
