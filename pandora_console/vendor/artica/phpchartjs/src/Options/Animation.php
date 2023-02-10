<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class Animation
 *
 * @package Artica\PHPChartJS\Options
 */
class Animation implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var string
     */
    private $easing;

    /**
     * @var Expr
     */
    private $onProgress;

    /**
     * @var Expr
     */
    private $onComplete;

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return string
     */
    public function getEasing()
    {
        return $this->easing;
    }

    /**
     * @param string $easing
     *
     * @return $this
     */
    public function setEasing($easing)
    {
        $this->easing = $easing;

        return $this;
    }

    /**
     * @return Expr
     */
    public function getOnProgress()
    {
        return $this->onProgress;
    }

    /**
     * @param string $onProgress
     *
     * @return $this
     */
    public function setOnProgress($onProgress)
    {
        $this->onProgress = new Expr(strval($onProgress));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getOnComplete()
    {
        return $this->onComplete;
    }

    /**
     * @param string $onComplete
     *
     * @return $this
     */
    public function setOnComplete($onComplete)
    {
        $this->onComplete = new Expr(strval($onComplete));

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
