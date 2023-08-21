<?php

namespace Artica\PHPChartJS\Options\Elements;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Center
 * @package Artica\PHPChartJS\Options\Elements
 */
class Center implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * Text center graph.
     * @var string
     */
    private $text;

    /**
     * Color text.
     * @default '#000'
     * @var string
     */
    private $color;

    /**
     * Get text center graph.
     *
     * @return  string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set text center graph.
     *
     * @param  string  $text  Text center graph.
     *
     * @return  self
     */
    public function setText(string $text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get color text.
     *
     * @return  string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set color text.
     *
     * @param  string  $color  Color text.
     *
     * @return  self
     */
    public function setColor(string $color)
    {
        $this->color = $color;

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
