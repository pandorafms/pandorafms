<?php

namespace Artica\PHPChartJS\Options\Legend;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Fonts;
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
     * @var integer
     */
    private $boxWidth;

    /**
     * @var Fonts
     */
    private $font;

    /**
     * @var integer
     */
    private $padding;

    /**
     * @var Expr
     */
    private $generateLabels;

    /**
     * @var boolean
     */
    private $usePointStyle;


    /**
     * @return integer
     */
    public function getBoxWidth()
    {
        return $this->boxWidth;
    }


    /**
     * @param integer $boxWidth
     *
     * @return Labels
     */
    public function setBoxWidth($boxWidth)
    {
        $this->boxWidth = intval($boxWidth);

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
     * @return integer
     */
    public function getPadding()
    {
        return $this->padding;
    }


    /**
     * @param integer $padding
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
     * @return boolean
     */
    public function isUsePointStyle()
    {
        return $this->usePointStyle;
    }


    /**
     * @param boolean $usePointStyle
     *
     * @return Labels
     */
    public function setUsePointStyle($usePointStyle)
    {
        $this->usePointStyle = !!$usePointStyle;

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
