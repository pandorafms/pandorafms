<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;
require_once 'include/config.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM')
    && ! is_user_admin($config['id_user'])
) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

$update_config = get_parameter('update_config', 0);
if ($update_config == 1 && $config['history_db_enabled'] == 1) {
    if (! isset($config['history_db_connection'])
        || $config['history_db_connection'] === false
    ) {
        $config['history_db_connection'] = db_connect(
            $config['history_db_host'],
            $config['history_db_name'],
            $config['history_db_user'],
            io_output_password($config['history_db_pass']),
            $config['history_db_port'],
            false
        );
    }

    if ($config['history_db_connection'] !== false) {
        $historical_days_purge = get_parameter('historical_days_purge', 0);
        $historical_days_compact = get_parameter('historical_days_compact', 0);
        $historical_step_compact = get_parameter('historical_step_compact', 0);
        $historical_event_purge = get_parameter('historical_event_purge', 0);
        $historical_string_purge = get_parameter('historical_string_purge', 0);

        $history_connect = @mysql_db_process_sql(
            'SELECT 1 FROM tconfig',
            'affected_rows',
            $config['history_db_connection'],
            false
        );

        $config_history = false;
        if ($history_connect !== false) {
            $config_history = mysql_db_process_sql(
                'SELECT * FROM tconfig',
                'affected_rows',
                $config['history_db_connection'],
                false
            );

            if (!$config_history) {
                $sql = "INSERT INTO tconfig (token, `value`) VALUES
                        ('days_purge', ".$historical_days_purge."),
                        ('days_compact', ".$historical_days_compact."),
                        ('step_compact', ".$historical_step_compact."),
                        ('event_purge', ".$historical_event_purge."),
                        ('string_purge', ".$historical_string_purge."),
                        ('history_db_enabled', 0)";

                mysql_db_process_sql(
                    $sql,
                    'insert_id',
                    $config['history_db_connection'],
                    false
                );
            } else {
                $sql = 'UPDATE tconfig SET `value` = '.$historical_days_purge." WHERE token = 'days_purge'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
                $sql = 'UPDATE tconfig SET `value` = '.$historical_days_compact." WHERE token = 'days_compact'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
                $sql = 'UPDATE tconfig SET `value` = '.$historical_step_compact." WHERE token = 'step_compact'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
                $sql = 'UPDATE tconfig SET `value` = '.$historical_event_purge." WHERE token = 'event_purge'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
                $sql = 'UPDATE tconfig SET `value` = '.$historical_string_purge." WHERE token = 'string_purge'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
                $sql = "UPDATE tconfig SET `value` = 0 WHERE token = 'history_db_enabled'";
                mysql_db_process_sql(
                    $sql,
                    'update_id',
                    $config['history_db_connection'],
                    false
                );
            }
        }
    }
}

$table_status = new StdClass();
$table_status->width = '100%';
$table_status->class = 'databox filters';
$table_status->style[0] = 'font-weight: bold';
$table_status->size[0] = '10%';

$table_status->data = [];

$sql = "SELECT UNIX_TIMESTAMP(NOW()) - `value` AS updated_at
        FROM tconfig
        WHERE token = 'db_maintance'";

$time_pandora_db_active = db_get_sql($sql);


if ($time_pandora_db_active < SECONDS_12HOURS) {
    $table_status->data[0][0] = html_print_image(
        'images/dot_green.png',
        true
    );
} else {
    $table_status->data[0][0] = html_print_image(
        'images/dot_red.png',
        true
    );
}

$table_status->data[0][0] .= ' '.__('Pandora_db running in active database.');
$table_status->data[0][0] .= ' '.__('Executed:').' ';
$table_status->data[0][0] .= human_time_description_raw(
    $time_pandora_db_active,
    true
);

$table_status->data[0][0] .= ' '.__('ago').'.';

if ($config['history_db_enabled'] == 1) {
    if (! isset($config['history_db_connection'])
        || $config['history_db_connection'] === false
    ) {
        $config['history_db_connection'] = db_connect(
            $config['history_db_host'],
            $config['history_db_name'],
            $config['history_db_user'],
            io_output_password($config['history_db_pass']),
            $config['history_db_port'],
            false
        );
    }

    $history_connect = @mysql_db_process_sql(
        'SELECT 1 FROM tconfig',
        'affected_rows',
        $config['history_db_connection'],
        false
    );

    $time_pandora_db_history = false;
    if ($history_connect) {
        if ($config['history_db_connection']) {
            $time_pandora_db_history = mysql_db_process_sql(
                $sql,
                'insert_id',
                $config['history_db_connection'],
                false
            );
        }
    }

    if ($time_pandora_db_history !== false
        && $time_pandora_db_history[0]['updated_at'] < SECONDS_12HOURS
    ) {
        $table_status->data[1][0] = html_print_image(
            'images/dot_green.png',
            true
        );
    } else {
        $table_status->data[1][0] = html_print_image(
            'images/dot_red.png',
            true
        );
    }

    $table_status->data[1][0] .= ' '.__('Pandora_db running in historical database.');
    $table_status->data[1][0] .= ' '.__('Executed:').' ';
    if ($time_pandora_db_history !== false) {
        $table_status->data[1][0] .= human_time_description_raw(
            $time_pandora_db_history[0]['updated_at'],
            true
        ).' '.__('ago').'.';
    } else {
        $table_status->data[1][0] .= __('not executed');
    }
}


