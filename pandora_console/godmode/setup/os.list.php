<?php
/**
 * Os List.
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

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/component_management&tab=list&tab2=list&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All OS definitions are read only. Go to %s to manage them.',
            $url
        )
    );
}

// Datatables list.
try {
    $columns = [
        'id_os',
        'icon_img',
        'name',
        'description',
        'options',
    ];

    $column_names = [
        [
            'text'  => __('ID'),
            'class' => 'w50px table_action_buttons',
        ],
        [
            'text'  => __('Icon'),
            'class' => 'w10px table_action_buttons',
        ],
        __('Name'),
        __('Description'),
        [
            'text'  => __('Options'),
            'class' => 'w20px table_action_buttons',
        ],
    ];

    $tableId = 'os_table';
    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'include/ajax/os',
            'ajax_data'           => ['method' => 'drawOSTable'],
            'pagination_options'  => [
                [
                    $config['block_size'],
                    10,
                    25,
                    100,
                    200,
                    500,
                ],
                [
                    $config['block_size'],
                    10,
                    25,
                    100,
                    200,
                    500,
                ],
            ],
            'ajax_postprocess'    => 'process_datatables_item(item)',
            'no_sortable_columns' => [
                -1,
                1,
            ],
            'order'               => [
                'field'     => 'id',
                'direction' => 'asc',
            ],
            'search_button_class' => 'sub filter float-right',
            'form'                => [
                'inputs' => [
                    [
                        'label' => __('Free search'),
                        'type'  => 'text',
                        'class' => 'w25p',
                        'id'    => 'free_search',
                        'name'  => 'free_search',
                    ],
                ],
            ],
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
            'dom_elements'        => 'lftpB',
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

$buttons = '';
if (is_metaconsole() === true) {
    $buttons .= '<form method="post" action="index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2=builder">';
    $buttons .= html_print_submit_button(
        __('Create OS'),
        '',
        false,
        ['icon' => 'next'],
        true
    );
    $buttons .= '</form>';
} else {
    $buttons .= '<form method="post" action="index.php?sec=gagente&sec2=godmode/setup/os&tab=manage_os&action=edit">';
    $buttons .= html_print_submit_button(__('Create OS'), 'update_button', false, ['icon' => 'next'], true);
    $buttons .= '</form>';
}

html_print_action_buttons(
    $buttons,
    [
        'type'  => 'data_table',
        'class' => 'fixed_action_buttons',
    ]
);

echo '<div id="aux" class="invisible"></div>';

?>
<script language="javascript" type="text/javascript">
    function process_datatables_item(item) {
        item.options = '<div class="table_action_buttons">';
        if (item.enable_delete === true) {
            var delete_id = item.id_os;
            item.options += '<a href="javascript:" onclick="delete_os(\'';
            item.options += delete_id;
            item.options += '\')" ><?php echo html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'main_menu_icon invert_filter']); ?></a>';
        }
        item.options += '</div>';
    }

    /**
     * Delete selected OS
     */
    function delete_os(id) {
        $('#aux').empty();
        $('#aux').text('<?php echo __('Are you sure?'); ?>');
        $('#aux').dialog({
            title: '<?php echo __('Delete'); ?> ' + id,
            buttons: [
                {
                    class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                    text: '<?php echo __('Cancel'); ?>',
                    click: function(e) {
                        $(this).dialog('close');
                    }
                },
                {
                    text: 'Delete',
                    class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                    click: function(e) {
                        $.ajax({
                            method: 'post',
                            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                            data: {
                                page: 'include/ajax/os',
                                method: 'deleteOS',
                                id_os: id
                            },
                            datatype: "json",
                            success: function (data) {
                                var r = JSON.parse(data);
                                if (r.deleted === false) {
                                    $('#aux').text('<?php echo __('Not deleted. Error deleting data'); ?>');
                                } else {
                                    $('#aux').dialog('close');
                                    location.reload();
                                }
                            },
                            error: function(e) {
                                $('#aux').text('<?php echo __('Not deleted. Error deleting data'); ?>');
                            }
                        });
                    }
                }
            ]
        });
    }
</script>
