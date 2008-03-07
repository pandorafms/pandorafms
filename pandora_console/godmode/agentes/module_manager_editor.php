<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation, version 2.


// General startup for established session
global $config;
check_login();

// Specific ACL check
if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

// Following variables come from module_manager.php -> configurar_agente.php :
//
// $form_moduletype: could be networkserver, pluginserver, wmiserver, or predictionserver

// Get form (GET)
$form_network_component = get_parameter_get("form_network_component", "");

// Using network component to fill some fields
if (($form_moduletype == "networkserver") && ($form_network_component != "")){
    // Preload data from template
    $row = get_db_row ("tnetwork_component", $id_nc , $form_network_component);
    if ($row == 0){
        unmanaged_error("Cannot load tnetwork_component reference from previous page");
    }
    $form_id_tipo_modulo = $row["type"];
    $form_id_module_group = $row["id_module_group"];
    $form_name = $row["name"];
    $form_descripcion = $row["description"];
    $form_tcp_send = $row["tcp_send"];
    $form_tcp_rcv = $row["tcp_rcv"];
    $form_tcp_port = $row["tcp_port"];
    $form_snmp_community = $row["snmp_community"];
    $form_snmp_oid = $row["snmp_oid"];
    $form_id_module_group = $row["id_module_group"];
    $form_interval = $row["module_interval"];
    $form_maxvalue = $row["max"];
    $form_minvalue = $row["min"];
    $form_export_id = $row["export_id"];
    $form_disabled = $row["disabled"];
    $form_post_process = $row["post_process"];
    $form_max_timeout = $row["max_timeout"];
    $form_max_timeout = "";
    $form_id_export = 0;
    $form_disabled = 0;
    $form_plugin_user = "";
    $form_plugin_pass = "";
    $form_plugin_parameter = "";
    $form_prediction_module = "";
    $form_id_plugin = "";
    $form_post_process = "";
} else {
    // Clean up specific network modules fields
    $form_name = "";
    $form_description = "";
    $form_id_module_group = 1;
    $form_id_tipo_modulo = 1;
    $form_post_process = "";
    $form_max_timeout = "";
    $form_minvalue = "";
    $form_maxvalue = "";
    $form_interval = "";
    $form_prediction_module = "";
    $form_id_plugin = "";
    $form_id_export = "";
    $form_disabled= "0";
    $form_tcp_send = "";
    $form_tcp_rcv = "";
    $form_tcp_port = "";
    $form_snmp_community = "public";
    $form_snmp_oid = "";
    $form_ip_target = $direccion_agente; // taken from configurar_agente.php
    $form_plugin_user = "";
    $form_plugin_pass = "";
    $form_plugin_parameter = "";
}

// WMI server
if ($form_moduletype == "wmiserver"){
    include $config["homedir"]."/godmode/agentes/module_manager_editor_wmi.php";
}

// Plugin server
if ($form_moduletype == "pluginserver"){
    include $config["homedir"]."/godmode/agentes/module_manager_editor_plugin.php";
}

// Network server
if ($form_moduletype == "networkserver"){
    include $config["homedir"]."/godmode/agentes/module_manager_editor_network.php";
}

// Data Server
if ($form_moduletype == "dataserver"){
    include $config["homedir"]."/godmode/agentes/module_manager_editor_data.php";
}

// Prediction server
if ($form_moduletype == "predictionserver"){
    include $config["homedir"]."/godmode/agentes/module_manager_editor_prediction.php";
}

