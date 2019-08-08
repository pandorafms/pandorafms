<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'IR') && ! check_acl($config['id_user'], 0, 'IW') && ! check_acl($config['id_user'], 0, 'IM')) {
    // Doesn't have access to this page
    db_pandora_audit('ACL Violation', 'Trying to access IntegriaIMS ticket creation');
    include 'general/noaccess.php';
    exit;
}

ui_print_page_header(__('Create Integria IMS Incident'), '', false, '', false, '');

if ($config['integria_enabled'] == 0) {
    ui_print_error_message(__('Integria integration must be enabled in Pandora setup'));
    return;
}

// OBTIENE PARAMETROS A TRAVES DE API DE INTEGRIA
integria_api_call('http://172.16.131.135/integria/include/api.php?', 'admin', 'integria', '1234', 'create_incident', ['Titulo de la incidencia numero5,2,2,Descripcion de la incidencia', '1:2:3', '1', 'copyto@someone.com', 'admin', '0', '1']);

// OBTIENE PARAMETROS INTORDUCIDOS POR USUARIO EN LA VISTA O CARGADOS EN LA MISMA A TRAVES DE LA API PREVIAMENTE
$create_incident = (int) get_parameter('create_incident', 0);
$incident_group_id = (int) get_parameter('group');
$incident_default_criticity_id = (int) get_parameter('default_criticity');
$incident_default_owner = (int) get_parameter('default_owner');
$incident_type = (int) get_parameter('incident_type');
$incident_title = get_parameter('incident_title');
$incident_content = get_parameter('incident_content');

hd(event_response_get_macro($incident_content));


// HACE PETICION A API DE INTEGRIA PARA CREAR TICKET
$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filters';
$table->head = [];

