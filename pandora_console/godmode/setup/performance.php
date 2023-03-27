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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
require_once $config['homedir'].'/include/config.php';
require_once $config['homedir'].'/vendor/autoload.php';

use PandoraFMS\Core\Config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')
    && ! is_user_admin($config['id_user'])
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

// Load needed resources.
ui_require_css_file('setup.multicolumn');

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
            'DESCRIBE tconfig',
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

$performance_variables_control = (array) json_decode(io_safe_output($config['performance_variables_control']));

$total_agents = db_get_value('count(*)', 'tagente');
$disable_agentaccess = ($total_agents >= 200 && $config['agentaccess'] == 0) ? true : false;

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
$table->class = 'filter-table-adv';
$table->data = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data[0][0] = html_print_label_input_block(
    __('Max. days before delete events'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['event_purge']->max,
            'name'   => 'event_purge',
            'value'  => $config['event_purge'],
            'return' => true,
            'min'    => $performance_variables_control['event_purge']->min,
        ]
    )
);

$table->data[0][1] = html_print_label_input_block(
    __('Max. days before delete traps'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['trap_purge']->max,
            'name'   => 'trap_purge',
            'value'  => $config['trap_purge'],
            'return' => true,
            'min'    => $performance_variables_control['trap_purge']->min,
        ]
    )
);

$table->data[1][0] = html_print_label_input_block(
    __('Max. days before delete audit events'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['audit_purge']->max,
            'name'   => 'audit_purge',
            'value'  => $config['audit_purge'],
            'return' => true,
            'min'    => $performance_variables_control['audit_purge']->min,
        ]
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Max. days before delete string data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['string_purge']->max,
            'name'   => 'string_purge',
            'value'  => $config['string_purge'],
            'return' => true,
            'min'    => $performance_variables_control['string_purge']->min,
        ]
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Max. days before delete GIS data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['gis_purge']->max,
            'name'   => 'gis_purge',
            'value'  => $config['gis_purge'],
            'return' => true,
            'min'    => $performance_variables_control['gis_purge']->min,
        ]
    )
);

$table->data[2][1] = html_print_label_input_block(
    __('Max. days before purge'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['days_purge']->max,
            'name'   => 'days_purge',
            'value'  => $config['days_purge'],
            'return' => true,
            'min'    => $performance_variables_control['days_purge']->min,
        ]
    )
);

$table->data[3][0] = html_print_label_input_block(
    __('Max. days before compact data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['days_compact']->max,
            'name'   => 'days_compact',
            'value'  => $config['days_compact'],
            'return' => true,
            'min'    => $performance_variables_control['days_compact']->min,
        ]
    )
);

$table->data[3][1] = html_print_label_input_block(
    __('Max. days before delete unknown modules'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['days_delete_unknown']->max,
            'name'   => 'days_delete_unknown',
            'value'  => $config['days_delete_unknown'],
            'return' => true,
            'min'    => $performance_variables_control['days_delete_unknown']->min,
        ]
    )
);

$table->data[4][0] = html_print_label_input_block(
    __('Max. days before delete not initialized modules'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['days_delete_not_initialized']->max,
            'name'   => 'days_delete_not_initialized',
            'value'  => $config['days_delete_not_initialized'],
            'return' => true,
            'min'    => $performance_variables_control['days_delete_not_initialized']->min,
        ]
    )
);

$table->data[4][1] = html_print_label_input_block(
    __('Max. days before delete autodisabled agents'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['days_autodisable_deletion']->max,
            'name'   => 'days_autodisable_deletion',
            'value'  => $config['days_autodisable_deletion'],
            'return' => true,
            'min'    => $performance_variables_control['days_autodisable_deletion']->min,
        ]
    )
);

$table->data[5][0] = html_print_label_input_block(
    __('Retention period of past special days'),
    html_print_input_text(
        'num_past_special_days',
        $config['num_past_special_days'],
        '',
        false,
        5,
        true
    )
);

