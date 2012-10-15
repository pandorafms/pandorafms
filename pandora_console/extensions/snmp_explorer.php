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
include_once($config['homedir'] . "/include/functions_agents.php");
require_once ('include/functions_modules.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_reporting.php');
require_once ('include/graphs/functions_utils.php');

function snmp_explorer() {

    $idAgent = (int) get_parameter('id_agente', 0);
    $ipAgent = db_get_value('direccion', 'tagente', 'id_agente', $idAgent);

    // Load global vars
    global $config;


    check_login ();

    $ip_target = (string) get_parameter ('ip_target', $ipAgent);
    $snmp_community = (string) get_parameter ('snmp_community', 'public');
    $snmp_version = get_parameter('snmp_version', '1');
    $snmp3_auth_user = get_parameter('snmp3_auth_user');
    $snmp3_security_level = get_parameter('snmp3_security_level');
    $snmp3_auth_method = get_parameter('snmp3_auth_method');
    $snmp3_auth_pass = get_parameter('snmp3_auth_pass');
    $snmp3_privacy_method = get_parameter('snmp3_privacy_method');
    $snmp3_privacy_pass = get_parameter('snmp3_privacy_pass');
    $tcp_port = (string) get_parameter ('tcp_port');

    //See if id_agente is set (either POST or GET, otherwise -1
    $id_agent = $idAgent;

    // Get passed variables
    $snmpwalk = (int) get_parameter("snmpwalk", 0);
    $create_modules = (int) get_parameter("create_modules", 0);

    $interfaces = array();

    if($snmpwalk) {	

	    $snmpis = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
				    $snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
				    $snmp3_privacy_method, $snmp3_privacy_pass, 0, ".1.3.6.1.2.1", $tcp_port);

	    if($snmpis === false) {
			    $snmpis = array();
	    }
	
	    $interfaces = array();

        // We get here only the interface part of the MIB, not full mib
	    foreach($snmpis as $key => $snmp) {

		    $data = explode(': ',$snmp);
		    $keydata = explode('::',$key);
		    $keydata2 = explode('.',$keydata[1]);
		
		    if(!isset($keydata2[1])) {
			    continue;
		    }
		
		    if(array_key_exists(1,$data)){
			    $interfaces[$keydata2[1]][$keydata2[0]]['type'] = $data[0];
			    $interfaces[$keydata2[1]][$keydata2[0]]['value'] = $data[1];
		    }else {
			    $interfaces[$keydata2[1]][$keydata2[0]]['type'] = '';
			    $interfaces[$keydata2[1]][$keydata2[0]]['value'] = $data[0];
		    }
		
		    $interfaces[$keydata2[1]][$keydata2[0]]['oid'] = $key;
		    $interfaces[$keydata2[1]][$keydata2[0]]['checked'] = 0;
	    }
		
	    unset($interfaces[0]);
    }

    if($create_modules) {	
        $id_snmp_serialize = get_parameter_post('id_snmp_serialize');
        $interfaces = unserialize_in_temp($id_snmp_serialize);
   	    
   	    if(!$interfaces) {
			$interfaces = array();
		}
		
	    $values = array();
	
	    if($tcp_port != ''){
		    $values['tcp_port'] = $tcp_port;
	    }
	    $values['snmp_community'] = $snmp_community;
	    $values['ip_target'] = $ip_target;
	    $values['tcp_send'] = $snmp_version;
	
	    if($snmp_version == '3') {
		    $values['plugin_user'] = $snmp3_auth_user;
		    $values['plugin_pass'] = $snmp3_auth_pass;
		    $values['plugin_parameter'] = $snmp3_auth_method;
		    $values['custom_string_1'] = $snmp3_privacy_method;
		    $values['custom_string_2'] = $snmp3_privacy_pass;
		    $values['custom_string_3'] = $snmp3_security_level;
	    }
			
	    $oids = array();
	    foreach($interfaces as $key => $interface) {
		    foreach($interface as $key2 => $module) {
			    $oid = get_parameter($key."-".$key2, '');
			    if($oid != '') {
				    $interfaces[$key][$key2]['checked'] = 1;
				    $oids[$key][] = $interfaces[$key][$key2]['oid'];
			    }
			    else {
				    $interfaces[$key][$key2]['checked'] = 0;
			    }
		    }
	    }
	    $modules = get_parameter('module', array());
	    $id_snmp = get_parameter('id_snmp');

	    if($id_snmp == false) {
		    ui_print_error_message (__('No modules selected'));
		    $id_snmp = array();
	    }
	
	    if(agents_get_name($id_agent) == false) {
		    ui_print_error_message (__('No agent selected or the agent does not exist'));
		    $id_snmp = array();
	    }
	
	    $result = false;
	
		$errors = array();
		$done = 0;

	    foreach($id_snmp as $id) {
			if (isset($interfaces[$id]['ifName']) && $interfaces[$id]['ifName']['value'] != ""){
					$ifname = $interfaces[$id]['ifName']['value'];
			}
			else if (isset($interfaces[$id]['ifDescr']) && $interfaces[$id]['ifDescr']['value'] != ""){
					$ifname = $interfaces[$id]['ifDescr']['value'];
			}
		    foreach($modules as $module) {
         			
			    $oid_array = explode('.',$module);
			    $oid_array[count($oid_array)-1] = $id;
			    $oid = implode('.',$oid_array);
			
			    // Get the name
			    $name_array = explode('::',$oid_array[0]);
			    $name = $name_array[1] . "_" . $ifname;
			
                // Clean the name
                $name = str_replace  ( "\""  , "" , $name);

                //  Proc moduletypes
                if (preg_match  ( "/Status/", $name_array[1]))
                    $module_type = 18;

                elseif (preg_match  ( "/Present/", $name_array[1]))
                    $module_type = 18;

                elseif (preg_match  ( "/PromiscuousMode/", $name_array[1]))
                    $module_type = 18;

                //  String moduletypes
                elseif (preg_match  ( "/Alias/", $name_array[1]))
                    $module_type = 17;

                elseif (preg_match  ( "/Address/", $name_array[1]))
                    $module_type = 17;

                elseif (preg_match  ( "/Name/", $name_array[1]))
                    $module_type = 17;

                elseif (preg_match  ( "/Specific/", $name_array[1]))
                    $module_type = 17;

                elseif (preg_match  ( "/Descr/", $name_array[1]))
                    $module_type = 17;

                // Specific counters (ends in s)
                elseif (preg_match  ( "/s$/", $name_array[1]))
                    $module_type = 16;
            
                // Otherwise, numeric
                else
                    $module_type = 15;			
			
			    $values['id_tipo_modulo'] = $module_type;
			    $values['descripcion'] = io_safe_input("(" . $ip_target." - ".$name . ") " . $interfaces[$id]['ifDescr']['value']);
			
			    $values['snmp_oid'] = $oid;
			    $values['id_modulo'] = 2;
						
			    $result = modules_create_agent_module ($id_agent, io_safe_input($name), $values);
			    
			    if(is_error($result)) {
					if(!isset($errors[$result])) {
						$errors[$result] = 0;
					}
					$errors[$result]++;
				}
				else {
					$done++;
				}
		    }
	    }
	    
	    if($done > 0) {
			ui_print_success_message(__('Successfully modules created')." ($done)");
		}
		
		if(!empty($errors)) {
			$msg = __('Could not be created').':';

		
			foreach($errors as $code => $number) {
				switch($code) {
					case ERR_EXIST:
						$msg .= '<br>'.__('Another module already exists with the same name')." ($number)";
						break;
					case ERR_INCOMPLETE:
						$msg .= '<br>'.__('Some required fields are missed').': ('.__('name').') '." ($number)";
						break;
					case ERR_DB:
					case ERR_GENERIC:
					default:
						$msg .= '<br>'.__('Processing error')." ($number)";
						break;
				}
				
			}
			
			ui_print_error_message($msg);
		
		}
    }

    // Create the interface list for the interface
    $interfaces_list = array();
    foreach($interfaces as $interface){
        // Get the interface name, removing " " characters and avoid "blank" interfaces
        if (isset($interface['ifName']) && $interface['ifName']['value'] != ""){
                $ifname = $interface['ifName']['value'];
        }
        else if (isset($interface['ifDescr']) && $interface['ifDescr']['value'] != ""){
                $ifname = $interface['ifDescr']['value'];
        }
        else {
                continue;
        }

	    $interfaces_list[$interface['ifIndex']['value']] = str_replace  ( "\""  , "" , $ifname);

    }

    echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
    echo "<form method='post' id='walk_form' action='index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=extension&id_agente=$id_agent&id_extension=snmp_explorer'>";

    $table->width = '98%';

    $table->valign[0] = 'top';
    $table->valign[1] = 'top';

    $table->data[0][0] = '<b>' . __('Target IP') . '</b>';
    $table->data[0][1] = html_print_input_text ('ip_target', $ip_target, '', 15, 60, true);

    $table->data[0][2] = '<b>' . __('Port') . '</b>';
    $table->data[0][3] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);

    $snmp_versions['1'] = 'v. 1';
    $snmp_versions['2'] = 'v. 2';
    $snmp_versions['2c'] = 'v. 2c';
    $snmp_versions['3'] = 'v. 3';

    $table->data[1][0] = '<b>' . __('SNMP community') . '</b>';
    $table->data[1][1] = html_print_input_text ('snmp_community', $snmp_community, '', 15, 60, true);

    $table->data[1][2] = '<b>' . _('SNMP version') . '</b>';
    $table->data[1][3] = html_print_select ($snmp_versions, 'snmp_version', $snmp_version, '', '', '', true, false, false, '');

    $table->data[1][3] .= '<div id="spinner_modules" style="float: left; display: none;">' . html_print_image("images/spinner.gif", true) . '</div>';
    html_print_input_hidden('snmpwalk', 1);

    html_print_table($table);

    unset($table);

    //SNMP3 OPTIONS 
    $table->width = '98%';

    $table->valign[0] = 'top';
    $table->valign[1] = 'top';

    $table->data[2][1] = '<b>'.__('Auth user').'</b>';
    $table->data[2][2] = html_print_input_text ('snmp3_auth_user', $snmp3_auth_user, '', 15, 60, true);
    $table->data[2][3] = '<b>'.__('Auth password').'</b>';
    $table->data[2][4] = html_print_input_text ('snmp3_auth_pass', $snmp3_auth_pass, '', 15, 60, true);
    $table->data[2][4] .= html_print_input_hidden('active_snmp_v3', 0, true);

    $table->data[5][0] = '<b>'.__('Privacy method').'</b>';
    $table->data[5][1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true);
    $table->data[5][2] = '<b>'.__('privacy pass').'</b>';
    $table->data[5][3] = html_print_input_text ('snmp3_privacy_pass', $snmp3_privacy_pass, '', 15, 60, true);

    $table->data[6][0] = '<b>'.__('Auth method').'</b>';
    $table->data[6][1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'snmp3_auth_method', $snmp3_auth_method, '', '', '', true);
    $table->data[6][2] = '<b>'.__('Security level').'</b>';
    $table->data[6][3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
	    'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'snmp3_security_level', $snmp3_security_level, '', '', '', true);

    if($snmp_version == 3) {
	    echo '<div id="snmp3_options">';
    }else {
	    echo '<div id="snmp3_options" style="display: none;">';
    }
    html_print_table($table);
    echo '</div>';
    	    
    echo "<div style='text-align:right; width:".$table->width."'>";
    echo '<span id="oid_loading" class="invisible">' . html_print_image("images/spinner.gif", true) . '</span>';
    html_print_submit_button(__('SNMP Walk'), 'snmp_walk', false, array('class' => 'sub next'));
    echo "</div>";
    
    if($snmpwalk && !$snmpis) {
	    echo '<span id="no_snmp" style="margin-left:50px" class="error">'.__('Unable to do SNMP walk').'</span>';
    }

    unset($table);

    echo "</form>";

    //echo "</tr></table>";
    //echo "</form>";

    if(!empty($interfaces_list)){
	    echo '<span id="form_interfaces">';
	    echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
	    echo "<form method='post' action='index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=extension&id_agente=$id_agent&id_extension=snmp_explorer'>";
		
	    $id_snmp_serialize = serialize_in_temp($interfaces, $config['id_user']."_snmp");
	    html_print_input_hidden('id_snmp_serialize', $id_snmp_serialize);

	    html_print_input_hidden('create_modules', 1);
	    html_print_input_hidden('ip_target', $ip_target);
	    html_print_input_hidden('tcp_port', $tcp_port);
	    html_print_input_hidden('snmp_community', $snmp_community);
	    html_print_input_hidden('snmp_version', $snmp_version);
	    html_print_input_hidden('snmp3_auth_user', $snmp3_auth_user);
	    html_print_input_hidden('snmp3_auth_pass', $snmp3_auth_pass);
	    html_print_input_hidden('snmp3_auth_method', $snmp3_auth_method);
	    html_print_input_hidden('snmp3_privacy_method', $snmp3_privacy_method);
	    html_print_input_hidden('snmp3_privacy_pass', $snmp3_privacy_pass);
	    html_print_input_hidden('snmp3_security_level', $snmp3_security_level);
		
	    $table->width = '98%';

	    $table->valign[0] = 'top';
	    $table->valign[1] = 'top';

	    //Agent selector
	    $table->data[0][0] = '<b>'.__('Interfaces').'</b>';
	    $table->data[0][1] = '';
	    $table->data[0][2] = '<b>'.__('Modules').'</b>';

	    $table->data[1][0] = html_print_select ($interfaces_list, 'id_snmp[]', 0, false, '', '', true, true, true, '', false, 'width:200px;');
	    $table->data[1][1] = html_print_image('images/darrowright.png', true);
	    $table->data[1][2] = html_print_select (array (), 'module[]', 0, false, '', 0, true, true, true, '', false, 'width:200px;');
    	$table->data[1][2] .= html_print_input_hidden('agent', $id_agent, true);
	
	    html_print_table($table);

	    echo "<div style='text-align:right; width:".$table->width."'>";
	    html_print_submit_button(__('Create modules'), '', false, array('class' => 'sub add'));
		echo "</div>";
	    unset($table);

	    echo "</form>";
	    echo '</div>';
    }

    ui_require_jquery_file ('pandora.controls');
    ui_require_jquery_file ('ajaxqueue');
    ui_require_jquery_file ('bgiframe');
    ui_require_jquery_file ('autocomplete');

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready (function () {
	var inputActive = true;

	$(document).data('text_for_module', $("#none_text").html());

	$("#id_snmp").change(snmp_changed_by_multiple_snmp);
	
	$("#snmp_version").change(function () {
		if (this.value == "3") {
			$("#snmp3_options").css("display", "");
		}
		else {
			$("#snmp3_options").css("display", "none");
		}
	});

	$("#walk_form").submit(function(){
		$("#submit-snmp_walk").disable ();
		$("#oid_loading").show ();
		$("#no_snmp").hide ();
		$("#form_interfaces").hide ();
	});	
	
});

function snmp_changed_by_multiple_snmp (event, id_snmp, selected) {
	var idSNMP = Array();
	
	jQuery.each ($("#id_snmp option:selected"), function (i, val) {
		idSNMP.push($(val).val());
	});
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));

	jQuery.post ('ajax.php', 
				{"page" : "godmode/agentes/agent_manager",
				 "get_modules_json_for_multiple_snmp": 1,
				 "id_snmp[]": idSNMP,
				 "id_snmp_serialize": $("#hidden-id_snmp_serialize").val()
				 },
				 function (data) {
					 $('#module').empty ();
					 c = 0;
					 jQuery.each (data, function (i, val) {
								  s = js_html_entity_decode(val);
								  $('#module').append ($('<option></option>').html (s).attr ("value", i));
								  $('#module').fadeIn ('normal');
								  c++;
								  });
					 if(c == 0){
						 if (typeof($(document).data('text_for_module')) != 'undefined') {
							 $('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
						 }
						 else {
							 if (typeof(data['any_text']) != 'undefined') {
								 $('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
							 }
							 else {
								 var anyText = $("#any_text").html(); //Trick for catch the translate text.
								 
								 if (anyText == null) {
									 anyText = 'Any';
								 }
								 
								 $('#module').append ($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
							 }
						 }
					 }
					 if (selected != undefined)
					 $('#module').attr ('value', selected);
					 $('#module').attr ('disabled', 0);
				 },
				 "json"
				 );
}

/* ]]> */
</script>

<?php
}


extensions_add_godmode_tab_agent('snmp_explorer', __('SNMP Explorer'), 'images/snmp_wizard.png', 'snmp_explorer', "v1r1");
?>
