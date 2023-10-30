<?php
/**
 * ITSM View edition tickets
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage ITSM
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';

global $config;

// Header tabs.
ui_print_standard_header(
    __('ITSM Edit'),
    '',
    false,
    'ITSM_tab',
    false,
    $headerTabs,
    [
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm&operation=list',
            'label' => __('ITSM Tickets'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm&operation=edit',
            'label' => __('Edit'),
        ],
    ]
);

if (empty($error) === false) {
    ui_print_error_message($error);
}

if (empty($successfullyMsg) === false) {
    ui_print_success_message($successfullyMsg);
}

// Main table.
$table = new stdClass();
$table->width = '100%';
$table->id = 'edit-ticket-itms';
$table->class = 'databox filter-table-adv';
$table->data = [];
$table->colspan[0][0] = 2;
$table->colspan[2][0] = 3;
$table->colspan[5][0] = 3;
$table->colspan[6][0] = 3;

$table->data[0][0] = html_print_label_input_block(
    __('Title'),
    html_print_input_text(
        'title',
        ($incidence['title'] ?? ''),
        __('Name'),
        30,
        100,
        true,
        false,
        true,
        '',
        'w100p'
    )
);

$ITSM_logo = 'images/pandoraITSM_logo_gray.png';
if ($config['style'] === 'pandora_black' && is_metaconsole() === false) {
    $ITSM_logo = 'images/pandoraITSM_logo.png';
}

$table->data[0][2] = '<div style="max-width: 150px; float:right;">'.html_print_image(
    $ITSM_logo,
    true,
    ['style' => 'width: -webkit-fill-available;'],
    false
).'</div>';
$table->data[1][0] = html_print_label_input_block(
    __('Type'),
    html_print_select(
        $objectTypes,
        'idIncidenceType',
        ($incidence['idIncidenceType'] ?? ''),
        '',
        __('Select'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Group'),
    html_print_select(
        $groups,
        'idGroup',
        ($incidence['idGroup'] ?? ''),
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[1][2] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
        $priorities,
        'priority',
        ($incidence['priority'] ?? 0),
        '',
        '',
        1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[2][0] = '<div class="object-type-fields">WIP...</div>';

$table->data[3][0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $status,
        'status',
        ($incidence['status'] ?? 0),
        '',
        '',
        1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[3][1] = html_print_label_input_block(
    __('Creator').ui_print_help_tip(
        __('This field corresponds to the ITSM user specified in ITSM setup'),
        true
    ),
    html_print_input_text(
        'idCreator',
        '',
        '',
        0,
        100,
        true,
        true,
        false,
        '',
        'w100p'
    )
);

$table->data[3][2] = html_print_label_input_block(
    __('Owner').ui_print_help_tip(__('Type at least two characters to search the user.'), true),
    html_print_autocomplete_users_from_pandora_itsm(
        'owner',
        ($incidence['owner'] ?? ''),
        true,
        0,
        false,
        true,
        'w100p',
    )
);

$table->data[4][0] = '<div id="incidence-resolution" class="invisible">'.html_print_label_input_block(
    __('Resolution'),
    html_print_select(
        $resolutions,
        'resolution',
        ($incidence['resolution'] ?? 0),
        '',
        __('None'),
        null,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    ).'</div>'
);

$table->data[5][0] = html_print_label_input_block(
    __('Description').$help_macros,
    html_print_textarea(
        'description',
        3,
        20,
        ($incidence['description'] ?? ''),
        '',
        true
    )
);

$formName = 'create_itsm_incident_form';
$classForm = 'max_floating_element_size';
$enctype = 'multipart/form-data';
echo '<form class="'.$classForm.'" id="'.$formName.'" name="'.$formName.'" method="POST" enctype="'.$enctype.'">';
html_print_table($table);
$buttons = '';
if (empty($idIncidence) === true) {
    $buttons .= html_print_input_hidden('create_incidence', 1, true);
    $buttons .= html_print_submit_button(
        __('Create'),
        'accion',
        false,
        [ 'icon' => 'next' ],
        true
    );
} else {
    $buttons .= html_print_input_hidden('update_incidence', 1, true);
    $buttons .= html_print_input_hidden('idIncidence', $idIncidence, true);
    $buttons .= html_print_submit_button(
        __('Update'),
        'accion',
        false,
        [ 'icon' => 'upd' ],
        true
    );
}

html_print_action_buttons($buttons);

echo '</form>';

ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/');
?>

<script type="text/javascript">
    $(document).ready(function () {
        var ajax_url = '<?php echo ui_get_full_url('ajax.php'); ?>';
        var fieldsData = '<?php echo base64_encode(json_encode($incidence['typeFieldData'])); ?>';

        defineTinyMCE('#textarea_description');

        $('#status').on('change', function() {
            if ($(this).val() === 'CLOSED') {
                $('#incidence-resolution').show();
            } else {
                $('#incidence-resolution').hide();
            }
        }).trigger('change');

        $('#idIncidenceType').on('change', function() {
            if ($(this).val() != 0) {
                $('.object-type-fields').show();
                var output = getInputFieldsIncidenceType(
                    $(this).val(),
                    fieldsData,
                    ajax_url
                );
            } else {
                $('.object-type-fields').hide();
            }
        }).trigger('change');
    });
</script>