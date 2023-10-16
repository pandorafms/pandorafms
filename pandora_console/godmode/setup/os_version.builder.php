<?php
/**
 * OS Builder
 *
 * @category   Os
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

ui_require_css_file('datepicker');
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
ui_include_time_picker();
ui_require_javascript_file('pandora');

if ($idOS > 0) {
    $os_version = db_get_row_filter('tconfig_os_version', ['id_os_version' => $idOS]);
    $product = $os_version['product'];
    $version = $os_version['version'];
    $end_of_life_date = $os_version['end_of_support'];
} else {
    $product = io_safe_input(strip_tags(io_safe_output((string) get_parameter('product'))));
    $version = io_safe_input(strip_tags(io_safe_output((string) get_parameter('version'))));
    $end_of_life_date = get_parameter('end_of_life_date', date('Y/m/d'));
}

$message = '';
if ($is_management_allowed === true) {
    switch ($action) {
        case 'edit':
            if ($idOS > 0) {
                $actionHidden = 'update';
                $textButton = __('Update');
                $classButton = ['icon' => 'wand'];
            } else {
                $actionHidden = 'save';
                $textButton = __('Create');
                $classButton = ['icon' => 'next'];
            }
        break;

        case 'save':
            $values = [];
            // Product and version must be stored with no entities to be able to use REGEXP in queries.
            // CAREFUL! output of these fields must be encoded to avoid scripting vulnerabilities.
            $values['product'] = io_safe_output($product);
            $values['version'] = io_safe_output($version);
            $values['end_of_support'] = $end_of_life_date;

            $result = db_process_sql_insert('tconfig_os_version', $values);

            if ($result === false) {
                $message = 2;
            } else {
                $message = 1;
            }

            $tab = 'manage_version';

            header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
        break;

        case 'update':
            $product = io_safe_output(get_parameter('product'));
            $version = io_safe_output(get_parameter('version'));
            $end_of_life_date = get_parameter('end_of_life_date', 0);
            $values = [];
            $values['product'] = $product;
            $values['version'] = $version;
            $values['end_of_support'] = $end_of_life_date;
            $result = db_process_sql_update('tconfig_os_version', $values, ['id_os_version' => $idOS]);

            if ($result === false) {
                $message = 4;
            } else {
                $message = 3;
            }

            $tab = 'manage_version';

            header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
        break;

        case 'delete':
            $sql = 'SELECT COUNT(id_os) AS count FROM tagente WHERE id_os = '.$idOS;
            $count = db_get_all_rows_sql($sql);
            $count = $count[0]['count'];

            if ($count > 0) {
                $message = 5;
            } else {
                $result = (bool) db_process_sql_delete('tconfig_os', ['id_os' => $idOS]);
                if ($result) {
                    $message = 6;
                } else {
                    $message = 7;
                }
            }

            if (is_metaconsole() === true) {
                header('Location:'.$config['homeurl'].'index.php?sec=advanced&sec2=advanced/component_management&tab=list&tab2='.$tab.'&message='.$message);
            } else {
                header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
            }
        break;

        default:
        case 'new':
            $actionHidden = 'save';
            $textButton = __('Create');
            $classButton = ['icon' => 'next'];
        break;
    }
}

echo '<form id="form_setup" method="post">';
$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filter-table-adv';

// $table->style[0] = 'width: 15%';
$table->data[0][] = html_print_label_input_block(
    __('Product'),
    html_print_input_text('product', io_safe_input($product), __('Product'), 20, 300, true, false, false, '', 'w250px')
);

$table->data[0][] = html_print_label_input_block(
    __('Version'),
    html_print_input_text('version', io_safe_input($version), __('Version'), 20, 300, true, false, false, '', 'w250px')
);

$timeInputs = [];

$timeInputs[] = html_print_div(
    [
        'id'      => 'end_of_life_date',
        'style'   => '',
        'content' => html_print_div(
            [
                'class'   => '',
                'content' => html_print_input_text(
                    'end_of_life_date',
                    $end_of_life_date,
                    '',
                    10,
                    10,
                    true
                ),
            ],
            true
        ),
    ],
    true
);

$table->data[1][] = html_print_label_input_block(
    __('End of life date'),
    implode('', $timeInputs)
);

html_print_table($table);

html_print_input_hidden('id_os', $idOS);
html_print_input_hidden('action', $actionHidden);

html_print_action_buttons(
    html_print_submit_button($textButton, 'update_button', false, $classButton, true),
    ['type' => 'form_action']
);

echo '</form>';

?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
    $("#text-end_of_life_date").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>", showButtonPanel: true});

});
</script>