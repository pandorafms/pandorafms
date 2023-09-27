<?php
/**
 * Element class parent for elements
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

namespace PandoraFMS\TacticalView;

/**
 * Parent element for general tactical view elements
 */
class Element
{

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * List of available ajax methods.
     *
     * @var array
     */
    protected $ajaxMethods = [];

    /**
     * Title of section
     *
     * @var string
     */
    public $title;

    /**
     * Interval for refresh element, 0 for not refresh.
     *
     * @var integer
     */
    protected $interval;


    /**
     * Contructor
     *
     * @param string $ajax_controller Controller.
     */
    public function __construct(
        $ajax_controller='include/ajax/general_tactical_view.ajax'
    ) {
        $this->interval = 0;
        $this->title = __('Default element');
        $this->ajaxController = $ajax_controller;
    }


    /**
     * Return error message to target.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public static function error(string $msg)
    {
        echo json_encode(['error' => $msg]);
    }


    /**
     * Verifies target method is allowed to be called using AJAX call.
     *
     * @param string $method Method to be invoked via AJAX.
     *
     * @return boolean Available (true), or not (false).
     */
    public function ajaxMethod(string $method):bool
    {
        return in_array($method, $this->ajaxMethods) === true;
    }


    /**
     * Cut the text to display it on the labels.
     *
     * @param string  $text   Text for cut.
     * @param integer $length Length max for text cutted.
     *
     * @return string
     */
    protected function controlSizeText(string $text, int $length=14):string
    {
        if (mb_strlen($text) > $length) {
            $newText = mb_substr($text, 0, $length).'...';
            return $newText;
        } else {
            return $text;
        }
    }


}
