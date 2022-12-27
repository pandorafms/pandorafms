<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class Hover
 *
 * @package Artica\PHPChartJS\Options
 */
class Hover implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var bool
     */
    private $intersect;

    /**
     * @var int
     */
    private $animationDuration;

    /**
     * @var Expr
     */
    private $onHover;

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = strval($mode);

        return $this;
    }

    /**
     * @return bool
     */
    public function isIntersect()
    {
        return $this->intersect;
    }

    /**
     * @return bool
     */
    public function getIntersect()
    {
        return $this->intersect;
    }

    /**
     * @param bool $intersect
     *
     * @return $this
     */
    public function setIntersect($intersect)
    {
        $this->intersect = ! ! $intersect;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnimationDuration()
    {
        return $this->animationDuration;
    }

    /**
     * @param int $animationDuration
     *
     * @return $this
     */
    public function setAnimationDuration($animationDuration)
    {
        $this->animationDuration = intval($animationDuration);

        return $this;
    }

    /**
     * @return \Laminas\Json\Expr
     */
    public function getOnHover()
    {
        return $this->onHover;
    }

    /**
     * @param Expr $onHover
     *
     * @return $this
     */
    public function setOnHover($onHover)
    {
        $this->onHover = new Expr(strval($onHover));

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