$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->data = [];
$table->style[0] = 'font-weight: bold';

$table->size[0] = '70%';
$table->size[1] = '30%';

// enterprise_hook('enterprise_warnings_history_days');
$table->data[1][0] = __('Max. days before delete events');
$table->data[1][0] .= ui_print_help_tip(
    __('If the compaction or purge of the data is more frequent than the events deletion, anomalies in module graphs could appear'),
    true
);

$table->data[1][1] = html_print_input_text(
    'event_purge',
    $config['event_purge'],
    '',
    5,
    5,
    true
);

$table->data[2][0] = __('Max. days before delete traps');
$table->data[2][1] = html_print_input_text(
    'trap_purge',
    $config['trap_purge'],
    '',
    5,
    5,
    true
);

$table->data[3][0] = __('Max. days before delete audit events');
$table->data[3][1] = html_print_input_text(
    'audit_purge',
    $config['audit_purge'],
    '',
    5,
    5,
    true
);

$table->data[4][0] = __('Max. days before delete string data');
$table->data[4][1] = html_print_input_text(
    'string_purge',
    $config['string_purge'],
    '',
    5,
    5,
    true
);

$table->data[5][0] = __('Max. days before delete GIS data');
$table->data[5][1] = html_print_input_text(
    'gis_purge',
    $config['gis_purge'],
    '',
    5,
    5,
    true
);

$table->data[6][0] = __('Max. days before purge');
$table->data[6][0] .= ui_print_help_tip(
    __('Configure a purge period more frequent than a compact data period has no sense'),
    true
);
$table->data[6][1] = html_print_input_text(
    'days_purge',
    $config['days_purge'],
    '',
    5,
    5,
    true
);

$table->data[7][0] = __('Max. days before compact data');
$table->data[7][1] = html_print_input_text(
    'days_compact',
    $config['days_compact'],
    '',
    5,
    5,
    true
);

$table->data[8][0] = __('Max. days before delete unknown modules');
$table->data[8][1] = html_print_input_text(
    'days_delete_unknown',
    $config['days_delete_unknown'],
    '',
    5,
    5,
    true
);

$table->data[9][0] = __('Max. days before delete autodisabled agents');
$table->data[9][1] = html_print_input_text(
    'days_autodisable_deletion',
    $config['days_autodisable_deletion'],
    '',
    5,
    5,
    true
);

$table->data[10][0] = __('Retention period of past special days');
$table->data[10][0] .= ui_print_help_tip(
    __('This number is days to keep past special days. 0 means never remove.'),
    true
);
$table->data[10][1] = html_print_input_text(
    'num_past_special_days',
    $config['num_past_special_days'],
    '',
    5,
    5,
    true
);

$table->data[11][0] = __('Max. macro data fields');
$table->data[11][0] .= ui_print_help_tip(
    __('Number of macro fields in alerts and templates between 1 and 15'),
    true
);
$table->data[11][1] = html_print_input_text(
    'max_macro_fields',
    $config['max_macro_fields'],
    '',
    5,
    5,
    true,
    false,
    false,
    'onChange="change_macro_fields()"'
);

if (enterprise_installed()) {
    $table->data[12][0] = __('Max. days before delete inventory data');
    $table->data[12][1] = html_print_input_text(
        'inventory_purge',
        $config['inventory_purge'],
        '',
        5,
        5,
        true
    );
}

