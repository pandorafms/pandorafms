<?php

/**
 * Server list view.
 *
 * @category   Server
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
 * Copyright (c) 2005-2024 Pandora FMS
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

if (check_acl($config['id_user'], 0, 'PM') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access resources exportation and importation'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/class/Prd.class.php';
$msg = '';

// Instance of the prd class.
$prd = new Prd();

if (isset($_FILES['resource_import']) === true) {
    $data = parse_ini_file($_FILES['resource_import']['tmp_name'], true);
    if ($data !== false) {
        $msg = $prd->importPrd($data);
        $array_msg = [];
        foreach ($msg['items'] as $key => $value) {
            $array_msg[$value[0]][key($value[1])][$value[1][key($value[1])]] = $value[1][key($value[1])];
        }

        $msg['items'] = $array_msg;
    } else {
        $msg = [
            'status' => false,
            'items'  => [],
            'errors' => ['Unexpected error: Unable to parse PRD file.'],
        ];
    }
}

$msg = json_encode($msg);

$table = new stdClass();
$table->id = 'import_data_table';
$table->class = 'databox filter-table-adv';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->size = [];

$table->data[0][0] = html_print_label_input_block(
    __('Resource importation'),
    html_print_input_file('resource_import', true)
);

$table->data[0][0] .= html_print_submit_button(
    __('Import resource'),
    'upload',
    false,
    [],
    true
);

echo '<form name="submit_import" method="POST" enctype="multipart/form-data">';
html_print_table($table);
echo '</form>';

$table = new stdClass();
$table->id = 'export_data_table';
$table->class = 'databox filter-table-adv';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$export_type = $prd->getTypesPrd();

$table->data[0][0] = html_print_label_input_block(
    __('Export type'),
    html_print_select(
        $export_type,
        'export_type',
        '',
        '',
        __('None'),
        0,
        true,
        false,
        true,
        'w40p'
    )
);

$table->data[1][0] = '';

$table->data[2][0] = html_print_button(
    __('Export'),
    'export_button',
    false,
    '',
    ['class' => 'flex_justify invisible_important'],
    true
);

html_print_table($table);

?>
<script type="text/javascript">
    let msg = <?php echo $msg; ?>;
    if (typeof msg === 'object' && Object.keys(msg).length > 0) {
        let title = "";
        let message = "";
        if (msg.status === true) {
            title = "<?php echo __('Resource successfully imported'); ?>";
            message = "<?php echo __('List of items created:'); ?>";
            message += "<br>";
            Object.entries(msg.items).forEach(([table, value]) => {
                message += table + "&nbsp;";
                Object.entries(value).forEach(([field, value2]) => {
                    message += "with " + field + ":&nbsp;(";
                    if (typeof value2 === 'object' && Object.keys(value2).length > 0) {
                        let cont = 0;
                        Object.entries(value2).forEach(([key3, value3]) => {
                            message += value3 + " , ";
                            if (cont === 6) {
                                message += "<br>";
                            }
                            cont++;
                        });
                    }
                });
                message = message.substring(0, message.length - 3);
                message += ")";
                message += "<br>";
            });

        } else {
            title = "<?php echo __('Import error'); ?>";
            Object.entries(msg.errors).forEach(([key, value]) => {
                message += value + "<br>";
            });
        }

        confirmDialog({
                title: title,
                message: message,
                hideCancelButton: true
            },
            "ResultDialog"
        );
    }

    $("#export_type").change(function(e) {
        if ($(this).val() === '0') {
            $("#button-export_button").addClass("invisible_important");
            $("#export_data_table-1-0").html('');
        } else {
            $("#export_data_table-1-0").html('');
            $.ajax({
                type: "GET",
                url: "ajax.php",
                dataType: "html",
                data: {
                    page: 'include/ajax/resources.ajax',
                    getResource: 1,
                    type: $(this).val(),
                },
                success: function(data) {
                    $("#export_data_table-1-0").append(`${data}`);
                    $('#select_value').select2();
                    $("#button-export_button").removeClass("invisible_important");
                },
                error: function(data) {
                    console.error("Fatal error in AJAX call to interpreter order", data)
                }
            });
        }
    });

    $("#button-export_button").click(function(e) {
        const value = $("#select_value").val();
        if (value !== '0') {
            //Show dialog.
            confirmDialog({
                    title: "<?php echo __('Exporting resource'); ?>",
                    message: "<?php echo __('Exporting resource and downloading, please wait'); ?>",
                    hideCancelButton: true
                },
                "downloadDialog"
            );

            $.ajax({
                type: "GET",
                url: "ajax.php",
                data: {
                    page: 'include/ajax/resources.ajax',
                    exportPrd: 1,
                    type: $("#export_type").val(),
                    value: value,
                    name: $("#select_value option:selected").text(),
                },
                success: function(data) {
                    let a = document.createElement('a');
                    const url = '<?php echo $config['homeurl'].'/attachment/'; ?>' + data;
                    a.href = url;
                    a.download = data;
                    a.click();

                    setTimeout(() => {
                        $.ajax({
                            type: "DELETE",
                            url: "ajax.php",
                            data: {
                                page: 'include/ajax/resources.ajax',
                                deleteFile: 1,
                                filename: data,
                            },
                        });
                        $("#confirm_downloadDialog").dialog("close");
                    }, 3000);
                },
                error: function(data) {
                    console.error("Fatal error in AJAX call to interpreter order", data)
                }
            });
        }
    });
</script>