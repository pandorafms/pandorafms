<?php
/**
 * Special days.
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
require_once 'include/functions_alerts.php';

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

ui_require_javascript_file('calendar');

$id = (int) get_parameter('id');
$date = (string) get_parameter('date');

$name = '';
$command = '';
$description = '';
$same_day = 'monday';
$id_group = 0;
if (empty($id) === false) {
    $special_day = alerts_get_alert_special_day($id);
    $date = str_replace('0001', '*', $special_day['date']);
    $date_orig = $date;
    $same_day = $special_day['same_day'];
    $description = $special_day['description'];
    $id_group = $special_day['id_group'];
    $id_group_orig = $id_group;
}

if (empty($date) === true) {
    $date = date('Y-m-d', get_system_time());
}

// Header.
ui_print_page_header(
    __('Alerts').' &raquo; '.__('Configure special day'),
    'images/gm_alerts.png',
    false,
    '',
    true
);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '20%';
$table->data = [];
$table->data[0][0] = __('Date');
$table->data[0][1] = html_print_input_text(
    'date',
    $date,
    '',
    10,
    10,
    true
);
$table->data[0][1] .= html_print_image(
    'images/calendar_view_day.png',
    true,
    [
        'alt'     => 'calendar',
        'onclick' => "scwShow(scwID('text-date'),this);",
        'class'   => 'invert_filter',
    ]
);
$table->data[1][0] = __('Group');
$groups = users_get_groups();
$own_info = get_user_info($config['id_user']);
// Only display group "All" if user is administrator or has "LM" privileges.
if (users_can_manage_group_all('LM') === true) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

$table->data[1][1] = html_print_select_groups(
    false,
    'LW',
    $display_all_group,
    'id_group',
    $id_group,
    '',
    '',
    0,
    true
);

$table->data[2][0] = __('Same day of the week');
$days = [];
$days['monday'] = __('Monday');
$days['tuesday'] = __('Tuesday');
$days['wednesday'] = __('Wednesday');
$days['thursday'] = __('Thursday');
$days['friday'] = __('Friday');
$days['saturday'] = __('Saturday');
$days['sunday'] = __('Sunday');
$table->data[2][1] = html_print_select(
    $days,
    'same_day',
    $same_day,
    '',
    '',
    0,
    true,
    false,
    false
);

$table->data[3][0] = __('Description');
$table->data[3][1] = html_print_textarea(
    'description',
    10,
    30,
    $description,
    '',
    true
);

echo '<form method="post" id="form-special-days" action="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if (empty($id) === false) {
    html_print_input_hidden('id', $id);
    html_print_input_hidden('update_special_day', 1);
    html_print_input_hidden('id_group_orig', $id_group_orig);
    html_print_input_hidden('date_orig', $date_orig);
    html_print_submit_button(__('Update'), 'create', false, 'class="sub upd"');
} else {
    html_print_input_hidden('create_special_day', 1);
    html_print_submit_button(__('Create'), 'create', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';
echo '<div id="modal-alert-templates" class="invisible"></div>';

ui_require_javascript_file('pandora_alerts');
?>
<script type="text/javascript">
$(document).ready (function () {
    $("#submit-create").click (function (e) {
        e.preventDefault();
        var date = new Date($("#text-date").val());
        var dateformat = date.toLocaleString(
            'default',
            {day: 'numeric', month: 'short',  year: 'numeric'}
        );

        load_templates_alerts_special_days({
            date: $("#text-date").val(),
            id_group: $("#id_group").val(),
            same_day: $("#same_day").val(),
            btn_ok_text: '<?php echo __('Create'); ?>',
            btn_cancel_text: '<?php echo __('Cancel'); ?>',
            title: dateformat,
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            page: "godmode/alerts/alert_special_days",
            loading: '<?php echo __('Loading, this operation might take several minutes...'); ?>',
            name_form: 'form-special-days'
        });
    });
});
</script>