$table->data[5][1] = html_print_label_input_block(
    __('Max. macro data fields'),
    html_print_input_text(
        'max_macro_fields',
        $config['max_macro_fields'],
        '',
        false,
        5,
        true,
        false,
        false,
        'onChange="change_macro_fields()"'
    )
);

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

    $config_history['days_purge'] = Config::get('days_purge', 180, true);
    $config_history['days_compact'] = Config::get('days_compact', 120, true);
    $config_history['step_compact'] = Config::get('step_compact', 1, true);
    $config_history['event_purge'] = Config::get('event_purge', 180, true);
    $config_history['string_purge'] = Config::get('string_purge', 180, true);

    $table_historical = new StdClass();
    $table_historical->width = '100%';
    $table_historical->class = 'filter-table-adv';
    $table_historical->data = [];

    $table_historical->size[0] = '50%';
    $table_historical->size[1] = '50%';

    enterprise_hook('enterprise_warnings_history_days');

    $table_historical->data[0][0] = html_print_label_input_block(
        __('Max. days before purge'),
        html_print_input_text(
            'historical_days_purge',
            $config_history['days_purge'],
            '',
            false,
            5,
            true
        )
    );

    $table_historical->data[0][1] = html_print_label_input_block(
        __('Max. days before compact data'),
        html_print_input_text(
            'historical_days_compact',
            $config_history['days_compact'],
            '',
            false,
            5,
            true
        )
    );

    $table_historical->data[1][0] = html_print_label_input_block(
        __('Compact interpolation in hours (1 Fine-20 bad)'),
        html_print_input_text(
            'historical_step_compact',
            $config_history['step_compact'],
            '',
            false,
            5,
            true
        )
    );

    $table_historical->data[1][1] = html_print_label_input_block(
        __('Max. days before delete events'),
        html_print_input_text(
            'historical_event_purge',
            $config_history['event_purge'],
            '',
            false,
            5,
            true
        )
    );

    $table_historical->data[2][0] = html_print_label_input_block(
        __('Max. days before delete string data'),
        html_print_input_text(
            'historical_string_purge',
            $config_history['string_purge'],
            '',
            5,
            5,
            true
        )
    );

    $table_historical->data[2][0] .= html_print_input_hidden(
        'historical_history_db_enabled',
        0,
        true
    );
}

$table->data[6][0] = html_print_label_input_block(
    __('Max. days before delete old messages'),
    html_print_input_text(
        'delete_old_messages',
        $config['delete_old_messages'],
        '',
        false,
        5,
        true
    )
);

$table->data[6][1] = html_print_label_input_block(
    __('Max. days before delete old network matrix data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['delete_old_network_matrix']->max,
            'name'   => 'delete_old_network_matrix',
            'value'  => $config['delete_old_network_matrix'],
            'return' => true,
            'min'    => $performance_variables_control['delete_old_network_matrix']->min,
        ]
    )
);

if (enterprise_installed()) {
    $table->data[7][0] = html_print_label_input_block(
        __('Max. days before delete inventory data'),
        html_print_input_text(
            'inventory_purge',
            $config['inventory_purge'],
            '',
            false,
            5,
            true
        )
    );
}

$table_other = new stdClass();
$table_other->width = '100%';
$table_other->class = 'filter-table-adv';
$table_other->data = [];

$table_other->size[0] = '50%';
$table_other->size[1] = '50%';

$table_other->data[0][0] = html_print_label_input_block(
    __('Item limit for realtime reports'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['report_limit']->max,
            'name'   => 'report_limit',
            'value'  => $config['report_limit'],
            'return' => true,
            'min'    => $performance_variables_control['report_limit']->min,
        ]
    )
);

$table_other->data[0][1] = html_print_label_input_block(
    __('Limit of events per query'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => 10000,
            'name'   => 'events_per_query',
            'value'  => $config['events_per_query'],
            'return' => true,
        ]
    )
);

$table_other->data[1][0] = html_print_label_input_block(
    __('Compact interpolation in hours (1 Fine-20 bad)'),
    html_print_input_text(
        'step_compact',
        $config['step_compact'],
        '',
        false,
        5,
        true
    )
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

$table_other->data[1][1] = html_print_label_input_block(
    __('Default hours for event view'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['event_view_hr']->max,
            'name'   => 'event_view_hr',
            'value'  => $config['event_view_hr'],
            'return' => true,
            'min'    => $performance_variables_control['event_view_hr']->min,
        ]
    )
);

$table_other->data[2][0] = html_print_label_input_block(
    __('Use realtime statistics'),
    html_print_checkbox_switch(
        'realtimestats',
        1,
        $config['realtimestats'],
        true
    )
);

$table_other->data[2][1] = html_print_label_input_block(
    __('Batch statistics period (secs)'),
    html_print_input_text(
        'stats_interval',
        $config['stats_interval'],
        '',
        false,
        5,
        true
    )
);

$table_other->data[3][0] = html_print_label_input_block(
    __('Use agent access graph'),
    html_print_checkbox_switch(
        'agentaccess',
        1,
        $config['agentaccess'],
        true,
        $disable_agentaccess
    )
);

$table_other->data[3][1] = html_print_label_input_block(
    __('Max. recommended number of files in attachment directory'),
    html_print_input_text(
        'num_files_attachment',
        $config['num_files_attachment'],
        '',
        false,
        5,
        true
    )
);

