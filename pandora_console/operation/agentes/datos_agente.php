<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

require_once 'include/functions_modules.php';

$module_id = get_parameter_get('id', 0);
$period = get_parameter('period', SECONDS_1DAY);
$group = agents_get_agentmodule_group($module_id);
$agentId = get_parameter('id_agente');
$freestring = get_parameter('freestring');

// Select active connection
$connection = get_parameter('connection', 'main');
if ($connection == 'history' && $config['history_db_enabled'] == 1) {
    if (! isset($config['history_db_connection']) || $config['history_db_connection'] === false) {
        $config['history_db_connection'] = db_connect($config['history_db_host'], $config['history_db_name'], $config['history_db_user'], io_output_password($config['history_db_pass']), $config['history_db_port'], false);
    }

    $connection_handler = $config['history_db_connection'];
} else {
    $connection_handler = $config['dbconnection'];
}

$selection_mode = get_parameter('selection_mode', 'fromnow');
$date_from = (string) get_parameter('date_from', date(DATE_FORMAT));
$time_from = (string) get_parameter('time_from', date(TIME_FORMAT));
$date_to = (string) get_parameter('date_to', date(DATE_FORMAT));
$time_to = (string) get_parameter('time_to', date(TIME_FORMAT));

if (! check_acl($config['id_user'], $group, 'AR') || $module_id == 0) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Data view'
    );
    include 'general/noaccess.php';
    return;
}

$table = new stdClass();
$table->cellpadding = 3;
$table->cellspacing = 3;
$table->width = '98%';
$table->class = 'databox';
$table->head = [];
$table->data = [];
$table->align = [];
$table->size = [];


$moduletype_name = modules_get_moduletype_name(modules_get_agentmodule_type($module_id));

$offset = (int) get_parameter('offset');
$block_size = (int) $config['block_size'];

// The "columns" array is the number(and definition) of columns in the report:
// $columns = array(
// "COLUMN1" => array(ROW_FROM_DB_TABLE, FUNCTION_NAME_TO_FORMAT_THE_DATA, "align"=>COLUMN_ALIGNMENT, "width"=>COLUMN_WIDTH)
// "COLUMN2" => array(ROW_FROM_DB_TABLE, FUNCTION_NAME_TO_FORMAT_THE_DATA, "align"=>COLUMN_ALIGNMENT, "width"=>COLUMN_WIDTH)
// ....
// )
//
// For each row from the query, and for each column, we'll call the FUNCTION passing as argument
// the value of the ROW.
//
$columns = [];

$datetime_from = strtotime($date_from.' '.$time_from);
$datetime_to = strtotime($date_to.' '.$time_to);

