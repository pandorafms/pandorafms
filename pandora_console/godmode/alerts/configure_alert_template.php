<?php
/**
 * Alerts templates.
 *
 * @category   Alerts
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

use PandoraFMS\Calendar;

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

$duplicate_template = (bool) get_parameter('duplicate_template');
$id = (int) get_parameter('id');
$pure = get_parameter('pure', 0);
$step = (int) get_parameter('step', 1);

// We set here the number of steps.
if (defined('LAST_STEP') === false) {
    define('LAST_STEP', 3);
}

// Default events calendar.
$default_events_calendar = default_events_calendar($id, 'talert_templates');

if ($duplicate_template === true) {
    $source_id = (int) get_parameter('source_id');
    $a_template = alerts_get_alert_template($source_id);
} else {
    $a_template = alerts_get_alert_template($id);
}

if (is_metaconsole() === true) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

$can_edit_all = false;
if (check_acl_restricted_all($config['id_user'], 0, 'LM')) {
    $can_edit_all = true;
}

if ($a_template !== false) {
    // If user tries to duplicate/edit a template with group=ALL.
    if ($a_template['id_group'] == 0) {
        if (is_metaconsole() === true) {
            alerts_meta_print_header();
        } else {
            if ($step == 1) {
                $help_header = '';
            } else if ($step == 2) {
                $help_header = 'configure_alert_template_step_2';
            } else if ($step == 3) {
                $help_header = '';
            }

            ui_print_standard_header(
                __('Alerts'),
                'images/gm_alerts.png',
                false,
                $help_header,
                true,
                [],
                [
                    [
                        'link'  => '',
                        'label' => __('Configure alert template'),
                    ],
                ]
            );
        }
    } else {
        // If user tries to duplicate/edit a template of others groups.
        $own_info = get_user_info($config['id_user']);
        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
            $own_groups = array_keys(users_get_groups($config['id_user'], 'LM'));
        } else {
            $own_groups = array_keys(users_get_groups($config['id_user'], 'LM', false));
        }

        $is_in_group = in_array($a_template['id_group'], $own_groups);
        // Then template group have to be in his own groups.
        if ($is_in_group) {
            // Header.
            if (is_metaconsole() === true) {
                alerts_meta_print_header();
            } else {
                ui_print_standard_header(
                    __('Alerts'),
                    'images/gm_alerts.png',
                    false,
                    'conf_alert_template',
                    true,
                    [],
                    [
                        [
                            'link'  => '',
                            'label' => __('Configure alert template'),
                        ],
                    ]
                );
            }
        } else {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Alert Management'
            );
            include 'general/noaccess.php';
            exit;
        }
    }

    // This prevents to duplicate the header in case duplicate/edit_template action is performed.
} else {
    // Header.
    if (is_metaconsole() === true) {
        alerts_meta_print_header();
    } else {
        if ($step == 1) {
            $help_header = '';
        } else if ($step == 2) {
            $help_header = 'configure_alert_template_step_2';
        } else if ($step == 3) {
            $help_header = '';
        }

        ui_print_standard_header(
            __('Alerts'),
            'images/gm_alerts.png',
            false,
            $help_header,
            true,
            [],
            [
                [
                    'link'  => '',
                    'label' => __('Configure alert template'),
                ],
            ]
        );
    }
}


if ($duplicate_template) {
    $source_id = (int) get_parameter('source_id');

    // If user doesn't have the permission to access
    // All group and source template is All group,
    // then group is changed to the first group of user.
    if ($can_edit_all == false && $a_template['id_group'] == 0) {
        $a_template['id_group'] = users_get_first_group(false, 'LM', false);
    }

    $id = alerts_duplicate_alert_template($source_id, $a_template['id_group']);

    if ($id) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Duplicate alert template '.$source_id.' clone to '.$id
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail try to duplicate alert template '.$source_id
        );
    }

    ui_print_result_message(
        $id,
        __('Successfully created from %s', alerts_get_alert_template_name($source_id)),
        __('Could not be created')
    );
}


/**
 * Build navbar steps.
 *
 * @param integer $step Step.
 * @param integer $id   Id template.
 *
 * @return void Html output.
 */
