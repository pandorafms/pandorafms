<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function files_repo_check_directory($print_messages=false)
{
    global $config;

    $attachment_path = io_safe_output($config['attachment_store']);
    $files_repo_path = $attachment_path.'/files_repo';

    $result = false;
    $messages = '';

    // attachment/ check
    if (!is_writable($attachment_path)) {
        $messages .= ui_print_error_message(
            [
                'message'     => __('Attachment directory is not writable by HTTP Server').'</h3>'.'<p>'.sprinf(__('Please check that the web server has write rights on the %s directory'), $attachment_path),
                'no_close'    => true,
                'force_style' => 'color: #000000 !important',
            ],
            '',
            true
        );
    } else {
        // attachment/agent_packages/ check
        if (!file_exists($files_repo_path) || !is_writable($files_repo_path)) {
            // Create the directoty if not exist
            if (!file_exists($files_repo_path)) {
                mkdir($files_repo_path);
            }

            if (!is_writable($files_repo_path)) {
                $messages .= ui_print_error_message(
                    [
                        'message'     => __('Attachment directory is not writable by HTTP Server').'</h3>'.'<p>'.sprintf(__('Please check that the web server has write rights on the %s directory'), $attachment_path),
                        'no_close'    => true,
                        'force_style' => 'color: #000000 !important',
                    ],
                    '',
                    true
                );
            } else {
                $result = true;
            }
        } else {
            $result = true;
        }
    }

    if ($print_messages) {
        echo $messages;
    }

    return $result;
}


function files_repo_check_file_acl($file_id, $user_id=false, $file_groups=false, $user_groups=false)
{
    global $config;

    $result = false;
    if (!$user_id) {
        $user_id = $config['id_user'];
    }

    if (is_user_admin($user_id)) {
        return true;
    }

    if (!$file_groups) {
        $file_groups = files_repo_get_file_groups($file_id);
        if (empty($file_groups)) {
            $file_groups = [];
        }
    }

    if (in_array(0, $file_groups)) {
        return true;
    }

    if (!$user_groups) {
        $user_groups = users_get_groups($user_id, false, true);
        if (empty($user_groups)) {
            $user_groups = [];
        }
    }

    foreach ($file_groups as $group_id) {
        // $user_groups has the id in the array keys
        if (in_array($group_id, $user_groups)) {
            $result = true;
            break;
        }
    }

    return $result;
}


function files_repo_get_file_groups($file_id)
{
    $groups = [];
    $filter = ['id_file' => $file_id];
    $result = db_get_all_rows_filter('tfiles_repo_group', $filter, 'id_group');

    if (!empty($result)) {
        foreach ($result as $key => $value) {
            $groups[] = $value['id_group'];
        }
    }

    return $groups;
}


function files_repo_get_user_groups($user_id)
{
    $groups = [];
    $filter = ['id_usuario' => $user_id];
    $result = db_get_all_rows_filter('tusuario_perfil', $filter, 'id_grupo');

    if (!empty($result)) {
        foreach ($result as $key => $value) {
            $groups[] = $value['id_grupo'];
        }
    }

    return $groups;
}


function files_repo_get_files($filter=false, $count=false)
{
    global $config;

    // Don't use the realpath for the download links!
    $files_repo_path = io_safe_output($config['attachment_store']).'/files_repo';

    $sql = 'SELECT *
		FROM tfiles_repo
		'.db_format_array_where_clause_sql($filter, 'AND', 'WHERE');
    $files = db_get_all_rows_sql($sql);

    if ($files === false) {
        $files = [];
    }

    $user_groups = files_repo_get_user_groups($config['id_user']);

    $files_data = [];
    foreach ($files as $file) {
        $file_groups = files_repo_get_file_groups($file['id']);
        $permission = files_repo_check_file_acl($file['id'], $config['id_user'], $file_groups, $user_groups);
        if (!$permission) {
            continue;
        }

        $data = [];
        $data['name'] = $file['name'];
        $data['description'] = $file['description'];
        $data['location'] = $files_repo_path.'/'.$file['id'].'_'.$data['name'];
        // Size in bytes
        $data['size'] = filesize($data['location']);
        // Last modification time in unix timestamp
        $data['mtime'] = filemtime($data['location']);
        $data['groups'] = $file_groups;
        $data['hash'] = $file['hash'];
        $files_data[$file['id']] = $data;
    }

    if ($count) {
        $files_data = count($files_data);
    }

    return $files_data;
}