$table->data = [];
$table->size = [];
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '90%';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select(
    $templates,
    'group',
    '',
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[1][0] = __('Default Criticity');
$table->data[1][1] = html_print_select(
    $templates,
    'default_criticity',
    '',
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[2][0] = __('Default Owner');
$table->data[2][1] = html_print_select(
    $templates,
    'default_owner',
    '',
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[0][2] = __('Incident Type');
$table->data[0][3] = html_print_select(
    $templates,
    'incident_type',
    '',
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[1][2] = __('Incident title');
$table->data[1][3] = html_print_input_text(
    'incident_title',
    $reportName,
    __('Name'),
    50,
    100,
    true,
    false,
    true
);

$table->data[2][2] = __('Incident content');
$table->data[2][3] = html_print_input_text(
    'incident_content',
    $reportName,
    '',
    50,
    100,
    true,
    false,
    true
);

echo '<form name="create_integria_incident_form" method="POST">';
html_print_table($table);
html_print_submit_button(__('Create'), 'accion', false, 'class="sub wand"');
echo '</form>';


function integria_api_call($api_url, $user, $user_pass, $api_pass, $operation, $params_array)
{
    $params_string = implode(',', $params_array);

    $url_data = [
        'user'      => $user,
        'user_pass' => $user_pass,
        'pass'      => $api_pass,
        'op'        => $operation,
        'params'    => $params_string,
    ];

    $url = sprintf(
        "$api_url%s",
        http_build_query($url_data, '', '&amp;')
    );

    hd($url);
    // ob_start();
    // $out = fopen('php://output', 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    // curl_setopt($ch, CURLOPT_STDERR, $out);
    $result = curl_exec($ch);

    // fclose($out);
    // $debug = ob_get_clean();
    // hd($debug);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    hd('http status: '.$http_status);

    $error = false;

    if ($result === false) {
        $error = curl_error($ch);
    }

    curl_close($ch);

    if ($error !== false || $http_status !== 200) {
        if ($error !== false) {
            ui_print_error_message(__('API request failed. Please check Integria\'s access credentials in Pandora setup.'));
        } else {
            // MOSTRAR MENSAJE DE EXITO EN LA PAGINA
        }

        return;
    }
}


/**
 * Replace macros.
 * If server_id > 0, it's a metaconsole query.
 *
 * @param integer $event_id    Event identifier.
 * @param integer $response_id Event response identifier.
 * @param integer $server_id   Node identifier (for metaconsole).
 * @param boolean $history     Use the history database or not.
 *
 * @return string The response text with the macros applied.
 */
function event_response_get_macro(
    int $event_id,
    $value
) {
    include_once 'include/functions_events.php';

    global $config;

    // If server_id > 0, it's a metaconsole query.
    // $meta = $server_id > 0 || is_metaconsole();
    $meta = false;
    $event = db_get_row('tevento', 'id_evento', $event_id);

    // $event_response = db_get_row('tevent_response', 'id', $response_id);
    $target = io_safe_output($event_response['target']);

    // Substitute each macro.
    if (strpos($target, '_agent_address_') !== false) {
        if ($meta) {
            $agente_table_name = 'tmetaconsole_agent';
            $filter = [
                'id_tagente'            => $event['id_agente'],
                'id_tmetaconsole_setup' => $server_id,
            ];
        } else {
            $agente_table_name = 'tagente';
            $filter = ['id_agente' => $event['id_agente']];
        }

        $ip = db_get_value_filter('direccion', $agente_table_name, $filter);
        // If agent has not an IP, display N/A.
        if ($ip === false) {
            $ip = __('N/A');
        }

        $return = str_replace('_agent_address_', $ip, $value);
    }

    if (strpos($target, '_agent_id_') !== false) {
        $target = str_replace('_agent_id_', $event['id_agente'], $target);
    }

    if ((strpos($target, '_module_address_') !== false)
        || (strpos($target, '_module_name_') !== false)
    ) {
        if ($event['id_agentmodule'] !== 0) {
            if ($meta) {
                $server = metaconsole_get_connection_by_id($server_id);
                metaconsole_connect($server);
            }

            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $target = str_replace('_module_address_', $module['ip_target'], $target);
            if (empty($module['nombre'])) {
                $module['nombre'] = __('N/A');
            }

            $target = str_replace(
                '_module_name_',
                io_safe_output($module['nombre']),
                $target
            );

            if ($meta) {
                metaconsole_restore_db();
            }
        } else {
            $target = str_replace('_module_address_', __('N/A'), $target);
            $target = str_replace('_module_name_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_event_id_') !== false) {
        $target = str_replace('_event_id_', $event['id_evento'], $target);
    }

    if (strpos($target, '_user_id_') !== false) {
        if (!empty($event['id_usuario'])) {
            $target = str_replace('_user_id_', $event['id_usuario'], $target);
        } else {
            $target = str_replace('_user_id_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_group_id_') !== false) {
        $target = str_replace('_group_id_', $event['id_grupo'], $target);
    }

    if (strpos($target, '_group_name_') !== false) {
        $target = str_replace(
            '_group_name_',
            groups_get_name($event['id_grupo'], true),
            $target
        );
    }

    if (strpos($target, '_event_utimestamp_') !== false) {
        $target = str_replace(
            '_event_utimestamp_',
            $event['utimestamp'],
            $target
        );
    }

    if (strpos($target, '_event_date_') !== false) {
        $target = str_replace(
            '_event_date_',
            date($config['date_format'], $event['utimestamp']),
            $target
        );
    }

    if (strpos($target, '_event_text_') !== false) {
        $target = str_replace(
            '_event_text_',
            events_display_name($event['evento']),
            $target
        );
    }

    if (strpos($target, '_event_type_') !== false) {
        $target = str_replace(
            '_event_type_',
            events_print_type_description($event['event_type'], true),
            $target
        );
    }

    if (strpos($target, '_alert_id_') !== false) {
        $target = str_replace(
            '_alert_id_',
            empty($event['is_alert_am']) ? __('N/A') : $event['is_alert_am'],
            $target
        );
    }

    if (strpos($target, '_event_severity_id_') !== false) {
        $target = str_replace('_event_severity_id_', $event['criticity'], $target);
    }

    if (strpos($target, '_event_severity_text_') !== false) {
        $target = str_replace(
            '_event_severity_text_',
            get_priority_name($event['criticity']),
            $target
        );
    }

    if (strpos($target, '_module_id_') !== false) {
        $target = str_replace('_module_id_', $event['id_agentmodule'], $target);
    }

    if (strpos($target, '_event_tags_') !== false) {
        $target = str_replace('_event_tags_', $event['tags'], $target);
    }

    if (strpos($target, '_event_extra_id_') !== false) {
        if (empty($event['id_extra'])) {
            $target = str_replace('_event_extra_id_', __('N/A'), $target);
        } else {
            $target = str_replace('_event_extra_id_', $event['id_extra'], $target);
        }
    }

    if (strpos($target, '_event_source_') !== false) {
        $target = str_replace('_event_source_', $event['source'], $target);
    }

    if (strpos($target, '_event_instruction_') !== false) {
        $target = str_replace(
            '_event_instruction_',
            events_display_instructions($event['event_type'], $event, false),
            $target
        );
    }

    if (strpos($target, '_owner_user_') !== false) {
        if (empty($event['owner_user'])) {
            $target = str_replace('_owner_user_', __('N/A'), $target);
        } else {
            $target = str_replace('_owner_user_', $event['owner_user'], $target);
        }
    }

    if (strpos($target, '_event_status_') !== false) {
        $event_st = events_display_status($event['estado']);
        $target = str_replace('_event_status_', $event_st['title'], $target);
    }

    if (strpos($target, '_group_custom_id_') !== false) {
        $group_custom_id = db_get_value_sql(
            sprintf(
                'SELECT custom_id FROM tgrupo WHERE id_grupo=%s',
                $event['id_grupo']
            )
        );
        $event_st = events_display_status($event['estado']);
        $target = str_replace('_group_custom_id_', $group_custom_id, $target);
    }

    // Parse the event custom data.
    if (!empty($event['custom_data'])) {
        $custom_data = json_decode(base64_decode($event['custom_data']));
        foreach ($custom_data as $key => $value) {
            $target = str_replace('_customdata_'.$key.'_', $value, $target);
        }
    }

    // This will replace the macro with the current logged user.
    if (strpos($target, '_current_user_') !== false) {
        $target = str_replace('_current_user_', $config['id_user'], $target);
    }

    return $target;
}