function print_alert_template_steps($step, $id)
{
    echo '<ol class="steps">';

    if (is_metaconsole() === true) {
        $sec = 'advanced';
    } else {
        $sec = 'galertas';
    }

    $pure = get_parameter('pure', 0);

    // Step 1.
    if ($step == 1) {
        echo '<li class="first current">';
    } else if ($step > 1) {
        echo '<li class="visited">';
    } else {
        echo '<li class="first">';
    }

    if ($id) {
        echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&pure='.$pure.'">';
        echo __('Step').' 1 &raquo; ';
        echo '<span>'.__('General').'</span>';
        echo '</a>';
    } else {
        echo __('Step').' 1 &raquo; ';
        echo '<span>'.__('General').'</span>';
    }

    echo '</li>';

    // Step 2.
    if ($step == 2) {
        echo '<li class="current">';
    } else if ($step > 2) {
        echo '<li class="visited">';
    } else {
        echo '<li>';
    }

    if ($id) {
        echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=2&pure='.$pure.'">';
        echo __('Step').' 2 &raquo; ';
        echo '<span>'.__('Conditions').'</span>';
        echo '</a>';
    } else {
        echo __('Step').' 2 &raquo; ';
        echo '<span>'.__('Conditions').'</span>';
    }

    echo '</li>';

    // Step 3.
    if ($step == 3) {
        echo '<li class="last current">';
    } else if ($step > 3) {
        echo '<li class="last visited">';
    } else {
        echo '<li class="last">';
    }

    if ($id) {
        echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=3&pure='.$pure.'">';
        echo __('Step').' 3 &raquo; ';
        echo '<span>'.__('Advanced fields').'</span>';
        echo '</a>';
    } else {
        echo __('Step').' 3 &raquo; ';
        echo '<span>'.__('Advanced fields').'</span>';
    }

    echo '</ol>';
    echo '<div id="steps_clean"> </div>';
}


/**
 * Update template
 *
 * @param integer $step Step.
 *
 * @return boolean result to update.
 */
function update_template($step)
{
    global $config;

    $id = (int) get_parameter('id');

    if (empty($id) === true) {
        return false;
    }

    if (is_metaconsole() === true) {
        $sec = 'advanced';
    } else {
        $sec = 'galertas';
    }

    if ($step == 1) {
        $name = (string) get_parameter('name');
        $description = (string) get_parameter('description');
        $wizard_level = (string) get_parameter('wizard_level');
        $priority = (int) get_parameter('priority');
        $id_group = get_parameter('id_group');
        // Only for Metaconsole. Save the previous name for synchronizing.
        if (is_metaconsole() === true) {
            $previous_name = db_get_value('name', 'talert_templates', 'id', $id);
        } else {
            $previous_name = '';
        }

        $values = [
            'name'          => $name,
            'description'   => $description,
            'id_group'      => $id_group,
            'priority'      => $priority,
            'wizard_level'  => $wizard_level,
            'previous_name' => $previous_name,
        ];

        $result = alerts_update_alert_template($id, $values);
    } else if ($step == 2) {
        $schedule = io_safe_output(get_parameter('schedule', []));
        json_decode($schedule, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $special_day = (int) get_parameter('special_day');
        $threshold = (int) get_parameter('threshold');
        $max_alerts = (int) get_parameter('max_alerts');
        $min_alerts = (int) get_parameter('min_alerts');
        $min_alerts_reset_counter = (int) get_parameter('min_alerts_reset_counter');
        $disable_event = (int) get_parameter('disable_event');
        $type = (string) get_parameter('type');
        $value = (string) html_entity_decode(get_parameter('value'));
        $max = (float) get_parameter('max');
        $min = (float) get_parameter('min');
        $matches = (bool) get_parameter('matches_value');

        $default_action = (int) get_parameter('default_action');
        if (empty($default_action) === true) {
            $default_action = null;
        }

        $values = [
            'schedule'                 => $schedule,
            'special_day'              => $special_day,
            'time_threshold'           => $threshold,
            'id_alert_action'          => $default_action,
            'max_alerts'               => $max_alerts,
            'min_alerts'               => $min_alerts,
            'min_alerts_reset_counter' => $min_alerts_reset_counter,
            'type'                     => $type,
            'value'                    => $value,
            'max_value'                => $max,
            'min_value'                => $min,
            'matches_value'            => $matches,
            'disable_event'            => $disable_event,
        ];

        $result = alerts_update_alert_template($id, $values);
    } else if ($step == 3) {
        $recovery_notify = (bool) get_parameter('recovery_notify');
        for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
            $values['field'.$i] = (string) get_parameter('field'.$i);
            $values['field'.$i.'_recovery'] = ($recovery_notify) ? (string) get_parameter('field'.$i.'_recovery') : '';
        }

        $values['recovery_notify'] = $recovery_notify;

        $result = alerts_update_alert_template($id, $values);
    } else {
        return false;
    }

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Update alert template #'.$id,
            false,
            false,
            json_encode($values)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail try to update alert template #'.$id,
            false,
            false,
            json_encode($values)
        );
    }

    return $result;
}


$is_management_allowed = is_management_allowed();

if ($is_management_allowed === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/alerts/configure_alert_template&pure=0&id='.$id.'&step='.$step
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alerts templates information is read only. Go to Go to %s to manage it.',
            $url
        )
    );
}

$step = (int) get_parameter('step', 1);

$create_alert = (bool) get_parameter('create_alert');
$create_template = (bool) get_parameter('create_template');
$update_template = (bool) get_parameter('update_template');

$disabled = false;
if (!$create_alert && !$create_template) {
    // When user edits a template with All group, user must have "LM" access privileges againt All group.
    if ($a_template['id_group'] == 0 && !$can_edit_all) {
        $disabled = true;
    }
}

