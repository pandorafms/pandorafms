<?php
/**
 * Wizard for SNMP / WMI discover.
 *
 * @category   Agent Wizard
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
global $config;
// Require needed class.
require_once $config['homedir'].'/include/class/AgentWizard.class.php';
// This page.
hd($_REQUEST);
$ajaxPage = 'godmode/agentes/agent_wizard';
// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $obj = new AgentWizard($ajaxPage);
} catch (Exception $e) {
    if (is_ajax()) {
        echo json_encode(['error' => '[AgentWizard]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[AgentWizard]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if (is_ajax()) {
    $method = get_parameter('method');

    if (method_exists($obj, $method) === true) {
        $obj->{$method}();
    } else {
        $obj->error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $obj->run();
}


// OLD. FOR ERASE.
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
/*
    global $config;

    check_login();

    $wizard_section = get_parameter('wizard_section', 'snmp_interfaces_explorer');
    $idAgent = (int) get_parameter('id_agente', 0);
    $ipAgent = db_get_value('direccion', 'tagente', 'id_agente', $idAgent);

    switch ($wizard_section) {
    case 'snmp_interfaces_explorer':
        $snmp_interfaces_explorer_style = 'font-weight: bold;';
        $wmi_explorer_style = '';
    break;

    case 'wmi_explorer':
        $snmp_interfaces_explorer_style = '';
        $wmi_explorer_style = 'font-weight: bold;';
    break;

    case 'snmp_explorer':
        $snmp_interfaces_explorer_style = '';
        $wmi_explorer_style = 'font-weight: bold;';
    break;
    }

    /*
    echo "<div style='text-align: right; width: 98%; padding-top: 10px; padding-bottom: 10px;'>";
    echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_interfaces_explorer&id_agente=$idAgent' style='" . $snmp_interfaces_explorer_style . "'>" . __('SNMP Interfaces explorer') . "</a>";
    echo " | ";
    echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=wmi_explorer&id_agente=$idAgent' style='" . $wmi_explorer_style . "'>" . __('WMI explorer') . "</a>";
    echo "</div>";
*/

// require 'agent_wizard.'.$wizard_section.'.php';
?>

<script language="javascript" type="text/javascript">
/*
function satellite_remote_warn(id_satellite, remote)
{
    if(!remote)
    {
        $('#server_to_exec option[value='+id_satellite+']').prop('disabled', true);
        $('#satellite_remote_tip').removeAttr("style").show();
    }
    else
    {
        $('#satellite_remote_tip').removeAttr("style").hide();
    }

}

</script>