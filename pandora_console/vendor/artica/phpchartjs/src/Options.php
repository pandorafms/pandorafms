<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Delegate\ArraySerializable;
use Artica\PHPChartJS\Options\Animation;
use Artica\PHPChartJS\Options\Elements;
use Artica\PHPChartJS\Options\Hover;
use Artica\PHPChartJS\Options\Layout;
use Artica\PHPChartJS\Options\Legend;
use Artica\PHPChartJS\Options\Scales;
use Artica\PHPChartJS\Options\Title;
use Artica\PHPChartJS\Options\Tooltips;
use JsonSerializable;
use Laminas\Json\Expr;
use Artica\PHPChartJS\Options\Plugins;

/**
 * Class Options
 *
 * @package Artica\PHPChartJS
 */
class Options implements ChartOwnedInterface, ArraySerializableInterface, JsonSerializable
{
    use ChartOwned;
    use ArraySerializable;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * @var Title
     */
    protected $title;

    /**
     * @var Elements
     */
    protected $elements;

    /**
     * @var Hover
     */
    protected $hover;

    /**
     * @var \Laminas\Json\Expr
     */
    protected $onClick;

    /**
     * @var Scales
     */
    protected $scales;

    /**
     * @var Animation
     */
    protected $animation;

    /**
     * @var Legend
     */
    protected $legend;

     /**
      * Plugins.
      *
      * @var Plugin
      */
    protected $plugins;

    /**
     * @var Tooltips
     */
    protected $tooltips;

    /**
     * @var boolean
     */
    protected $maintainAspectRatio;

    /**
     * @var boolean
     */
    protected $responsive;

    /**
     * @var string
     */
    protected $indexAxis;


    /**
     * @return Layout
     */
    public function getLayout()
    {
        if (is_null($this->layout)) {
            $this->layout = new Layout();
        }

        return $this->layout;
    }


    /**
     * @return Elements
     */
    public function getElements()
    {
        if (is_null($this->elements)) {
            $this->elements = new Elements();
        }

        return $this->elements;
    }


    /**
     * @return Title
     */
    public function getTitle()
    {
        if (is_null($this->title)) {
            $this->title = new Title();
        }

        return $this->title;
    }


    /**
     * @return Hover
     */
    public function getHover()
    {
        if (is_null($this->hover)) {
            $this->hover = new Hover();
        }

        return $this->hover;
    }


    /**
     * @return \Laminas\Json\Expr
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
     * @return Scales
     */
    public function getScales()
    {
        if (is_null($this->scales)) {
            $this->scales = new Scales();
        }

        return $this->scales;
    }


    /**
     * @return Animation
     */
    public function getAnimation()
    {
        if (is_null($this->animation)) {
            $this->animation = new Animation();
        }

        return $this->animation;
    }


    /**
     * @return bool
     */
    public function disableAnimation()
    {
        $this->animation = false;

        return $this->animation;
    }


    /**
     * @return Legend
     */
    public function getLegend()
    {
        if (is_null($this->legend)) {
            $this->legend = new Legend();
        }

        return $this->legend;
    }


    /**
     * Get plugin.
     *
     * @return Plugin
     */
    public function getPlugins()
    {
        if ($this->plugins === null) {
            $this->plugins = new Plugins();
        }

        return $this->plugins;
    }


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
     * @return boolean
     */
    public function isResponsive()
    {
        if (is_null($this->responsive)) {
            $this->responsive = true;
        }

        return $this->responsive;
    }


    /**
     * @param boolean $flag
     *
     * @return $this
     */
    public function setResponsive($flag)
    {
        $this->responsive = boolval($flag);

        return $this;
    }


    /**
     * @return boolean
     */
    public function isMaintainAspectRatio()
    {
        if (is_null($this->maintainAspectRatio)) {
            $this->maintainAspectRatio = true;
        }

        return $this->maintainAspectRatio;
    }


    /**
     * @param boolean $flag
     *
     * @return $this
     */
    public function setMaintainAspectRatio($flag)
    {
        $this->maintainAspectRatio = boolval($flag);

        return $this;
    }


    /**
     * Get Index axis.
     *
     * @return string
     */
    public function getIndexAxis()
    {
        return $this->indexAxis;
    }


    /**
     * Set Index Axis.
     *
     * @param string $indexAxis Index Axis.
     *
     * @return $this
     */
    public function setIndexAxis($indexAxis)
    {
        $this->indexAxis = $indexAxis;

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