$name = '';
$description = '';
$type = '';
$value = '';
$max = '';
$min = '';
$schedule = json_encode(
    $default_events_calendar
);
$special_day = 0;
$default_action = 0;
$fields = [];
for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
    $fields[$i] = '';
}

for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
    $fields_recovery[$i] = '';
}

$priority = 1;
$min_alerts = 0;
$min_alerts_reset_counter = 0;
$max_alerts = 1;
$threshold = SECONDS_1DAY;
$recovery_notify = false;
$field2_recovery = '';
$field3_recovery = '';
$matches = true;
$id_group = 0;
$wizard_level = 'nowizard';

if ($create_template) {
    $name = (string) get_parameter('name');
    $description = (string) get_parameter('description');
    $type = (string) get_parameter('type', 'critical');
    $value = (string) get_parameter('value');
    $max = (float) get_parameter('max');
    $min = (float) get_parameter('min');
    $matches = (bool) get_parameter('matches_value');
    $priority = (int) get_parameter('priority');
    $wizard_level = (string) get_parameter('wizard_level');
    $id_group = get_parameter('id_group');
    $name_check = db_get_value('name', 'talert_templates', 'name', $name);

    $values = [
        'description'   => $description,
        'value'         => $value,
        'max_value'     => $max,
        'min_value'     => $min,
        'id_group'      => $id_group,
        'matches_value' => $matches,
        'priority'      => $priority,
        'wizard_level'  => $wizard_level,
        'schedule'      => $schedule,
    ];

    if ($config['dbtype'] == 'oracle') {
        $values['field3'] = ' ';
        $values['field3_recovery'] = ' ';
    }

    if (!$name_check) {
        $result = alerts_create_alert_template($name, $type, $values);
    } else {
        $result = '';
    }

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Create alert template #'.$result,
            false,
            false,
            json_encode($values)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail try to create alert template',
            false,
            false,
            json_encode($values)
        );
    }

    // Show errors.
    if (!isset($messageAction)) {
        $messageAction = __('Could not be created');
    }

    if ($name == '') {
        $messageAction = __('No template name specified');
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully created'),
        $messageAction
    );


    // Go to previous step in case of error.
    if ($result === false) {
        $step--;
    } else {
        $id = $result;
    }
}

if ($update_template) {
    $result = update_template($step - 1);

    ui_print_result_message(
        $result,
        __('Successfully updated'),
        __('Could not be updated')
    );
    // Go to previous step in case of error.
    if ($result === false) {
        $step--;
    }
}

if ($id && ! $create_template) {
    $template = alerts_get_alert_template($id);
    $name = $template['name'];
    $description = $template['description'];
    $type = $template['type'];
    $value = $template['value'];
    $max = $template['max_value'];
    $min = $template['min_value'];
    $matches = $template['matches_value'];

    $schedule = json_encode(
        $default_events_calendar
    );
    $special_day = (int) $template['special_day'];
    $max_alerts = $template['max_alerts'];
    $min_alerts = $template['min_alerts'];
    $min_alerts_reset_counter = $template['min_alerts_reset_counter'];
    $disable_event = $template['disable_event'];
    $threshold = $template['time_threshold'];
    $fields = [];
    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        $fields[$i] = $template['field'.$i];
    }

    $recovery_notify = $template['recovery_notify'];

    $fields_recovery = [];
    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        $fields_recovery[$i] = $template['field'.$i.'_recovery'];
    }

    $default_action = $template['id_alert_action'];
    $priority = $template['priority'];
    $id_group = $template['id_group'];
    $wizard_level = $template['wizard_level'];
}

print_alert_template_steps($step, $id);

$table = new stdClass();
$table->id = 'template';
$table->width = '100%';
$table->class = 'databox filters w100p filter-table-adv';

$table->style = [];

$table->size = [];
$table->size[0] = '50%';
$table->size[2] = '50%';

$table->colspan = [];
$table->colspan[1][0] = 2;