$table_other->data[4][0] = html_print_label_input_block(
    __('Delete not init modules'),
    html_print_checkbox_switch(
        'delete_notinit',
        1,
        $config['delete_notinit'],
        true
    )
);

$table_other->data[4][1] = html_print_label_input_block(
    __('Big Operation Step to purge old data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['big_operation_step_datos_purge']->max,
            'name'   => 'big_operation_step_datos_purge',
            'value'  => $config['big_operation_step_datos_purge'],
            'return' => true,
            'min'    => $performance_variables_control['big_operation_step_datos_purge']->min,
        ]
    )
);

$table_other->data[5][0] = html_print_label_input_block(
    __('Small Operation Step to purge old data'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['small_operation_step_datos_purge']->max,
            'name'   => 'small_operation_step_datos_purge',
            'value'  => $config['small_operation_step_datos_purge'],
            'return' => true,
            'min'    => $performance_variables_control['small_operation_step_datos_purge']->min,
        ]
    )
);

$table_other->data[5][1] = html_print_label_input_block(
    __('Graph container - Max. Items'),
    html_print_input_text(
        'max_graph_container',
        $config['max_graph_container'],
        '',
        false,
        5,
        true
    )
);

$table_other->data[6][0] = html_print_label_input_block(
    __('Events response max. execution'),
    html_print_input_text(
        'max_execution_event_response',
        $config['max_execution_event_response'],
        '',
        false,
        5,
        true
    )
);

$table_other->data[6][1] = html_print_label_input_block(
    __('Row limit in csv log'),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['row_limit_csv']->max,
            'name'   => 'row_limit_csv',
            'value'  => $config['row_limit_csv'],
            'return' => true,
            'min'    => $performance_variables_control['row_limit_csv']->min,
        ]
    )
);

$table_other->data[7][0] = html_print_label_input_block(
    __('SNMP walk binary'),
    html_print_input_text(
        'snmpwalk',
        $config['snmpwalk'],
        '',
        false,
        10,
        true
    )
);

$tip = ui_print_help_tip(
    __('SNMP bulk walk is not able to request V1 SNMP, this option will be used instead (by default snmpwalk, slower).'),
    true
);

$table_other->data[7][1] = html_print_label_input_block(
    __('SNMP walk binary (fallback)').$tip,
    html_print_input_text(
        'snmpwalk_fallback',
        $config['snmpwalk_fallback'],
        '',
        false,
        10,
        true
    )
);

$tip = ui_print_help_tip(
    __(
        '%s web2image cache system cleanup. It is always cleaned up after perform an upgrade',
        get_product_name()
    ),
    true
);

$table_other->data[8][0] = html_print_label_input_block(
    __('WMI binary'),
    html_print_input_text(
        'wmiBinary',
        $config['wmiBinary'],
        '',
        false,
        50,
        true
    )
);

// Agent Wizard defaults.
$defaultAgentWizardOptions = json_decode(io_safe_output($config['agent_wizard_defaults']));
$tableSnmpWizard = new stdClass();
$tableSnmpWizard->width = '100%';
$tableSnmpWizard->class = 'filter-table-adv';
$tableSnmpWizard->data = [];
$tableSnmpWizard->size[0] = '50%';
$tableSnmpWizard->size[1] = '50%';

$i = 0;
$j = 0;
foreach ($defaultAgentWizardOptions as $key => $value) {
    if ($i > 1) {
        $i = 0;
        $j++;
    }

    $tableSnmpWizard->data[$j][$i] = html_print_label_input_block(
        $key,
        html_print_checkbox_switch('agent_wizard_defaults_'.$key, 1, $value, true)
    );
    $i++;
}

echo '<form id="form_setup" method="post" class="max_floating_element_size">';

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

echo '<fieldset>';
    echo '<legend>'.__('Agent SNMP Interface Wizard defaults').' '.ui_print_help_icon('agent_snmp_wizard_options_tab', true).'</legend>';
    html_print_table($tableSnmpWizard);
echo '</fieldset>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden('update_config', 1);
$actionButtons = html_print_submit_button(
    __('Update'),
    'update_button',
    false,
    [ 'icon' => 'update' ],
    true
);
html_print_action_buttons($actionButtons, ['type' => 'form_action']);
echo '</form>';
?>

<script language="javascript" type="text/javascript">

function change_macro_fields() {
    var value = $("#text-max_macro_fields").val();
    if (value <= 0) {
        $("#text-max_macro_fields").val(1);
    }
    else if (value > 20) {
        $("#text-max_macro_fields").val(20);
    }
}

</script>
