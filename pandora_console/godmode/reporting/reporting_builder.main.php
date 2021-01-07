<?php
/**
 * Reporting builder main.
 *
 * @category   Options reports.
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

// Login check.
check_login();

if (! check_acl($config['id_user'], 0, 'RW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_users.php';

$groups = users_get_groups();

switch ($action) {
    default:
    case 'new':
        $actionButtonHtml = html_print_submit_button(
            __('Save'),
            'add',
            false,
            'class="sub wand"',
            true
        );
        $hiddenFieldAction = 'save';
    break;
    case 'update':
    case 'edit':
        $actionButtonHtml = html_print_submit_button(
            __('Update'),
            'edit',
            false,
            'class="sub upd"',
            true
        );
        $hiddenFieldAction = 'update';
    break;
}

$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filters';
$table->head = [];

if (is_metaconsole() === true) {
    $table->head[0] = __('Main data');
    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
}

$table->data = [];
$table->size = [];
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '90%';
if (is_metaconsole() === false) {
    $table->style[0] = 'font-weight: bold; vertical-align: top;';
} else {
    $table->style[0] = 'font-weight: bold;';
}

$table->data['name'][0] = __('Name');
$table->data['name'][1] = html_print_input_text(
    'name',
    $reportName,
    __('Name'),
    80,
    100,
    true,
    false,
    true
);

$table->data['group'][0] = __('Group');
$write_groups = users_get_groups_for_select(
    false,
    'AR',
    true,
    true,
    false,
    'id_grupo'
);

// If the report group is not among the
// RW groups (special permission) we add it.
if (isset($write_groups[$idGroupReport]) === false && $idGroupReport) {
    $write_groups[$idGroupReport] = groups_get_name($idGroupReport);
}

$table->data['group'][1] = '<div class="w290px inline">';
$table->data['group'][1] .= html_print_select_groups(
    $config['id_user'],
    'AR',
    true,
    'id_group',
    $idGroupReport,
    '',
    '',
    '',
    true
);
$table->data['group'][1] .= '</div>';

if ($report_id_user == $config['id_user']
    || is_user_admin($config['id_user'])
) {
    // S/he is the creator of report (or admin) and s/he can change the access.
    $type_access = [
        'group_view' => __('Only the group can view the report'),
        'group_edit' => __('The next group can edit the report'),
        'user_edit'  => __('Only the user and admin user can edit the report'),
    ];
    $table->data['access'][0] = __('Write Access');
    $table->data['access'][0] .= ui_print_help_tip(
        __('For example, you want a report that the people of "All" groups can see but you want to edit only for you or your group.'),
        true
    );
    $table->data['access'][1] = html_print_select(
        $type_access,
        'type_access',
        $type_access_selected,
        'change_type_access(this)',
        '',
        0,
        true
    );

    $style = 'display: none;';
    if ($type_access_selected == 'group_edit') {
        $style = '';
    }

    $table->data['access'][1] .= '<span style="'.$style.'" class="access_subform" id="group_edit">';
    $table->data['access'][1] .= '<div class="w290px inline">';
    $table->data['access'][1] .= html_print_select_groups(
        false,
        'RW',
        false,
        'id_group_edit',
        $id_group_edit,
        false,
        '',
        '',
        true
    );
    $table->data['access'][1] .= '</div>';
    $table->data['access'][1] .= '</span>';
}

if ($enterpriseEnable) {
    $non_interactive_check = false;
    if (isset($non_interactive)) {
        $non_interactive_check = $non_interactive;
    }

    $table->data['interactive_report'][0] = __('Non interactive report');
    $table->data['interactive_report'][1] = __('Yes');
    $table->data['interactive_report'][1] .= '&nbsp;&nbsp;&nbsp;';
    $table->data['interactive_report'][1] .= html_print_radio_button(
        'non_interactive',
        1,
        '',
        $non_interactive_check,
        true
    );
    $table->data['interactive_report'][1] .= '&nbsp;&nbsp;';
    $table->data['interactive_report'][1] .= __('No');
    $table->data['interactive_report'][1] .= '&nbsp;&nbsp;&nbsp;';
    $table->data['interactive_report'][1] .= html_print_radio_button(
        'non_interactive',
        0,
        '',
        $non_interactive_check,
        true
    );
}

$table->data['description'][0] = __('Description');
$table->data['description'][1] = html_print_textarea(
    'description',
    5,
    15,
    $description,
    '',
    true
);

if (enterprise_installed() === true) {
    $table->data['cover'][0] = __('Generate cover page in PDF render');
    $table->data['cover'][1] = html_print_checkbox_switch(
        'cover_page_render',
        1,
        $cover_page_render,
        true
    );

    $table->data['index'][0] = __('Generate index in PDF render');
    $table->data['index'][1] = html_print_checkbox_switch(
        'index_render',
        1,
        $index_render,
        true
    );
}

echo '<form class="" method="post">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo $actionButtonHtml;
html_print_input_hidden('action', $hiddenFieldAction);
html_print_input_hidden('id_report', $idReport);
echo '</div></form>';
?>
<script type="text/javascript">
    function change_type_access(select_item) {
        $(".access_subform").hide();
        if ($(select_item).val() == "group_edit") {
            $("#group_edit").show()
        } else {
            $("#group_edit").hide()
        }

    }
</script>
