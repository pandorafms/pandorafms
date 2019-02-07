<?php

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

require 'agent_wizard.'.$wizard_section.'.php';
