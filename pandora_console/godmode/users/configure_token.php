<?php
/**
 * Configure Token.
 *
 * @category   Users
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *
 * Pandora FMS - https://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2024 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Global variables.
global $config;

check_login();

require_once $config['homedir'].'/include/functions_token.php';

enterprise_include_once('meta/include/functions_users_meta.php');

// Get parameters.
$tab = get_parameter('tab', 'token');
$pure = get_parameter('pure', 0);
$id_token = (int) get_parameter('id_token');

// Header.
if (is_metaconsole() === false) {
    user_print_header($pure, $tab);
    $sec2 = 'gusuarios';
} else {
    user_meta_print_header();
    $sec2 = 'advanced';
}

$url_list = 'index.php?sec='.$sec;
$url_list .= '&sec2=godmode/users/token_list';
$url_list .= '&pure='.$pure;

// Edit token.
if (empty($id_token) === true) {
    $label = '';
    $validity = '';
    $page_title = __('Create token');
} else {
    try {
        $token = get_user_token($id_token);
    } catch (\Exception $e) {
        ui_print_error_message(
            __('There was a problem get token, %s', $e->getMessage())
        );
    }
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->data = [];
$table->rowspan = [];
$table->colspan = [];

$table->data[0][0] = __('Token label');
$table->data[0][1] = html_print_input_text(
    'label',
    $token['label'],
    '',
    50,
    255,
    true
);

if ((bool) users_is_admin() === true) {
    $table->data[0][2] = __('User');
    $user_users = users_get_user_users(
        $config['id_user'],
        'AR',
        true
    );

    $table->data[0][3] = html_print_select(
        $user_users,
        'idUser',
        $config['id_user'],
        '',
        '',
        0,
        true
    );
}

$expiration_date = null;
$expiration_time = null;
if (empty($token['validity']) === false) {
    $array_date = explode(' ', io_safe_output($token['validity']));
    if (is_array($array_date) === true) {
        $expiration_date = $array_date[0];
        if (isset($array_date[1]) === true
            && empty($array_date[1]) === false
        ) {
            $expiration_time = $array_date[1];
        }
    }
}

$table->data[1][0] = __('Expiration');
$table->data[1][1] = html_print_input_text(
    'date-expiration',
    $expiration_date,
    '',
    50,
    255,
    true
);

$table->data[1][2] = __('Expiration Time');
$table->data[1][3] = html_print_input_text(
    'time-expiration',
    $expiration_time,
    '',
    50,
    255,
    true
);

echo '<form class="max_floating_element_size" method="post" action="'.$url_list.'">';

html_print_table($table);

$actionButtons = [];

if (empty($id_token) === true) {
    $actionButtons[] = html_print_submit_button(
        __('Create'),
        'crt',
        false,
        ['icon' => 'wand'],
        true
    );
    html_print_input_hidden('create_token', 1);
} else {
    $actionButtons[] = html_print_submit_button(
        __('Update'),
        'upd',
        false,
        ['icon' => 'update'],
        true
    );

    html_print_input_hidden('id_token', $id_token);
    html_print_input_hidden('update_token', 1);
}

$actionButtons[] = html_print_go_back_button(
    ui_get_full_url($url_list),
    ['button_class' => ''],
    true
);

html_print_action_buttons(
    implode('', $actionButtons),
    ['type' => 'form_action']
);

echo '</form>';

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

?>

<script type="text/javascript" language="javascript">
    $(document).ready (function () {
        $('#text-date-expiration').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showAnim: 'slideDown'
        });

        $('[id^=text-time-expiration]').timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'
        });
    });
</script>
