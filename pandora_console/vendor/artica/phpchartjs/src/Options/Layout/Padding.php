<?php

namespace Artica\PHPChartJS\Options\Layout;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;

/**
 * Class Padding
 * @package Artica\PHPChartJS\Options\Layout
 */
class Padding implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var int
     */
    private $bottom;

    /**
     * @var int
     */
    private $left;

    /**
     * @var int
     */
    private $right;

    /**
     * @var int
     */
    private $top;

    /**
     * @return int
     */
    public function getBottom()
    {
        return $this->bottom;
    }

    /**
     * @param int $bottom
     *
     * @return $this
     */
    public function setBottom($bottom)
    {
        $this->bottom = intval($bottom);

        return $this;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param int $left
     *
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = intval($left);

        return $this;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param int $right
     *
     * @return $this
     */
    public function setRight($right)
    {
        $this->right = intval($right);

        return $this;
    }

    /**
     * @return int
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * @param int $top
     *
     * @return $this
     */
    public function setTop($top)
    {
        $this->top = intval($top);

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
