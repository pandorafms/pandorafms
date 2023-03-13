<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function extension_uploader_extensions()
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Group Management'
        );
        include 'general/noaccess.php';

        return;
    }

    // Header.
    ui_print_standard_header(
        __('Extensions'),
        'images/extensions.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Admin tools'),
            ],
            [
                'link'  => '',
                'label' => __('Extension manager'),
            ],
            [
                'link'  => '',
                'label' => __('Uploader extension'),
            ],
        ]
    );

    $upload = (bool) get_parameter('upload', 0);
    $upload_enteprise = (bool) get_parameter('upload_enterprise', 0);

    if ($upload) {
        $error = $_FILES['extension']['error'];

        if ($error == 0) {
            $zip = new ZipArchive;

            $tmpName = $_FILES['extension']['tmp_name'];

            if ($upload_enteprise) {
                $pathname = $config['homedir'].'/'.ENTERPRISE_DIR.'/'.EXTENSIONS_DIR.'/';
            } else {
                $pathname = $config['homedir'].'/'.EXTENSIONS_DIR.'/';
            }

            if ($zip->open($tmpName) === true) {
                $result = $zip->extractTo($pathname);
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }

        if ($result) {
            db_pandora_audit(
                AUDIT_LOG_EXTENSION_MANAGER,
                'Upload extension '.$_FILES['extension']['name']
            );
        }

        ui_print_result_message(
            $result,
            __('Success to upload extension'),
            __('Fail to upload extension')
        );
    }

    $table = new stdClass();

    $table->width = '100%';
    $table->class = 'databox filters filter-table-adv';
    $table->size[0] = '20%';
    $table->size[1] = '20%';
    $table->size[2] = '60%';
    $table->data = [];

    $table->data[0][0] = html_print_label_input_block(
        __('Upload extension').ui_print_help_tip(__('Upload the extension as a zip file.'), true),
        html_print_input_file(
            'extension',
            true,
            [
                'required' => true,
                'accept'   => '.zip',
            ]
        )
    );

    if (enterprise_installed()) {
        $table->data[0][1] = html_print_label_input_block(
            __('Upload enterprise extension'),
            html_print_checkbox(
                'upload_enterprise',
                1,
                false,
                true
            )
        );
    } else {
        $table->data[0][1] = '';
    }

    $table->data[0][2] = '';

    echo "<form method='post' enctype='multipart/form-data'>";
    html_print_table($table);
    html_print_input_hidden('upload', 1);
    html_print_action_buttons(
        html_print_submit_button(
            __('Upload'),
            'submit',
            false,
            ['icon' => 'wand'],
            true
        )
    );
    echo '</form>';
}


extensions_add_godmode_menu_option(__('Extension uploader'), 'PM', null, null, 'v1r1');
extensions_add_godmode_function('extension_uploader_extensions');
