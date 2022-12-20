<?php

namespace Artica\PHPChartJS;

use Artica\PHPChartJS\Renderer\Html;
use Laminas\Json\Expr;

/**
 * Class Chart
 *
 * @package Artica\PHPChartJS
 */
abstract class Chart implements ChartInterface
{
    /**
     * The internal type of chart.
     */
    const TYPE = null;

    /**
     * The list of models that should be used for this chart type.
     */
    const MODEL = [
        'dataset' => DataSet::class,
        'options' => Options::class,
    ];

    /**
     * @var string
     */
    protected $id;

    /**
     * @var integer
     */
    protected $height;

    /**
     * @var integer
     */
    protected $width;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var LabelsCollection
     */
    protected $labels;

    /**
     * @var PluginsCollection
     */
    protected $plugins;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var Defaults
     */
    protected $defaults;

    /**
     * @var DataSetCollection
     */
    protected $dataSets;


    /**
     * @return string
     */
    public function getId()
    {
        if (is_null($this->id)) {
            $this->id = uniqid('chart');
        }

        return $this->id;
    }


    /**
     * @param string $id
     *
     * @return Chart
     */
    public function setId($id)
    {
        $this->id = strval($id);

        return $this;
    }


    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }


    /**
     * @param integer $height
     *
     * @return Chart
     */
    public function setHeight($height)
    {
        $this->height = intval($height);

        return $this;
    }


    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }


    /**
     * @param integer $width
     *
     * @return Chart
     */
    public function setWidth($width)
    {
        $this->width = intval($width);

        return $this;
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * @param string $title
     *
     * @return Chart
     */
    public function setTitle($title)
    {
        $this->title = strval($title);

        return $this;
    }


    /**
     * @return LabelsCollection
     */
    public function labels()
    {
        if (is_null($this->labels)) {
            $this->labels = new LabelsCollection();
        }

        return $this->labels;
    }


    /**
     * @param string $label
     *
     * @return $this
     */
    public function addLabel($label)
    {
        $this->labels()->append($label);

        return $this;
    }


    /**
     * @param $offset
     *
     * @return string|boolean
     */
    public function getLabel($offset)
    {
        return $this->labels()->offsetGet($offset);
    }


    /**
     * @return PluginsCollection
     */
    public function plugins()
    {
        if (is_null($this->plugins)) {
            $this->plugins = new PluginsCollection();
        }

        return $this->plugins;
    }


    /**
     * @param string $plugin
     *
     * @return $this
     */
    public function addPlugin($plugin)
    {
        $this->plugins()->append(new Expr(strval($plugin)));

        return $this;
    }


    /**
     * @param $offset
     *
     * @return string|boolean
     */
    public function getPlugin($offset)
    {
        return $this->plugins()->offsetGet($offset);
    }


    /**
     * @return DataSetCollection
     */
    public function dataSets()
    {
        if (is_null($this->dataSets)) {
            $this->dataSets = new DataSetCollection();
        }

        return $this->dataSets;
    }


    /**
     * @param DataSet $dataSet
     *
     * @return $this
     */
    public function addDataSet(DataSet $dataSet)
    {
        $this->dataSets()->append($dataSet->setOwner($this));

        return $this;
    }


    /**
     * @param $offset
     *
     * @return DataSet|boolean
     */
    public function getDataSet($offset)
    {
        return $this->dataSets()->offsetGet($offset);
    }


    /**
     * @param boolean $pretty
     *
     * @return string
     */
    public function render($pretty=false)
    {
        $renderer = new Html($this);

        return $renderer->render($pretty ? $renderer::RENDER_PRETTY : null);
    }


    /**
     * @return DataSet
     */
    public function createDataSet()
    {
        $datasetClass = static::MODEL['dataset'];
        /*
            @var \Artica\PHPChartJS\DataSet $dataSet
        */
        $dataSet = new $datasetClass();
        $dataSet->setOwner($this);

        return $dataSet;
    }


    /**
     * @return Options
     */
    public function options()
    {
        if (is_null($this->options)) {
            $optionsClass  = static::MODEL['options'];
            $this->options = new $optionsClass($this);
            $this->options->setOwner($this);
        }

        return $this->options;
    }


    /**
     * @return Defaults
     */
    public function defaults()
    {
        if (is_null($this->defaults)) {
            $this->defaults = new Defaults($this);
        }

        return $this->defaults;
    }


}
