<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class WaterMark
 *
 * @package Artica\PHPChartJS\Options
 */
class WaterMark implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var float
     */
    private $width;

    /**
     * @var float
     */
    private $height;

    /**
     * @var string
     */
    private $src;

    /**
     * @var string
     */
    private $position;

    /**
     * @var string
     */
    private $align;


    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }


    /**
     * @param float $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = intval($width);

        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }


    /**
     * @param float $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = intval($height);

        return $this;
    }


    /**
     * @return string
     */
    public function getSrc()
    {
        return $this->src;
    }


    /**
     * @param string $src
     *
     * @return $this
     */
    public function setSrc($src)
    {
        $this->src = strval($src);

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
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }


}
