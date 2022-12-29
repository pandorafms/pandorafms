<?php

namespace Artica\PHPChartJS\Options\Tooltips;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use JsonSerializable;
use Laminas\Json\Expr;

/**
 * Class Callbacks
 *
 * @package Artica\PHPChartJS\Tooltips
 */
class Callbacks implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var Expr
     */
    private $beforeTitle;

    /**
     * @var Expr
     */
    private $title;

    /**
     * @var Expr
     */
    private $afterTitle;

    /**
     * @var Expr
     */
    private $beforeLabel;

    /**
     * @var Expr
     */
    private $label;

    /**
     * @var Expr
     */
    private $labelColor;

    /**
     * @var Expr
     */
    private $afterLabel;

    /**
     * @var Expr
     */
    private $afterBody;

    /**
     * @var Expr
     */
    private $beforeFooter;

    /**
     * @var Expr
     */
    private $footer;

    /**
     * @var Expr
     */
    private $afterFooter;

    /**
     * @var Expr
     */
    private $dataPoints;

    /**
     * @return Expr
     */
    public function getBeforeTitle()
    {
        return $this->beforeTitle;
    }

    /**
     * @param string $beforeTitle
     *
     * @return $this
     */
    public function setBeforeTitle($beforeTitle)
    {
        $this->beforeTitle = new Expr(strval($beforeTitle));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = new Expr(strval($title));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getAfterTitle()
    {
        return $this->afterTitle;
    }

    /**
     * @param string $afterTitle
     *
     * @return $this
     */
    public function setAfterTitle($afterTitle)
    {
        $this->afterTitle = new Expr(strval($afterTitle));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getBeforeLabel()
    {
        return $this->beforeLabel;
    }

    /**
     * @param string $beforeLabel
     *
     * @return $this
     */
    public function setBeforeLabel($beforeLabel)
    {
        $this->beforeLabel = new Expr(strval($beforeLabel));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = new Expr(strval($label));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getLabelColor()
    {
        return $this->labelColor;
    }

    /**
     * @param string $labelColor
     *
     * @return $this
     */
    public function setLabelColor($labelColor)
    {
        $this->labelColor = new Expr(strval($labelColor));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getAfterLabel()
    {
        return $this->afterLabel;
    }

    /**
     * @param string $afterLabel
     *
     * @return $this
     */
    public function setAfterLabel($afterLabel)
    {
        $this->afterLabel = new Expr(strval($afterLabel));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getAfterBody()
    {
        return $this->afterBody;
    }

    /**
     * @param string $afterBody
     *
     * @return $this
     */
    public function setAfterBody($afterBody)
    {
        $this->afterBody = new Expr(strval($afterBody));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getBeforeFooter()
    {
        return $this->beforeFooter;
    }

    /**
     * @param string $beforeFooter
     *
     * @return $this
     */
    public function setBeforeFooter($beforeFooter)
    {
        $this->beforeFooter = new Expr(strval($beforeFooter));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param string $footer
     *
     * @return $this
     */
    public function setFooter($footer)
    {
        $this->footer = new Expr(strval($footer));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getAfterFooter()
    {
        return $this->afterFooter;
    }

    /**
     * @param string $afterFooter
     *
     * @return $this
     */
    public function setAfterFooter($afterFooter)
    {
        $this->afterFooter = new Expr(strval($afterFooter));

        return $this;
    }

    /**
     * @return Expr
     */
    public function getDataPoints()
    {
        return $this->dataPoints;
    }

    /**
     * @param string $dataPoints
     *
     * @return $this
     */
    public function setDataPoints($dataPoints)
    {
        $this->dataPoints = new Expr(strval($dataPoints));

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