if ($config['history_db_enabled'] == 1) {
    if (! isset($config['history_db_connection'])
        || $config['history_db_connection'] === false
    ) {
        $config['history_db_connection'] = db_connect(
            $config['history_db_host'],
            $config['history_db_name'],
            $config['history_db_user'],
            io_output_password($config['history_db_pass']),
            $config['history_db_port'],
            false
        );
    }

    $config_history = false;
    if ($config['history_db_connection']) {
        $history_connect = @mysql_db_process_sql(
            'SELECT 1 FROM tconfig',
            'affected_rows',
            $config['history_db_connection'],
            false
        );

        if ($history_connect !== false) {
            $config_history_array = mysql_db_process_sql(
                'SELECT * FROM tconfig',
                'affected_rows',
                $config['history_db_connection'],
                false
            );

            if (isset($config_history_array) && is_array($config_history_array)) {
                foreach ($config_history_array as $key => $value) {
                    $config_history[$value['token']] = $value['value'];
                }
            }
        } else {
            echo ui_print_error_message(
                __('The tconfig table does not exist in the historical database')
            );
        }
    }

    if ($config_history === false) {
        $config_history = [];
        $config_history['days_purge'] = 180;
        $config_history['days_compact'] = 120;
        $config_history['step_compact'] = 1;
        $config_history['event_purge'] = 180;
        $config_history['string_purge'] = 180;
    }

    $table_historical = new StdClass();
    $table_historical->width = '100%';
    $table_historical->class = 'databox filters';
    $table_historical->data = [];
    $table_historical->style[0] = 'font-weight: bold';

    $table_historical->size[0] = '70%';
    $table_historical->size[1] = '30%';

    enterprise_hook('enterprise_warnings_history_days');

    $table_historical->data[0][0] = __('Max. days before purge');
    $table_historical->data[0][0] .= ui_print_help_tip(
        __('Configure a purge period more frequent than a compact data period has no sense'),
        true
    );
    $table_historical->data[0][1] = html_print_input_text(
        'historical_days_purge',
        $config_history['days_purge'],
        '',
        5,
        5,
        true
    );

    $table_historical->data[1][0] = __('Max. days before compact data');
    $table_historical->data[1][1] = html_print_input_text(
        'historical_days_compact',
        $config_history['days_compact'],
        '',
        5,
        5,
        true
    );

    $table_historical->data[2][0] = __('Compact interpolation in hours (1 Fine-20 bad)');
    $table_historical->data[2][0] .= ui_print_help_tip(
        __('Data will be compacted in intervals of the specified length.'),
        true
    );
    $table_historical->data[2][1] = html_print_input_text(
        'historical_step_compact',
        $config_history['step_compact'],
        '',
        5,
        5,
        true
    );

    $table_historical->data[3][0] = __('Max. days before delete events');
    $table_historical->data[3][0] .= ui_print_help_tip(
        __('If the compaction or purge of the data is more frequent than the events deletion, anomalies in module graphs could appear'),
        true
    );

    $table_historical->data[3][1] = html_print_input_text(
        'historical_event_purge',
        $config_history['event_purge'],
        '',
        5,
        5,
        true
    );

    $table_historical->data[4][0] = __('Max. days before delete string data');
    $table_historical->data[4][1] = html_print_input_text(
        'historical_string_purge',
        $config_history['string_purge'],
        '',
        5,
        5,
        true
    );

    $table_historical->data[4][1] .= html_print_input_hidden(
        'historical_history_db_enabled',
        0,
        true
    );
}

$table->data[] = [
    __('Max. days before delete old messages'),
    html_print_input_text(
        'delete_old_messages',
        $config['delete_old_messages'],
        '',
        5,
        5,
        true
    ),
];


$table->data[] = [
    __('Max. days before delete old network matrix data'),
    html_print_input_text(
        'delete_old_network_matrix',
        $config['delete_old_network_matrix'],
        '',
        5,
        5,
        true
    ),
];

$table_other = new stdClass();
$table_other->width = '100%';
$table_other->class = 'databox filters';
$table_other->data = [];
$table_other->style[0] = 'font-weight: bold';

$table_other->size[0] = '70%';
$table_other->size[1] = '30%';

$table_other->data[1][0] = __('Item limit for realtime reports');
$table_other->data[1][0] .= ui_print_help_tip(
    __('Set a value too high cause a slowdown on console and a performance penalty in the system.'),
    true
);
$table_other->data[1][1] = html_print_input_text(
    'report_limit',
    $config['report_limit'],
    '',
    5,
    5,
    true
);

$table_other->data[2][0] = __('Compact interpolation in hours (1 Fine-20 bad)');
$table_other->data[2][0] .= ui_print_help_tip(
    __('Data will be compacted in intervals of the specified length.'),
    true
);
$table_other->data[2][1] = html_print_input_text(
    'step_compact',
    $config['step_compact'],
    '',
    5,
    5,
    true
);

$intervals = [];
$intervals[SECONDS_1HOUR] = __('1 hour');
$intervals[SECONDS_12HOURS] = __('12 hours');
$intervals[SECONDS_1DAY] = __('Last day');
$intervals[SECONDS_2DAY] = __('2 days');
$intervals[SECONDS_10DAY] = __('10 days');
$intervals[SECONDS_1WEEK] = __('Last week');
$intervals[SECONDS_2WEEK] = __('2 weeks');
$intervals[SECONDS_1MONTH] = __('Last month');

