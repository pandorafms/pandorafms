<?php

namespace Artica\PHPChartJS\Options;

use Artica\PHPChartJS\ArraySerializableInterface;
use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Legend;
use JsonSerializable;

/**
 * Undocumented class
 */
class Plugins implements ArraySerializableInterface, JsonSerializable
{
    use ArraySerializable;

    /**
     * @var string
     */
    private $legend;


    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     */
    private $datalabels;


    /**
     * @var string
     */
    private $tooltips;


    /**
     * @var string
     */
    private $tooltip;


    /**
     * @return Tooltips
     */
    public function getTooltips()
    {
        if (is_null($this->tooltips)) {
            $this->tooltips = new Tooltips();
        }

        return $this->tooltips;
    }


    /**
     * @return Tooltip
     */
    public function getTooltip()
    {
        if (is_null($this->tooltip)) {
            $this->tooltip = new Tooltip();
        }

        return $this->tooltip;
    }


    /**
     * Return Legend.
     *
     * @return Legend
     */
    public function getLegend()
    {
        if (isset($this->legend) === false) {
            $this->legend = new Legend();
        }

        return $this->legend;
    }

    /**
     * Return Title.
     *
     * @return Title
     */
    public function getTitle()
    {
        if (isset($this->title) === false) {
            $this->title = new Title();
        }

        return $this->title;
    }


    /**
     * Return Data label.
     *
     * @return DataLabel
     */
    public function getDataLabel()
    {
        if (isset($this->datalabels) === false) {
            $this->datalabels = new DataLabel();
        }

        return $this->datalabels;
    }


    /**
     * Serialize.
     *
     * @return array
     */
    public function jsonSerialize():mixed
    {
        return $this->getArrayCopy();
    }


}
