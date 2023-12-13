<?php
/**
 * Version expiration date editor
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

// Datatables list.
try {
    $columns = [
        'product',
        'version',
        'end_of_support',
        'options',
    ];

    $column_names = [
        __('Product'),
        __('Version'),
        __('End of support date'),
        [
            'text'  => __('Options'),
            'class' => 'w100px table_action_buttons',
        ],
    ];

    $tableId = 'os_version_table';
    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'include/ajax/os',
            'ajax_data'           => ['method' => 'drawOSVersionTable'],
            'ajax_postprocess'    => 'process_datatables_item(item)',
            'no_sortable_columns' => [-1],
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

echo '<div id="aux" class="invisible"></div>';

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/setup/os&tab=manage_version&action=edit">';

html_print_action_buttons(
    html_print_submit_button(__('Create OS version'), 'update_button', false, ['icon' => 'next'], true),
    ['type' => 'form_action']
);

echo '</form>';

echo '<form id="redirect-form" method="post" action="index.php?sec=view&sec2=operation/agentes/estado_agente">';
html_print_input_hidden('os_type_regex', '');
html_print_input_hidden('os_version_regex', '');

echo '</form>';

?>

<script language="javascript" type="text/javascript">
    function process_datatables_item(item) {
        id = item.id_os_version;

        idrow = '<b><a href="javascript:" onclick="show_form(\'';
        idrow += item.id_os_version;
        idrow += '\')" >'+item.id_os_version+'</a></b>';
        item.id_os_version = idrow;
        item.options = '<div class="table_action_buttons">';
        item.options += '<a href="index.php?sec=gagente&amp;sec2=godmode/setup/os&amp;tab=manage_version&amp;action=edit&amp;id_os=';
        item.options += id;
        item.options += '" ><?php echo html_print_image('images/edit.svg', true, ['title' => __('Edit'), 'class' => 'main_menu_icon invert_filter']); ?></a>';

        item.options += '<a href="javascript:" onclick="redirect_to_agents_by_version(\'';
        item.options += item.product;
        item.options += '\',\'';
        item.options += item.version;
        item.options += '\')" ><?php echo html_print_image('images/agents.svg', true, ['title' => __('Show agents'), 'class' => 'main_menu_icon invert_filter']); ?></a>';

        item.options += '<a href="javascript:" onclick="delete_os_version(\'';
        item.options += id;
        item.options += '\')" ><?php echo html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'main_menu_icon invert_filter']); ?></a>';
        item.options += '</div>';

        item.options += '<form method="post" action="?sec=view&sec2=operation/agentes/estado_agente"></form>';
    }

    function redirect_to_agents_by_version(product, version) {
        $('#hidden-os_type_regex').val(product);
        $('#hidden-os_version_regex').val(version);
        $('#redirect-form').submit();
    }

    /**
     * Delete selected OS version
     */
    function delete_os_version(id) {
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
                                method: 'deleteOSVersion',
                                id_os_version: id
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