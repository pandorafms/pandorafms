<?php
/**
 * SnmpTraps element for tactical view.
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
 * SnmpTraps, this class contain all logic for this section.
 */
class SnmpTraps extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('SNMP Traps');
        $this->ajaxMethods = [
            'getQueues',
            'getTotalSources',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'queues'     => [
                'id'     => 'total-queues',
                'method' => 'getQueues',
            ],
            'total-snmp' => [
                'id'     => 'total-snmp',
                'method' => 'getTotalSources',
            ],
        ];
    }


    /**
     * Check if snmp traps module exist.
     *
     * @return boolean
     */
    public function isEnabled():bool
    {
        if (empty($this->monitoringAgent) === true) {
            return false;
        }

        $existModule = modules_get_agentmodule_id(io_safe_input('snmp_trap_queue'), $this->monitoringAgent['id_agente']);
        if ($existModule === false) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Returns the html of queues traps.
     *
     * @return string
     */
    public function getQueues():string
    {
        if ($this->isEnabled() === true) {
            $value = $this->valueMonitoring('snmp_trap_queue');
            if (isset($value[0]['data']) === true) {
                $total = round($value[0]['data']);
            } else {
                $total = __('N/A');
            }
        } else {
            $total = __('N/A');
        }

        return html_print_div(
            [
                'content' => $total,
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 10px 10px;',
                'id'      => 'total-queues',
            ],
            true
        );
    }


    /**
     * Returns the html of total sources traps.
     *
     * @return string
     */
    public function getTotalSources():string
    {
        if ($this->isEnabled() === true) {
            $value = $this->valueMonitoring('total_trap');
            if (isset($value[0]['data']) === true) {
                $total = round($value[0]['data']);
            } else {
                $total = __('N/A');
            }
        } else {
            $total = __('N/A');
        }

        return html_print_div(
            [
                'content' => $total,
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 10px 10px;',
                'id'      => 'total-snmp',
            ],
            true
        );
    }


}
