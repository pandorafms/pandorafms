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
    public $interval;

    /**
     * Agent of automonitoritation
     *
     * @var array
     */
    protected $monitoringAgent;

    /**
     * Refresh config for async method.
     *
     * @var array
     */
    public $refreshConfig = [];


    /**
     * Contructor
     *
     * @param string $ajax_controller Controller.
     */
    public function __construct(
        $ajax_controller='include/ajax/general_tactical_view.ajax'
    ) {
        global $config;
        $this->interval = 0;
        $this->title = __('Default element');
        $this->ajaxController = $ajax_controller;
        // Without ACL.
        $agent_name = $config['self_monitoring_agent_name'];
        if (empty($agent_name) === true) {
            $agent_name = 'pandora.internals';
        }

        $agent = db_get_row('tagente', 'nombre', $agent_name, '*');
        if (is_array($agent) === true) {
            $this->monitoringAgent = $agent;
        }

        /*
            // With ACL.
            $agent = agents_get_agents(['nombre' => 'pandora.internals']);
            if (is_array($agent) === true && count($agent) > 0) {
                $this->monitoringAgent = $agent[0];
            }
        */
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


    /**
     * Return a valur from Module of monitoring.
     *
     * @param string  $moduleName Name of module value.
     * @param integer $dateInit   Date init for filter.
     * @param integer $dateEnd    Date end for filter.
     *
     * @return array Array of module data.
     */
    protected function valueMonitoring(string $moduleName, int $dateInit=0, int $dateEnd=0):array
    {
        if (empty($this->monitoringAgent) === false) {
            $module = modules_get_agentmodule_id(io_safe_input($moduleName), $this->monitoringAgent['id_agente']);
            if (is_array($module) === true && key_exists('id_agente_modulo', $module) === true) {
                if ($dateInit === 0 && $dateEnd === 0) {
                    $value = modules_get_last_value($module['id_agente_modulo']);
                    $rawData = [['datos' => $value]];
                } else {
                    $rawData = modules_get_raw_data($module['id_agente_modulo'], $dateInit, $dateEnd);
                }

                if ($rawData === false || is_array($rawData) === false) {
                    return [['datos' => 0]];
                } else {
                    return $rawData;
                }
            } else {
                return [['datos' => 0]];
            }

            return [['datos' => 0]];
        } else {
            return [['datos' => 0]];
        }
    }


    /**
     * Simple image loading for async functions.
     *
     * @return string
     */
    public static function loading():string
    {
        return html_print_div(
            [
                'content' => '<span></span>',
                'class'   => 'spinner-fixed inherit',
            ],
            true
        );
    }


    /**
     * Return the name of class
     *
     * @return string
     */
    public static function nameClass():string
    {
        return static::class;
    }


}
