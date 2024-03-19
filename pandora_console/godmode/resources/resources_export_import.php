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
// Instance of the prd class.
$prd = new Prd();

$msg = '';
if (isset($_FILES['resource_import']) === true) {
    if (empty($_FILES['resource_import']['tmp_name']) === false) {
        $data = parse_ini_file($_FILES['resource_import']['tmp_name'], true);
        if ($data !== false) {
            if (isset($data['prd_data']['name']) === true
                && isset($data['prd_data']['type']) === true
            ) {
                $name = $data['prd_data']['name'];
                $type = $data['prd_data']['type'];
            }

            $msg = $prd->importPrd($data);
        } else {
            $msg = [
                'status' => false,
                'items'  => [],
                'errors' => ['Unexpected error: Unable to parse PRD file.'],
            ];
        }
    } else {
        $msg = [
            'status' => false,
            'items'  => [],
            'errors' => ['No files have selected'],
        ];
    }
}

$msg = json_encode($msg);

echo '<div class="div-import-export">';
// Import section.
$label_import = html_print_label(
    __('Import resources to').' '.get_product_name(),
    'label_import',
    true,
    ['style' => 'font-size: 13px; line-height: 16px'],
);

$div_label_import = html_print_div(
    [
        'style'   => 'padding-bottom: 20px;',
        'content' => $label_import,
    ],
    true
);

$input_file = '<input class="input-file-style" style="padding-top: 1px; width: 100%;" type="file" value name="resource_import" id="file-resource_import" >';

$div_input_file = html_print_div(
    [
        'style'   => 'padding-top: 20px;display: flex; justify-content: left;width:100%; height: 60px;',
        'content' => $input_file,
    ],
    true
);

$button_import = html_print_submit_button(
    __('Import'),
    'upload',
    false,
    [
        'icon'     => 'import',
        'class'    => 'disabled',
        'disabled' => '',
    ],
    true
);

$div_button_import = html_print_div(
    [
        'style'   => 'padding-bottom: 20px',
        'content' => $button_import,
    ],
    true
);

$div_import = html_print_div(
    [
        'style'   => 'width: 80%',
        'content' => $div_label_import.$div_input_file.$div_button_import,
    ],
    true
);

$img_import = html_print_image(
    'images/import_to.svg',
    true,
    [
        'border' => '0',
        'width'  => '100%',
    ]
);

$div_img_import = html_print_div(
    [
        'style'   => 'margin-left: 40px; margin-right: 20px',
        'content' => $img_import,
    ],
    true
);

echo '<form class="form-import" name="submit_import" method="POST" enctype="multipart/form-data">';
echo html_print_div(
    [
        'class'   => 'div-import',
        'content' => $div_import.$div_img_import,
    ],
    true
);
echo '</form>';


// Export section.
$label_export = html_print_label(
    __('Export resources from').' '.get_product_name(),
    'label_export',
    true,
    ['style' => 'font-size: 13px; line-height: 16px'],
);

$div_label_export = html_print_div(
    [
        'style'   => 'padding-bottom: 20px',
        'content' => $label_export,
    ],
    true
);

$export_type = $prd->getTypesPrd();

$select_export_type = html_print_label_input_block(
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
        'w90p'
    ),
    ['div_style' => 'display: flex; flex-direction: column; width: 50%'],
);

$div_select_export = html_print_div(
    [
        'id'      => 'div_select_export',
        'style'   => 'padding-bottom: 20px;display: flex; flex-direction: row; height: 60px',
        'content' => $select_export_type,
    ],
    true
);

$button_export = html_print_button(
    __('Export'),
    'export_button',
    false,
    '',
    [
        'class'    => 'flex_justify disabled',
        'icon'     => 'export',
        'disabled' => '',
    ],
    true
);

$div_button_export = html_print_div(
    [
        'style'   => '',
        'content' => $button_export,
    ],
    true
);

$div_export = html_print_div(
    [
        'style'   => 'padding-bottom: 20px; width: 80%',
        'content' => $div_label_export.$div_select_export.$div_button_export,
    ],
    true
);

$img_export = html_print_image(
    'images/export_to.svg',
    true,
    [
        'border' => '0',
        'width'  => '100%',
    ]
);

$div_img_export = html_print_div(
    [
        'style'   => 'margin-left: 40px; margin-right: 20px',
        'content' => $img_export,
    ],
    true
);

echo html_print_div(
    [
        'class'   => 'div-export',
        'content' => $div_export.$div_img_export,
    ],
    true
);

echo '</div>';

?>
<script type="text/javascript">
    let msg = <?php echo $msg; ?>;
    if (typeof msg === 'object' && Object.keys(msg).length > 0) {
        let title = "";
        let message = "";
        if (msg.status === true) {
            title = "<?php echo __('Importation successfully completed'); ?>";
            message = "<?php echo __('PRD import successfull:'); ?>";
            const name = "<?php echo ($name ?? ''); ?>";
            const type = "<?php echo ($type ?? ''); ?>";
            message += ` ${type} - ${name}`;
        } else {
            title = "<?php echo __('Import failure'); ?>";
            Object.entries(msg.errors).forEach(([key, value]) => {
                message += value + "<br>";
            });
        }

        if (typeof msg.info === 'object' && Object.keys(msg.info).length > 0) {
            message += "<br><br>";
            Object.entries(msg.info).forEach(([key, value]) => {
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

    $('input[type="file"]').change(function() {
        console.log($(this).val());
        if ($(this).val() === '') {
            $("#button-upload").addClass("disabled");
            $('#button-upload').prop('disabled', true);
        } else {
            $("#button-upload").removeClass("disabled");
            $('#button-upload').prop('disabled', false);
        }
    });

    $("#export_type").change(function(e) {
        if ($(this).val() === '0') {
            $("#resource_type").remove();
            $("#button-export_button").addClass("disabled");
            $('#button-export_button').prop('disabled', true);
        } else {
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
                    $("#resource_type").remove();
                    $("#div_select_export").append(`${data}`);
                    $('#select_value').select2();
                    $("#button-export_button").removeClass("disabled");
                    $('#button-export_button').prop('disabled', false);
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

            const filename = '<?php echo uniqid().'.prd'; ?>';

            $.ajax({
                type: "GET",
                url: "ajax.php",
                dataType: 'json',
                data: {
                    page: 'include/ajax/resources.ajax',
                    exportPrd: 1,
                    type: $("#export_type").val(),
                    value: value,
                    name: $("#select_value option:selected").text(),
                    filename: filename
                },
                success: function(data) {
                    if (data.error === -1 || data.error === -2) {
                        console.error("Failed to create file");
                        $("#confirm_downloadDialog").dialog("close");
                    } else {
                        let a = document.createElement('a');
                        const url = '<?php echo $config['homeurl'].'/attachment/'; ?>' + filename;
                        a.href = url;
                        a.download = data.name_download;
                        a.click();

                        setTimeout(() => {
                            $.ajax({
                                type: "DELETE",
                                url: "ajax.php",
                                data: {
                                    page: 'include/ajax/resources.ajax',
                                    deleteFile: 1,
                                    filename: filename,
                                },
                            });
                            $("#confirm_downloadDialog").dialog("close");
                        }, 3000);
                    }
                },
                error: function(data) {
                    console.error("Fatal error in AJAX call to interpreter order", data);
                    $.ajax({
                        type: "DELETE",
                        url: "ajax.php",
                        data: {
                            page: 'include/ajax/resources.ajax',
                            deleteFile: 1,
                            filename: filename,
                        },
                    });
                    $("#confirm_downloadDialog").dialog("close");
                }
            });
        }
    });
</script>