<?php

namespace Artica\PHPChartJS\ConfigDefaults;

/**
 * Class GlobalConfig
 * @package Artica\PHPChartJS\ConfigDefaults
 */
class GlobalConfig
{
    /**
     * @var GlobalConfig
     */
    private static $instance;

    /**
     * @var string
     */
    private $defaultFontColor;

    /**
     * @var string
     */
    private $defaultFontFamily;

    /**
     * @var int
     */
    private $defaultFontSize;

    /**
     * @var string
     */
    private $defaultFontStyle;

    /**
     * @var LayoutConfig
     */
    private $layout;

    /**
     * @var TooltipsConfig
     */
    private $tooltips;

    /**
     * @var HoverConfig
     */
    private $hover;

    /**
     * @var AnimationConfig
     */
    private $animation;

    /**
     * @var ElementsConfig
     */
    private $elements;

    /**
     * GlobalConfig constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getDefaultFontColor()
    {
        return $this->defaultFontColor;
    }

    /**
     * @param string $defaultFontColor
     *
     * @return $this
     */
    public function setDefaultFontColor($defaultFontColor)
    {
        $this->defaultFontColor = strval($defaultFontColor);

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultFontFamily()
    {
        return $this->defaultFontFamily;
    }

    /**
     * @param string $defaultFontFamily
     *
     * @return $this
     */
    public function setDefaultFontFamily($defaultFontFamily)
    {
        $this->defaultFontFamily = strval($defaultFontFamily);

        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultFontSize()
    {
        return $this->defaultFontSize;
    }

    /**
     * @param int $defaultFontSize
     *
     * @return $this
     */
    public function setDefaultFontSize($defaultFontSize)
    {
        $this->defaultFontSize = intval($defaultFontSize);

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultFontStyle()
    {
        return $this->defaultFontStyle;
    }

    /**
     * @param string $defaultFontStyle
     *
     * @return $this
     */
    public function setDefaultFontStyle($defaultFontStyle)
    {
        $this->defaultFontStyle = $defaultFontStyle;

        return $this;
    }

    /**
     * @return LayoutConfig
     */
    public function layout()
    {
        if (is_null($this->layout)) {
            $this->layout = new LayoutConfig();
        }

        return $this->layout;
    }

    /**
     * @return TooltipsConfig
     */
    public function tooltips()
    {
        if (is_null($this->tooltips)) {
            $this->tooltips = new TooltipsConfig();
        }

        return $this->tooltips;
    }

    /**
     * @return HoverConfig
     */
    public function hover()
    {
        if (is_null($this->hover)) {
            $this->hover = new HoverConfig();
        }

        return $this->hover;
    }

    /**
     * @return AnimationConfig
     */
    public function animation()
    {
        if (is_null($this->animation)) {
            $this->animation = new AnimationConfig();
        }

        return $this->animation;
    }

    /**
     * @return ElementsConfig
     */
    public function elements()
    {
        if (is_null($this->elements)) {
            $this->elements = new ElementsConfig();
        }

        return $this->elements;
    }
}