function files_repo_add_file($file_input_name='upfile', $description='', $groups=[], $public=false)
{
    global $config;

    $attachment_path = io_safe_output($config['attachment_store']);
    $files_repo_path = $attachment_path.'/files_repo';

    $result = [];
    $result['status'] = false;
    $result['message'] = '';

    $upload_status = get_file_upload_status($file_input_name);
    $upload_result = translate_file_upload_status($upload_status);

    if ($upload_result === true) {
        $filename = $_FILES[$file_input_name]['name'];
        // Replace conflictive characters
        $filename = str_replace([' ', '=', '?', '&'], '_', $filename);
        $filename = filter_var($filename, FILTER_SANITIZE_URL);
        // The filename should not be larger than 200 characters
        if (mb_strlen($filename, 'UTF-8') > 200) {
            $filename = mb_substr($filename, 0, 200, 'UTF-8');
        }

        $hash = '';
        if ($public) {
            $hash = md5(time().$config['dbpass']);
            $hash = mb_substr($hash, 0, 8, 'UTF-8');
        }

        $values = [
            'name'        => $filename,
            'description' => $description,
            'hash'        => $hash,
        ];
        $file_id = db_process_sql_insert('tfiles_repo', $values);

        if ($file_id) {
            $file_tmp = $_FILES[$file_input_name]['tmp_name'];
            $destination = $files_repo_path.'/'.$file_id.'_'.$filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                if (is_array($groups) && !empty($groups)) {
                    db_process_sql_delete('tfiles_repo_group', ['id_file' => $file_id]);
                    foreach ($groups as $group) {
                        $values = [
                            'id_file'  => $file_id,
                            'id_group' => $group,
                        ];
                        db_process_sql_insert('tfiles_repo_group', $values);
                    }
                }

                $result['status'] = true;
            } else {
                db_process_sql_delete('tfiles_repo', ['id' => $file_id]);
                unlink($file_tmp);
                $result['message'] = __('The file could not be copied');
            }
        } else {
            $result['message'] = __('There was an error creating the file');
        }
    } else {
        $result['message'] = $upload_result;
    }

    return $result;
}


function files_repo_update_file($file_id, $description='', $groups=[], $public=false)
{
    global $config;

    $result = [];
    $result['status'] = false;
    $result['message'] = '';

    $hash = '';
    if ($public) {
        $hash = md5(time().$config['dbpass']);
        $hash = mb_substr($hash, 0, 8, 'UTF-8');
    }

    $values = [
        'description' => $description,
        'hash'        => $hash,
    ];
    $filter = ['id' => $file_id];
    $res = db_process_sql_update('tfiles_repo', $values, $filter);
    if ($res !== false) {
        if (is_array($groups) && !empty($groups)) {
            db_process_sql_delete('tfiles_repo_group', ['id_file' => $file_id]);
            foreach ($groups as $group) {
                $values = [
                    'id_file'  => $file_id,
                    'id_group' => $group,
                ];
                db_process_sql_insert('tfiles_repo_group', $values);
            }
        }

        $result['status'] = true;
    } else {
        $result['message'] = __('There was an error updating the file');
    }

    return $result;
}


function files_repo_delete_file($file_id)
{
    global $config;

    $result = -1;

    $filename = db_get_value('name', 'tfiles_repo', 'id', $file_id);

    if ($filename) {
        $attachment_path = io_safe_output($config['attachment_store']);
        $files_repo_path = $attachment_path.'/files_repo';
        $location = $files_repo_path.'/'.$file_id.'_'.$filename;

        if (file_exists($location)) {
            $result = false;
            if (unlink($location)) {
                $result = (bool) db_process_sql_delete('tfiles_repo', ['id' => $file_id]);
            }
        }
    }

    return $result;
}