$table_other->data[3][0] = __('Default hours for event view');
$table_other->data[3][1] = html_print_input_text(
    'event_view_hr',
    $config['event_view_hr'],
    '',
    5,
    5,
    true
);

$table_other->data[5][0] = __('Use realtime statistics');
$table_other->data[5][1] = html_print_checkbox_switch(
    'realtimestats',
    1,
    $config['realtimestats'],
    true
);

$table_other->data[6][0] = __('Batch statistics period (secs)');
$table_other->data[6][0] .= ui_print_help_tip(
    __('If realtime statistics are disabled, statistics interval resfresh will be set here.'),
    true
);
$table_other->data[6][1] = html_print_input_text(
    'stats_interval',
    $config['stats_interval'],
    '',
    5,
    5,
    true
);

$table_other->data[7][0] = __('Use agent access graph');
$table_other->data[7][1] = html_print_checkbox_switch('agentaccess', 1, $config['agentaccess'], true);

$table_other->data[8][0] = __('Max. recommended number of files in attachment directory');
$table_other->data[8][0] .= ui_print_help_tip(
    __('This number is the maximum number of files in attachment directory. If this number is reached then a warning message will appear in the header notification space.'),
    true
);
$table_other->data[8][1] = html_print_input_text(
    'num_files_attachment',
    $config['num_files_attachment'],
    '',
    5,
    5,
    true
);

$table_other->data[9][0] = __('Delete not init modules');
$table_other->data[9][1] = html_print_checkbox_switch('delete_notinit', 1, $config['delete_notinit'], true);

$table_other->data[10][0] = __('Big Operation Step to purge old data');
$table_other->data[10][0] .= ui_print_help_tip(
    __('The number of blocks that a time interval is split into. A bigger value means bigger blocks, which is faster but heavier on the database. Default is 100.'),
    true
);
$table_other->data[10][1] = html_print_input_text(
    'big_operation_step_datos_purge',
    $config['big_operation_step_datos_purge'],
    '',
    5,
    5,
    true
);

$table_other->data[11][0] = __('Small Operation Step to purge old data');
$table_other->data[11][0] .= ui_print_help_tip(
    __('The number of rows that are processed in a single query in deletion. Default is 1000. Increase to 3000-5000 in fast systems. Decrease to 500 or 250 on systems with locks.'),
    true
);
$table_other->data[11][1] = html_print_input_text(
    'small_operation_step_datos_purge',
    $config['small_operation_step_datos_purge'],
    '',
    5,
    5,
    true
);

$table_other->data[12][0] = __('Graph container - Max. Items');
$table_other->data[12][0] .= ui_print_help_tip(
    __('The number of graphs that are viewed in a container. Default is 10 .Increasing this number could lead to performance problems'),
    true
);
$table_other->data[12][1] = html_print_input_text(
    'max_graph_container',
    $config['max_graph_container'],
    '',
    5,
    5,
    true
);

$table_other->data[13][0] = __('Events response max. execution');
$table_other->data[13][0] .= ui_print_help_tip(
    __('Number of events that will perform the desired action at the same time'),
    true
);
$table_other->data[13][1] = html_print_input_text(
    'max_execution_event_response',
    $config['max_execution_event_response'],
    '',
    5,
    5,
    true
);

echo '<form id="form_setup" method="post">';

echo '<fieldset>';
    echo '<legend>'.__('Database maintenance status').' '.ui_print_help_icon('database_maintenance_status_tab', true).'</legend>';
    html_print_table($table_status);
echo '</fieldset>';

echo '<fieldset>';
    echo '<legend>'.__('Database maintenance options').' '.ui_print_help_icon('database_maintenance_options_tab', true).'</legend>';
    html_print_table($table);
echo '</fieldset>';

if ($config['history_db_enabled'] == 1) {
    echo '<fieldset>';
    echo '<legend>'.__('Historical database maintenance options').' '.ui_print_help_icon('historical_database_maintenance_options_tab', true).'</legend>';
        html_print_table($table_historical);
    echo '</fieldset>';
}

echo '<fieldset>';
    echo '<legend>'.__('Others').' '.ui_print_help_icon('others_database_maintenance_options_tab', true).'</legend>';
    html_print_table($table_other);
echo '</fieldset>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden('update_config', 1);
html_print_submit_button(
    __('Update'),
    'update_button',
    false,
    'class="sub upd"'
);
echo '</div>';
echo '</form>';
?>

<script language="javascript" type="text/javascript">

function change_macro_fields() {
    var value = $("#text-max_macro_fields").val();
    if (value <= 0) {
        $("#text-max_macro_fields").val(1);
    }
    else if (value > 15) {
        $("#text-max_macro_fields").val(15);
    }
}

</script>