if ($moduletype_name == 'log4x') {
    $table->width = '100%';
    $sql_freestring = '%'.$freestring.'%';

    if ($selection_mode == 'fromnow') {
        $sql_body = sprintf("FROM tagente_datos_log4x WHERE id_agente_modulo = %d AND message like '%s' AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, $sql_freestring, (get_system_time() - $period));
    } else {
        $sql_body = sprintf("FROM tagente_datos_log4x WHERE id_agente_modulo = %d AND message like '%s' AND utimestamp >= %d AND utimestamp <= %d ORDER BY utimestamp DESC", $module_id, $sql_freestring, $datetime_from, $datetime_to);
    }

    $columns = [
        'Sev'        => [
            'severity', 'modules_format_data', 'align' => 'center', 'width' => '70px'
        ],
        'Message'    => [
            'message', 'modules_format_verbatim', 'align' => 'left', 'width' => '45%'
        ],
        'StackTrace' => [
            'stacktrace', 'modules_format_verbatim', 'align' => 'left', 'width' => '50%'
        ],
    ];
} else if (preg_match('/string/', $moduletype_name)) {
    $sql_freestring = '%'.$freestring.'%';
    if ($selection_mode == 'fromnow') {
        $sql_body = sprintf(" FROM tagente_datos_string WHERE id_agente_modulo = %d AND datos like '%s' AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, $sql_freestring, (get_system_time() - $period));
    } else {
        $sql_body = sprintf(" FROM tagente_datos_string WHERE id_agente_modulo = %d AND datos like '%s' AND utimestamp >= %d AND utimestamp <= %d ORDER BY utimestamp DESC", $module_id, $sql_freestring, $datetime_from, $datetime_to);
    }

    $columns = [
        'Data' => [
            'datos',                 'modules_format_data',                 'align' => 'left'
        ],
        'Time' => [
            'utimestamp',             'modules_format_time',         'align' => 'center'
        ],
    ];
} else {
    if ($selection_mode == 'fromnow') {
        $sql_body = sprintf(' FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC', $module_id, (get_system_time() - $period));
    } else {
        $sql_body = sprintf(' FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp >= %d AND utimestamp <= %d ORDER BY utimestamp DESC', $module_id, $datetime_from, $datetime_to);
    }

    $columns = [
        'Data' => [
            'datos',                 'modules_format_data',             'align' => 'left'
        ],
        'Time' => [
            'utimestamp',             'modules_format_time',             'align' => 'center'
        ],
    ];
}

$sql_body = io_safe_output($sql_body);
// Clean all codification characters
$sql = 'SELECT * '.$sql_body;
$sql_count = 'SELECT count(*) '.$sql_body;

$count = db_get_value_sql($sql_count, $connection_handler);

switch ($config['dbtype']) {
    case 'mysql':
        $sql .= ' LIMIT '.$offset.','.$block_size;
    break;

    case 'postgresql':
        $sql .= ' LIMIT '.$block_size.' OFFSET '.$offset;
    break;

    case 'oracle':
        $set = [];
        $set['limit'] = $block_size;
        $set['offset'] = $offset;
        $sql = oracle_recode_query($sql, $set);
    break;
}

$result = db_get_all_rows_sql($sql, false, true, $connection_handler);
if ($result === false) {
    $result = [];
}

if (($config['dbtype'] == 'oracle') && ($result !== false)) {
    for ($i = 0; $i < count($result); $i++) {
        unset($result[$i]['rnum']);
    }
}

$header_title = __('Received data from').' '.modules_get_agentmodule_agent_alias($module_id).' / '.modules_get_agentmodule_name($module_id);

echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$agentId.'&tab=data_view&id='.$module_id."'>";

echo '<h4>'.$header_title;
if ($config['history_db_enabled'] == 1) {
    echo '&nbsp;';
    html_print_select(['main' => __('Main database'), 'history' => __('History database')], 'connection', $connection);
    ui_print_help_tip(__('Switch between the main database and the history database to retrieve module data'));
}

echo '</h4>';

$formtable = new stdClass();
$formtable->width = '98%';
$formtable->class = 'databox';
$formtable->data = [];
$formtable->size = [];
$formtable->size[0] = '40%';
$formtable->size[1] = '20%';
$formtable->size[2] = '30%';

$formtable->data[0][0] = html_print_radio_button_extended('selection_mode', 'fromnow', '', $selection_mode, false, '', 'class="mrgn_right_15px"', true).__('Choose a time from now');
$formtable->data[0][1] = html_print_extended_select_for_time('period', $period, '', '', '0', 10, true);

$formtable->data[1][0] = html_print_radio_button_extended('selection_mode', 'range', '', $selection_mode, false, '', 'class="mrgn_right_15px"', true).__('Specify time range');
$formtable->data[1][1] = __('Timestamp from:');

$formtable->data[1][2] = html_print_input_text('date_from', $date_from, '', 10, 10, true);
$formtable->data[1][2] .= html_print_input_text('time_from', $time_from, '', 9, 7, true);

$formtable->data[1][1] .= '<br />';
$formtable->data[1][1] .= __('Timestamp to:');

$formtable->data[1][2] .= '<br />';
$formtable->data[1][2] .= html_print_input_text('date_to', $date_to, '', 10, 10, true);
$formtable->data[1][2] .= html_print_input_text('time_to', $time_to, '', 9, 7, true);

if (preg_match('/string/', $moduletype_name) || $moduletype_name == 'log4x') {
    $formtable->data[2][0] = __('Free text for search');
    $formtable->data[2][1] = html_print_input_text('freestring', $freestring, '', 20, 30, true);
}

html_print_table($formtable);

echo '<div class="action-buttons w98p">';
html_print_submit_button(__('Update'), 'updbutton', false, 'class="sub upd"');
echo '</div>';

echo '</form><br />';

$table->width = '98%';

$index = 0;
foreach ($columns as $col => $attr) {
    $table->head[$index] = $col;

    if (isset($attr['align'])) {
        $table->align[$index] = $attr['align'];
    }

    if (isset($attr['width'])) {
        $table->size[$index] = $attr['width'];
    }

    $index++;
}

$id_type_web_content_string = db_get_value(
    'id_tipo',
    'ttipo_modulo',
    'nombre',
    'web_content_string'
);

foreach ($result as $row) {
    $data = [];

    $is_web_content_string = (bool) db_get_value_filter(
        'id_agente_modulo',
        'tagente_modulo',
        [
            'id_agente_modulo' => $row['id_agente_modulo'],
            'id_tipo_modulo'   => $id_type_web_content_string,
        ]
    );

    foreach ($columns as $col => $attr) {
        if ($attr[1] != 'modules_format_data') {
            $data[] = $attr[1]($row[$attr[0]]);
        } else if (($config['command_snapshot']) && (preg_match("/[\n]+/i", $row[$attr[0]]))) {
            // Its a single-data, multiline data (data snapshot) ?
            // Detect string data with \n and convert to <br>'s.
            $datos = preg_replace('/\n/i', '<br>', $row[$attr[0]]);
            $datos = preg_replace('/\s/i', '&nbsp;', $datos);

            // Because this print_table monster, I cannot format properly this cells.
            $datos = "<span class='mono'>".$datos.'</span>';

            // I dont why, but using index (value) method, data is automatically converted to html entities ¿?
            $data[$attr[1]] = $datos;
        } else if ($is_web_content_string) {
            // Fixed the goliat sends the strings from web
            // without HTML entities.
            $data[$attr[1]] = io_safe_input($row[$attr[0]]);
        } else {
            // Just a string of alphanumerical data... just do print
            // Fixed the data from Selenium Plugin.
            if ($row[$attr[0]] != strip_tags($row[$attr[0]])) {
                $data[$attr[1]] = io_safe_input($row[$attr[0]]);
            } else {
                $data[$attr[1]] = $row[$attr[0]];
            }
        }
    }

    array_push($table->data, $data);
    if (count($table->data) > 200) {
        break;
    }
}

if (empty($table->data)) {
    ui_print_error_message(__('No available data to show'));
} else {
    ui_pagination($count);
    html_print_table($table);
    unset($table);
}

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
    $("#text-time_from, #text-time_to").timepicker({
        showSecond: true,
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>'});
    
    $("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
    
    $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
});
</script>

