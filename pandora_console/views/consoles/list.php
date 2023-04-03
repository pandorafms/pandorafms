<?php
/**
 * Console: Consoles list page.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Alert
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

// Header.
ui_print_standard_header(
    __('%s registered consoles', $config['rb_product_name']),
    '',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('Servers'),
        ],
    ]
);


if (empty($message) === false) {
    echo $message;
}

// Auxiliar to display deletion modal.
echo '<div id="delete_modal" class="invisible"></div>';
echo '<div id="msg" class="invisible"></div>';


// Consoles list.
try {
    $columns = [
        'id_console',
        'description',
        'version',
        'last_execution',
        'console_type',
        'timezone',
        'public_url',
        'options',
    ];

    $column_names = [
        __('Console ID'),
        __('Description'),
        __('Version'),
        __('Last Execution'),
        __('Console type'),
        __('Timezone'),
        __('Public URL'),
        [
            'text'  => __('Options'),
            'class' => 'action_buttons',
        ],
    ];


    $tableId = 'consoles_list';
    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'include/ajax/consoles.ajax',
            'ajax_data'           => ['get_all_datatables_formatted' => 1],
            'ajax_postprocess'    => 'process_datatables_item(item)',
            'no_sortable_columns' => [-1],
            'order'               => [
                'field'     => 'id',
                'direction' => 'asc',
            ],
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
<script type="text/javascript">
    /**
    * Process datatable item before draw it.
    */
    function process_datatables_item(item) {
        item.options = '<a href="javascript:" onclick="delete_key(\'';
        item.options += item.id;
        item.options += '\')" ><?php echo html_print_image('images/cross.png', true, ['title' => __('Delete'), 'class' => 'invert_filter']); ?></a>';
    }

    /**
     * Delete selected key
     */
    function delete_key(id) {
        $('#delete_modal').empty();
        $('#delete_modal').html('<?php echo __('<span>Are you sure?</span><br><br><i>WARNING: you also need to delete config.php options in your console or delete the whole console.</i>'); ?>');
        $('#delete_modal').dialog({
            title: '<?php echo __('Delete'); ?>',
            buttons: [
                {
                    class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                    text: '<?php echo __('Cancel'); ?>',
                    click: function(e) {
                        $(this).dialog('close');
                        cleanupDOM();

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
                                page: 'include/ajax/consoles.ajax',
                                delete: 1,
                                id
                            },
                            datatype: "json",
                            success: function (data) {
                                showMsg(data);
                            },
                            error: function(e) {
                                showMsg(e);
                            }
                        });
                    }
                }
            ]
        });
    }

    /**
    * Process ajax responses and shows a dialog with results.
    */
    function showMsg(data) {
        var title = "<?php echo __('Success'); ?>";
        var dt_satellite_agents = $("#<?php echo $tableId; ?>").DataTable();
        dt_<?php echo $tableId; ?>.draw(false);

        var text = '';
        var failed = 0;
        try {
            data = JSON.parse(data);
            text = data['result'];
        } catch (err) {
            title =  "<?php echo __('Failed'); ?>";
            text = err.message;
            failed = 1;
        }
        if (!failed && data['error'] != undefined) {
            title =  "<?php echo __('Failed'); ?>";
            text = data['error'];
            failed = 1;
        }
        if (data['report'] != undefined) {
            data['report'].forEach(function (item){
                text += '<br>'+item;
            });
        }

        $('#msg').empty();
        $('#msg').html(text);
        $('#msg').dialog({
            width: 450,
            position: {
                my: 'center',
                at: 'center',
                of: window,
                collision: 'fit'
            },
            title: title,
            buttons: [
                {
                    class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                    text: 'OK',
                    click: function(e) {
                        if (!failed) {
                            $(".ui-dialog-content").dialog("close");
                            $('.info').hide();
                        } else {
                            $(this).dialog('close');
                        }
                    }
                }
            ]
        });
    }

</script>