if ($step == 2) {
    if (!isset($show_matches)) {
        $show_matches = false;
    }

    $data_special_days = Calendar::calendars(
        // Fields.
        [ '`talert_calendar`.*' ],
        // Filter.
        [],
        // Count.
        false,
        // Offset.
        null,
        // Limit.
        null,
        // Order.
        null,
        // Sort field.
        null,
        // Reduce to a select.
        true
    );
    $table->data[0][0] = html_print_label_input_block(
        __('Use special days list'),
        html_print_select(
            $data_special_days,
            'special_day',
            $special_day,
            '',
            __('None'),
            0,
            true,
            false,
            false,
            'w100p',
            (!$is_management_allowed | $disabled),
            'width: 100%'
        )
    );

    $table->data[0][1] = '&nbsp;';

    // Firing conditions and events.
    $table->data[1][0] = html_print_label_input_block(
        __('Schedule'),
        ui_print_warning_message(
            [
                'message'     => __('No alert has been scheduled yet'),
                'force_style' => 'display:none;',
                'force_class' => 'alert_schedule',
            ],
            '',
            true
        ).'<div id="calendar_map" style="width: 90%;"></div>'.html_print_input_hidden('schedule', $schedule, true)
    );

    $table->data[2][0] = html_print_label_input_block(
        __('Time threshold').ui_print_help_tip(__('Reset the alert counter within the configured period if there is no manual recovery or validation of the alert.'), true),
        html_print_extended_select_for_time(
            'threshold',
            $threshold,
            '',
            '',
            '',
            false,
            true,
            false,
            true,
            'w100p',
            (!$is_management_allowed | $disabled)
        )
    );

    $usr_groups = implode(
        ',',
        array_keys(users_get_groups($config['id_user'], 'LM', true))
    );

    $sql_query = sprintf(
        'SELECT id, name
        FROM talert_actions
        WHERE id_group IN (%s)
        ORDER BY name',
        $usr_groups
    );

    $table->data[2][1] = html_print_label_input_block(
        __('Default action').ui_print_help_tip(
            __('Unless they\'re left blank, the fields from the action will override those set on the template.'),
            true
        ),
        html_print_select_from_sql(
            $sql_query,
            'default_action',
            $default_action,
            '',
            __('None'),
            0,
            true,
            false,
            false,
            (!$is_management_allowed | $disabled),
            false,
            false,
            0,
            'w100p'
        )
    );

    $table->data[3][0] = html_print_label_input_block(
        __('Min. number of alerts'),
        html_print_input_text(
            'min_alerts',
            $min_alerts,
            '',
            5,
            7,
            true,
            false,
            false,
            '',
            'w100p',
            '',
            '',
            false,
            '',
            '',
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data[3][1] = html_print_label_input_block(
        __('Reset counter for non-sustained alerts').ui_print_help_tip(
            __('Enable this option if you want the counter to be reset when the alert is not being fired consecutively, even if it\'s within the time threshold'),
            true
        ),
        html_print_checkbox(
            'min_alerts_reset_counter',
            1,
            $min_alerts_reset_counter,
            true,
            (!$is_management_allowed | $disabled),
            '',
            false,
            ($create_template == 1) ? 'checked=checked' : ''
        )
    );

    $table->data[4][0] = html_print_label_input_block(
        __('Max. number of alerts'),
        html_print_input_text(
            'max_alerts',
            $max_alerts,
            '',
            5,
            7,
            true,
            false,
            false,
            '',
            'w100p',
            '',
            '',
            false,
            '',
            '',
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data[4][1] = html_print_label_input_block(
        __('Disable event'),
        html_print_checkbox(
            'disable_event',
            1,
            $disable_event,
            true,
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data[5][0] = html_print_label_input_block(
        __('Condition type'),
        html_print_select(
            alerts_get_alert_templates_types(),
            'type',
            $type,
            '',
            __('None'),
            0,
            true,
            false,
            false,
            'w100p',
            (!$is_management_allowed | $disabled)
        ).'<span id="matches_value" '.(($show_matches) ? '' : 'class="invisible"').'>'.'&nbsp;'.html_print_checkbox('matches_value', 1, $matches, true).html_print_label(
            __('Trigger when matches the value'),
            'checkbox-matches_value',
            true
        ).'</span>'
    );

    $table->data['value'][1] = html_print_label_input_block(
        __('Value'),
        html_print_input_text(
            'value',
            $value,
            '',
            35,
            255,
            true
        ).'&nbsp;<span id="regex_ok">'.html_print_image(
            'images/suc.png',
            true,
            [
                'style' => 'display:none',
                'id'    => 'regex_good',
                'title' => __('The regular expression is valid'),
                'width' => '20px',
            ]
        ).html_print_image(
            'images/err.png',
            true,
            [
                'style' => 'display:none',
                'id'    => 'regex_bad',
                'title' => __('The regular expression is not valid'),
                'width' => '20px',
            ]
        ).'</span>'
    );

    // Min first, then max, that's more logical.
    $table->data['min'][0] = html_print_label_input_block(
        __('Min.'),
        html_print_input_text(
            'min',
            $min,
            '',
            5,
            255,
            true,
            $disabled
        )
    );

    $table->data['max'][1] = html_print_label_input_block(
        __('Max.'),
        html_print_input_text(
            'max',
            $max,
            '',
            5,
            255,
            true,
            $disabled
        )
    );

    $table->data['example'][0] = ui_print_alert_template_example(
        $id,
        true,
        false
    );
} else if ($step == 3) {
    $table->style[1] = 'font-weight: bold; vertical-align: top;';
    $table->style[2] = 'font-weight: bold; vertical-align: top;';
    $table->size = [];
    $table->size[1] = '50%';
    $table->size[2] = '50%';
    $table->colspan[0][0] = 2;

    $table->class = 'databox filters w100p filter-table-adv alert-template-fields';
    // Alert recover.
    if (! $recovery_notify) {
        $table->cellstyle['label_fields'][2] = 'display:none;';
        for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
            $table->cellstyle['field'.$i][2] = 'display:none;';
        }
    }

    $values = [
        false => __('Disabled'),
        true  => __('Enabled'),
    ];
    $table->data[0][0] = html_print_label_input_block(
        __('Alert recovery'),
        html_print_select(
            $values,
            'recovery_notify',
            $recovery_notify,
            '',
            '',
            '',
            true,
            false,
            false,
            'w25p',
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data['label_fields'][1] = '<span class"center">'.__('Firing fields').'</span>';
    $table->data['label_fields'][2] = '<span class"center">'.__('Recovery fields').'</span>';

    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        if (isset($template[$name]) === true) {
            $value = $template[$name];
        } else {
            $value = '';
        }

        // $table->data['field'.$i][0] = sprintf(__('Field %s'), $i);
        // TinyMCE.
        // triggering fields.
        // Basic.
        $col1 = '<div id="command_div"><b><small>';
        $col1 .= __('Basic').'&nbsp;&nbsp;';
        $col1 .= html_print_radio_button_extended(
            'editor_type_value_'.$i,
            0,
            '',
            false,
            (!$is_management_allowed | $disabled),
            "UndefineTinyMCE('#textarea_field".$i."')",
            'style="height: 15px !important;"',
            true
        );
        // Advanced.
        $col1 .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $col1 .= __('Advanced').'&nbsp;&nbsp;';
        $col1 .= html_print_radio_button_extended(
            'editor_type_value_'.$i,
            0,
            '',
            true,
            (!$is_management_allowed | $disabled),
            "defineTinyMCE('#textarea_field".$i."')",
            'style="height: 15px !important;"',
            true
        );
        $col1 .= '</small></b></div>';

        // Texarea.
        $col1 .= html_print_textarea(
            'field'.$i,
            1,
            1,
            isset($fields[$i]) ? $fields[$i] : '',
            'class="fields w100p" style="min-height: 100px !important;"',
            true,
            '',
            (!$is_management_allowed | $disabled)
        );
        $table->data['field'.$i][1] = html_print_label_input_block(
            sprintf(__('Field %s'), $i),
            $col1
        );

        // Recovery.
        // Basic.
        $col2 = '<div id="command_div"><b><small>';
        $col2 .= __('Basic').'&nbsp;&nbsp;';
        $col2 .= html_print_radio_button_extended(
            'editor_type_recovery_value_'.$i,
            0,
            '',
            false,
            (!$is_management_allowed | $disabled),
            "UndefineTinyMCE('#textarea_field".$i."_recovery')",
            'style="height: 15px !important;"',
            true
        );
        // Advanced.
        $col2 .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $col2 .= __('Advanced').'&nbsp;&nbsp;';
        $col2 .= html_print_radio_button_extended(
            'editor_type_recovery_value_'.$i,
            0,
            '',
            true,
            (!$is_management_allowed | $disabled),
            "defineTinyMCE('#textarea_field".$i."_recovery')",
            'style="height: 15px !important;"',
            true
        );
        $col2 .= '</small></b></div>';

        // Texarea.
        $col2 .= html_print_textarea(
            'field'.$i.'_recovery',
            1,
            1,
            isset($fields_recovery[$i]) ? $fields_recovery[$i] : '',
            'class="fields w100p" style="min-height: 100px !important;"',
            true,
            '',
            (!$is_management_allowed | $disabled)
        );
        $table->data['field'.$i][2] = html_print_label_input_block(
            '&nbsp;',
            $col2
        );
    }
} else {
    // Step 1 by default.
    $table->size = [];
    $table->size[0] = '50%';
    $table->size[1] = '50%';
    $table->colspan = [];
    $table->colspan[1][0] = 2;
    $table->data = [];

    $show_matches = false;
    switch ($type) {
        case 'equal':
        case 'not_equal':
        case 'regex':
            $show_matches = true;
            $table->rowstyle['value'] = '';
        break;

        case 'max_min':
            $show_matches = true;
        case 'max':
            $table->rowstyle['max'] = '';
            if ($type == 'max') {
                break;
            }

        case 'min':
            $table->rowstyle['min'] = '';
        break;

        case 'onchange':
            $show_matches = true;
        break;

        default:
            // Not possible.
        break;
    }

    $table->data[0][0] = html_print_label_input_block(
        __('Name'),
        html_print_input_text(
            'name',
            $name,
            '',
            35,
            255,
            true,
            false,
            false,
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    $groups = users_get_groups();
    $own_info = get_user_info($config['id_user']);

    $return_all_group = false;

    if (users_can_manage_group_all('LM') === true || $disabled) {
        $return_all_group = true;
    } else {
        if ($id_group == 0) {
            $id_group = users_get_first_group(false, 'LM', false);
        }
    }

    $table->data[0][1] = html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            false,
            'AR',
            $return_all_group,
            'id_group',
            $id_group,
            '',
            '',
            0,
            true,
            false,
            true,
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data[1][0] = html_print_label_input_block(
        __('Description'),
        html_print_textarea(
            'description',
            10,
            30,
            $description,
            '',
            true,
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    $table->data[2][0] = html_print_label_input_block(
        __('Priority'),
        html_print_select(
            get_priorities(),
            'priority',
            $priority,
            '',
            0,
            0,
            true,
            false,
            false,
            '',
            (!$is_management_allowed | $disabled)
        )
    );

    if (is_metaconsole() === true) {
        $wizard_levels = [
            'nowizard' => __('No wizard'),
            'basic'    => __('Basic'),
            'advanced' => __('Advanced'),
        ];
        $table->data[2][1] = html_print_label_input_block(
            __('Wizard level'),
            html_print_select(
                $wizard_levels,
                'wizard_level',
                $wizard_level,
                '',
                '',
                -1,
                true,
                false,
                false
            )
        );
    } else {
        $table->data[2][1] .= html_print_input_hidden('wizard_level', $wizard_level, true);
    }
}

if ($step == 2) {
    echo ui_get_using_system_timezone_warning();
}

$offset = (int) get_parameter('offset');
// If it's the last step it will redirect to template lists.
if ($step >= LAST_STEP) {
    echo '<form class="max_floating_element_size" method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_templates&pure='.$pure.'&offset='.$offset.'">';
} else {
    echo '<form class="max_floating_element_size" method="post">';
}

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('id', $id);
    html_print_input_hidden('update_template', 1);
} else {
    html_print_input_hidden('create_template', 1);
}

if (!$disabled) {
    if ($is_management_allowed === true) {
        if ($step >= LAST_STEP) {
            $actionButtons = html_print_submit_button(
                __('Finish'),
                'finish',
                false,
                ['icon' => 'wand'],
                true
            );
        } else {
            html_print_input_hidden('step', ($step + 1));
            if ($step == 2) {
                // Javascript onsubmit to avoid min = 0 and max = 0.
                $actionButtons = html_print_submit_button(
                    __('Next'),
                    'next',
                    false,
                    [
                        'class'   => 'submitButton',
                        'onclick' => 'return check_fields_step2();',
                        'icon'    => 'next',
                    ],
                    true
                );
            } else {
                $actionButtons = html_print_submit_button(
                    __('Next'),
                    'next',
                    false,
                    ['icon' => 'next'],
                    true
                );
            }
        }
    }

    html_print_action_buttons($actionButtons, ['type' => 'form_action']);
}

echo '</div>';
echo '</form>';

ui_require_javascript_file('pandora_alerts');
ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/');
ui_require_css_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('pandora_fullcalendar');
?>

<script type="text/javascript">
/* <![CDATA[ */
var matches = <?php echo "'".__('The alert would fire when the value matches <span id="value"></span>')."'"; ?>;
var matches_not = <?php echo '"'.__("The alert would fire when the value doesn\'t match %s", "<span id='value'></span>").'"'; ?>;
var is = <?php echo "'".__('The alert would fire when the value is <span id="value"></span>')."'"; ?>;
var is_not = <?php echo "'".__('The alert would fire when the value is not <span id="value"></span>')."'"; ?>;
var between = <?php echo "'".__('The alert would fire when the value is between <span id="min"></span> and <span id="max"></span>')."'"; ?>;
var between_not = <?php echo '"'.__('The alert would fire when the value is not between <span id=min></span> and <span id=max></span>').'"'; ?>;
var under = <?php echo "'".__('The alert would fire when the value is below <span id="min"></span>')."'"; ?>;
var over = <?php echo "'".__('The alert would fire when the value is above <span id="max"></span>')."'"; ?>;
var warning = <?php echo "'".__('The alert would fire when the module is in warning status')."'"; ?>;
var critical = <?php echo "'".__('The alert would fire when the module is in critical status')."'"; ?>;
var onchange_msg = <?php echo '"'.__('The alert would fire when the module value changes').'"'; ?>;
var onchange_not = <?php echo '"'.__('The alert would fire when the module value does not change').'"'; ?>;
var unknown = <?php echo "'".__('The alert would fire when the module is in unknown status')."'"; ?>;
var error_message_min_max_zero = <?php echo "'".__('The alert template cannot have the same value for min and max thresholds.')."'"; ?>;
var not_normal = <?php echo "'".__('The alert would fire when the module is in not normal status')."'"; ?>;

function check_fields_step2() {
    var correct = true;

    type = $("select[name='type']").val();
    min_v = $("input[name='min']").val();
    max_v = $("input[name='max']").val();

    if (type == 'max_min') {
        if ((min_v == 0) && (max_v == 0)) {
            alert(error_message_min_max_zero);
            correct = false;
        }
    }

    return correct;
}

function check_regex () {
    if ($("#type").val() != 'regex') {
        $("img#regex_good, img#regex_bad").hide ();
        return;
    }
    
    try {
        re = new RegExp ($("#text-value").val());
    } catch (error) {
        $("img#regex_good").hide ();
        $("img#regex_bad").show ();
        return;
    }
    $("img#regex_bad").hide ();
    $("img#regex_good").show ();
}

function render_example () {
    /* Set max */
    var vmax = parseInt ($("input#text-max").val());
    if (isNaN (vmax) || vmax == "") {
        $("span#max").empty ().append ("0");
    }
    else {
        $("span#max").empty ().append (vmax);
    }
    
    /* Set min */
    var vmin = parseInt ($("input#text-min").val());
    if (isNaN (vmin) || vmin == "") {
        $("span#min").empty ().append ("0");
    }
    else {
        $("span#min").empty ().append (vmin);
    }
    
    /* Set value */
    var vvalue = $("input#text-value").val();
    if (vvalue == "") {
        $("span#value").empty ().append ("<em><?php echo __('Empty'); ?></em>");
    }
    else {
        $("span#value").empty ().append (vvalue);
    }
}

// Fix for metaconsole toggle
$('.row_field').css("display", "none");
var hided = true;

function toggle_fields() {
    $('.row_field').toggle();
    if (hided) {
        $('.row_field').css("display", "");
        hided = false;
    }
    else {
        $('.row_field').css("display", "none");
        hided = true;
    }
}

//toggle_fields();

$(document).ready (function () {
<?php
if ($step == 2) {
    ?>
    $("input#text-value").keyup (render_example);
    $("input#text-max").keyup (render_example);
    $("input#text-min").keyup (render_example);

    $("#type").change (function () {
        switch (this.value) {
        case "equal":
        case "not_equal":
            $("img#regex_good, img#regex_bad, span#matches_value").hide ();
            $("#template-max, #template-min").hide ();
            $("#template-value, #template-example").show ();

            /* Show example */
            if (this.value == "equal")
                $("span#example").empty ().append (is);
            else
                $("span#example").empty ().append (is_not);
            break;
        case "regex":
            $("#template-max, #template-min").hide ();
            $("#template-value, #template-example, span#matches_value").show ();
            check_regex ();

            /* Show example */
            if ($("#checkbox-matches_value")[0].checked)
                $("span#example").empty ().append (matches);
            else
                $("span#example").empty ().append (matches_not);
            break;
        case "max_min":
            $("#template-value").hide ();
            $("#template-max, #template-min, #template-example, span#matches_value").show ();

            /* Show example */
            if ($("#checkbox-matches_value")[0].checked)
                $("span#example").empty ().append (between);
            else
                $("span#example").empty ().append (between_not);

            break;
        case "max":
            $("#template-value, #template-min, span#matches_value").hide ();
            $("#template-max, #template-example").show ();

            /* Show example */
            $("span#example").empty ().append (over);
            break;
        case "min":
            $("#template-value, #template-max, span#matches_value").hide ();
            $("#template-min, #template-example").show ();

            /* Show example */
            $("span#example").empty ().append (under);
            break;
        case "warning":
            $("#template-value, #template-max, span#matches_value, #template-min").hide ();
            $("#template-example").show ();

            /* Show example */
            $("span#example").empty ().append (warning);
            break;
        case "critical":
            $("#template-value, #template-max, span#matches_value, #template-min").hide ();
            $("#template-example").show ();

            /* Show example */
            $("span#example").empty ().append (critical);
            break;
        case "not_normal":
            $("#template-value, #template-max, span#matches_value, #template-min").hide ();
            $("#template-example").show ();

            /* Show example */
            $("span#example").empty ().append (not_normal);
            break;
        case "onchange":
            $("#template-value, #template-max, #template-min").hide ();
            $("#template-example, span#matches_value").show ();

            /* Show example */
            if ($("#checkbox-matches_value")[0].checked)
                $("span#example").empty ().append (onchange_msg);
            else
                $("span#example").empty ().append (onchange_not);
            break;
        case "unknown":
            $("#template-value, #template-max, span#matches_value, #template-min").hide ();
            $("#template-example").show ();

            if ($("#text-min_alerts").val() > 0 ) {
                unknown = <?php echo "'".__('The alert would fire when the module is in unknown status. Warning: unknown_updates of pandora_server.conf must be equal to 1')."'"; ?>;
            }

            /* Show example */
            $("span#example").empty ().append (unknown);
            break;
        default:
            $("#template-value, #template-max, #template-min, #template-example, span#matches_value").hide ();
            break;
        }

        render_example ();
    }).change ();

    $("#checkbox-matches_value").click (function () {
        enabled = this.checked;
        type = $("#type").val();
        if (type == "regex") {
            if (enabled) {
                $("span#example").empty ().append (matches);
            }
            else {
                $("span#example").empty ().append (matches_not);
            }
        }
        else if (type == "max_min") {
            if (enabled) {
                $("span#example").empty ().append (between);
            }
            else {
                $("span#example").empty ().append (between_not);
            }
        }
        else if (type == "onchange") {
            if (enabled) {
                $("span#example").empty ().append (onchange_msg);
            }
            else {
                $("span#example").empty ().append (onchange_not);
            }
        }
        render_example ();
    });

    $("#text-value").keyup (check_regex);

    $('#text-time_from, #text-time_to').timepicker({
        showSecond: true,
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>'}
    );

    $("#threshold").change (function () {
        if (this.value == -1) {
            $("#text-other_threshold").val("");
            $("#template-threshold-other_label").show ();
            $("#template-threshold-other_input").show ();
        }
        else {
            $("#template-threshold-other_label").hide ();
            $("#template-threshold-other_input").hide ();
        }
    });

    var is_management_allowed = parseInt('<?php echo (int) $is_management_allowed; ?>');

    var eventsBBDD = $("#hidden-schedule").val();
    if(eventsBBDD === '') {
        eventsBBDD = '<?php echo json_encode($default_events_calendar); ?>';
    }

    var events = loadEventBBDD(eventsBBDD);
    var calendarEl = document.getElementById('calendar_map');

    var options = {
        contentHeight: "auto",
        headerToolbar: {
            left: "",
            center: "",
            right: is_management_allowed === 0 ? '' : "timeGridWeek,dayGridWeek"
        },
        buttonText: {
            dayGridWeek: '<?php echo __('Simple'); ?>',
            timeGridWeek: '<?php echo __('Detailed'); ?>'
        },
        dayHeaderFormat: { weekday: "short" },
        initialView: "dayGridWeek",
        navLinks: false,
        selectable: true,
        selectMirror: true,
        slotDuration: "01:00:00",
        slotLabelInterval: "02:00:00",
        snapDuration: "01:00:00",
        slotMinTime: "00:00:00",
        slotMaxTime: "24:00:00",
        scrollTime: "01:00:00",
        locale: "en-GB",
        firstDay: 1,
        eventTimeFormat: {
            hour: "numeric",
            minute: "2-digit",
            hour12: false
        },
        eventColor: "#82b92e",
        editable: is_management_allowed === 0 ? false : true,
        dayMaxEvents: 3,
        dayPopoverFormat: { weekday: "long" },
        defaultAllDay: false,
        displayEventTime: true,
        displayEventEnd: true,
        selectOverlap: false,
        eventOverlap: false,
        allDaySlot: true,
        droppable: false,
        select: is_management_allowed === 0 ? false : select_alert_template,
        selectAllow: is_management_allowed === 0 ? false : selectAllow_alert_template,
        eventAllow: is_management_allowed === 0 ? false : eventAllow_alert_template,
        eventDrop: is_management_allowed === 0 ? false : eventDrop_alert_template,
        eventDragStop: is_management_allowed === 0 ? false : eventDragStop_alert_template,
        eventResize: is_management_allowed === 0 ? false : eventResize_alert_template,
        eventMouseEnter: is_management_allowed === 0 ? false : eventMouseEnter_alert_template,
        eventMouseLeave: is_management_allowed === 0 ? false : eventMouseLeave_alert_template,
        eventClick: is_management_allowed === 0 ? false : eventClick_alert_template,
    };

    var settings = {
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>',
        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
        removeText:  '<?php echo __('Remove'); ?>',
        userLanguage: '<?php echo get_user_language(); ?>',
        loadingText: '<?php echo __('Loading, this operation might take several minutes...'); ?>',
        tooltipText: '<?php echo __('Drag out to remove'); ?>',
        alert: '<?php echo __('Alert'); ?>'
    }

    var calendar = fullCalendarPandora(calendarEl, options, settings, events);
    calendar.render();
    <?php
} else if ($step == 3) {
    ?>
    $("#recovery_notify").change (function () {
        var max_fields = parseInt('<?php echo $config['max_macro_fields']; ?>');

        if (this.value == 1) {
            $("#template-label_fields-2").show();
            for (i = 1; i <= max_fields; i++) {
                $("#template-field" + i + "-2").show();
            }
            //$("#template-label_fields-2, #template-field1-2, #template-field2-2, #template-field3-2, #template-field4-2, #template-field5-2, #template-field6-2, #template-field7-2, #template-field8-2, #template-field9-2, #template-field10-2").show ();
        }
        else {
            $("#template-label_fields-2").hide();
            for (i = 1; i <= max_fields; i++) {
                $("#template-field" + i + "-2").hide();
            }
            //$("#template-label_fields-2, #template-field1-2, #template-field2-2, #template-field3-2, #template-field4-2, #template-field5-2, #template-field6-2, #template-field7-2, #template-field8-2, #template-field9-2, #template-field10-2").hide ();
        }
    });

    defineTinyMCE('textarea.tiny-mce-editor');

    <?php
}
?>
})
/* ]]> */
</script>
