<?php
/**
 * File repository
 *
 * @category   Files repository
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
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

// ACL Check.
check_login();
if (check_acl($config['id_user'], 0, 'PM') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access to Files repository'
    );
    include 'general/noaccess.php';
    return;
}

$tab = get_parameter('tab', '');

$url = 'index.php?sec=extensions&sec2=godmode/files_repo/files_repo';

// Header tabs.
$godmode['text'] = '<a href="'.$url.'&tab=configuration">';
$godmode['text'] .= html_print_image(
    'images/configuration@svg.svg',
    true,
    [
        'title' => __('Administration view'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$godmode['text'] .= '</a>';
$godmode['godmode'] = 1;

$operation['text'] = '<a href="'.$url.'">';
$operation['text'] .= html_print_image(
    'images/see-details@svg.svg',
    true,
    [
        'title' => __('Operation view'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$operation['text'] .= '</a>';
$operation['operation'] = 1;

$operation['active'] = 1;
$godmode['active'] = 0;
if ($tab === 'configuration') {
    $godmode['active'] = 1;
    $operation['active'] = 0;
}

$onheader = [
    'godmode'   => $godmode,
    'operation' => $operation,
];

// Header.
ui_print_standard_header(
    __('Extensions'),
    'images/extensions.png',
    false,
    '',
    true,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Tools'),
        ],
        [
            'link'  => '',
            'label' => __('Files repository'),
        ],
    ]
);

require_once __DIR__.'/../../include/functions_files_repository.php';

// Directory files_repo check.
if (files_repo_check_directory() === false) {
    return;
}

$server_content_length = 0;
if (isset($_SERVER['CONTENT_LENGTH'])) {
    $server_content_length = $_SERVER['CONTENT_LENGTH'];
}

// Check for an anoying error that causes the $_POST and $_FILES arrays.
// were empty if the file is larger than the post_max_size.
if (intval($server_content_length) > 0 && empty($_POST)) {
    ui_print_error_message(
        __('Problem uploading. Please check this PHP runtime variable values: <pre>  post_max_size (currently '.ini_get('post_max_size').')</pre>')
    );
}

// GET and POST parameters.
$file_id = (int) get_parameter('file_id');
$add_file = (bool) get_parameter('add_file');
$update_file = (bool) get_parameter('update_file');
$delete_file = (bool) get_parameter('delete');

// File add or update.
if ($add_file === true || ($update_file === true && $file_id > 0)) {
    $groups = get_parameter('groups', []);
    $public = (bool) get_parameter('public');
    $description = io_safe_output((string) get_parameter('description'));
    if (mb_strlen($description, 'UTF-8') > 200) {
        $description = mb_substr($description, 0, 200, 'UTF-8');
    }

    $description = io_safe_input($description);

    if ($add_file === true) {
        $result = files_repo_add_file('upfile', $description, $groups, $public);
    } else if ($update_file === true) {
        $result = files_repo_update_file($file_id, $description, $groups, $public);
        $file_id = 0;
    }

    if ($result['status'] == false) {
        ui_print_error_message($result['message']);
    } else {
        if ($add_file === true) {
            ui_print_success_message(__('Successfully created'));
        } else if ($update_file === true) {
            ui_print_success_message(__('Successfully updated'));
        }
    }
}

// File delete.
if ($delete_file === true && $file_id > 0) {
    $result = files_repo_delete_file($file_id);
    if ($result !== -1) {
        ui_print_result_message($result, __('Successfully deleted'), __('Could not be deleted'));
    }

    $file_id = 0;
}

$operation['active'] = 1;
if ($tab === 'configuration') {
    include_once __DIR__.'/files_repo_form.php';
} else {
    include_once __DIR__.'/files_repo_list.php';
}
