<?php
/**
 * Configurations element for tactical view.
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

use PandoraFMS\TacticalView\Element;

/**
 * Configurations, this class contain all logic for this section.
 */
class Configurations extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Configurations');
    }


    /**
     * Get total groups from automonitorization.
     *
     * @return string
     */
    public function getTotalGroups():string
    {
        $value = $this->valueMonitoring('total_groups');
        $total = round($value[0]['datos']);
        $image = html_print_image('images/Tactical_Groups.svg', true);
        $text = '<span class="subtitle">'.__('Groups').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total modules from automonitorization.
     *
     * @return string
     */
    public function getTotalModules():string
    {
        $value = $this->valueMonitoring('total_modules');
        $total = round($value[0]['datos']);
        $image = html_print_image('images/Tactical_Modules.svg', true);
        $text = '<span class="subtitle">'.__('Modules').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total policies from automonitorization.
     *
     * @return string
     */
    public function getTotalPolicies():string
    {
        $totalPolicies = db_get_value(
            'count(*)',
            'tpolicies'
        );

        $image = html_print_image('images/Tactical_Policies.svg', true);
        $text = '<span class="subtitle">'.__('Policies').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($totalPolicies, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total remote plugins from automonitorization.
     *
     * @return string
     */
    public function getTotalRemotePlugins():string
    {
        $totalPLugins = db_get_value(
            'count(*)',
            'tplugin',
            'plugin_type',
            1,
        );

        $sql = 'SELECT count(*) AS total FROM tplugin WHERE plugin_type = 1;';
        $rows = db_process_sql($sql);
        $totalPLugins = 0;
        if (is_array($rows) === true && count($rows) > 0) {
            $totalPLugins = $rows[0]['total'];
        }

        $image = html_print_image('images/Tactical_Plugins.svg', true);
        $text = '<span class="subtitle">'.__('Remote plugins').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($totalPLugins, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total module templates from automonitorization.
     *
     * @return string
     */
    public function getTotalModuleTemplate():string
    {
        $countModuleTemplates = db_get_value(
            'count(*)',
            'tnetwork_profile'
        );

        $image = html_print_image('images/Tactical_Module_template.svg', true);
        $text = '<span class="subtitle">'.__('Module templates').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($countModuleTemplates, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total not unit modules from automonitorization.
     *
     * @return string
     */
    public function getNotInitModules():string
    {
        $value = $this->valueMonitoring('total_notinit');
        $total = round($value[0]['datos']);
        $image = html_print_image('images/Tactical_Not_init_module.svg', true);
        $text = '<span class="subtitle">'.__('Not-init modules').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Get total unknow agents from automonitorization.
     *
     * @return string
     */
    public function getTotalUnknowAgents():string
    {
        $value = $this->valueMonitoring('total_unknown');
        $total = round($value[0]['datos']);
        $image = html_print_image('images/Tactical_Unknown_agent.svg', true);
        $text = '<span class="subtitle">'.__('Unknown agents').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


    /**
     * Returns the html of total events.
     *
     * @return string
     */
    public function getTotalEvents():string
    {
        $data = $this->valueMonitoring('last_events_24h');
        $total = $data[0]['datos'];
        $image = html_print_image('images/system_event.svg', true);
        $text = '<span class="subtitle">'.__('Events in last 24 hrs').'</span>';
        $number = html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l text_center',
                'style'   => '',
            ],
            true
        );
        $output = $image.$text.$number;
        return $output;
    }


}
