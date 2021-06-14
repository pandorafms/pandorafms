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
function users_extension_main()
{
    users_extension_main_god(false);
}


function users_extension_main_god($god=true)
{
    global $config;

    if (isset($config['id_user'])) {
        if (!check_acl($config['id_user'], 0, 'UM')) {
            return;
        }
    }

    if ($god) {
        $image = 'images/gm_users.png';
    } else {
        $image = 'images/user.png';
    }

    // Header
    ui_print_page_header(__('Users connected'), $image, false, '', $god);

    // Get groups user has permission
    $group_um = users_get_groups_UM($config['id_user']);
    // Is admin or has group permissions all.
    $groups = implode(',', array_keys($group_um, 1));

    // Get user conected last 5 minutes.Show only those on which the user has permission.
    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                'SELECT tusuario.id_user, tusuario.last_connect
                FROM tusuario
                INNER JOIN tusuario_perfil ON tusuario_perfil.id_usuario = tusuario.id_user
                AND tusuario_perfil.id_grupo IN (%s)                 
                WHERE last_connect > (UNIX_TIMESTAMP(NOW()) - '.SECONDS_5MINUTES.')
                GROUP BY tusuario.id_user
				ORDER BY last_connect DESC',
                $groups
            );
        break;

        case 'postgresql':
            $sql = sprintf(
                "SELECT tusuario.id_user, tusuario.last_connect
            FROM tusuario
            INNER JOIN tusuario_perfil ON tusuario_perfil.id_usuario = tusuario.id_user
            AND tusuario_perfil.id_grupo IN (%s)
            WHERE last_connect > (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - ".SECONDS_5MINUTES.')
            GROUP BY tusuario.id_user
            ORDER BY last_connect DESC',
                $groups
            );
        break;

        case 'oracle':
            $sql = sprintf(
                "SELECT tusuario.id_user, tusuario.last_connect
                FROM tusuario
                INNER JOIN tusuario_perfil ON tusuario_perfil.id_usuario = tusuario.id_user
                AND tusuario_perfil.id_grupo IN (%s)
                WHERE last_connect > (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (".SECONDS_1DAY.')) - '.SECONDS_5MINUTES.')
                GROUP BY tusuario.id_user
				ORDER BY last_connect DESC',
                $groups
            );
        break;
    }

    $check_profile = db_get_row('tusuario_perfil', 'id_usuario', $config['id_user'], 'id_up');
    if ($check_profile === false) {
        return ui_print_error_message(
            __('This user does not have any associated profile'),
            '',
            false
        );
    }

    $rows = db_get_all_rows_sql($sql);
    if (empty($rows)) {
        $rows = [];
        echo "<div class='nf'>".__('No other users connected').'</div>';
    } else {
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = '100%';
        $table->class = 'info_table';
        $table->size = [];
        $table->data = [];
        $table->head = [];

        $table->head[0] = __('User');
        $table->head[1] = __('IP');
        $table->head[2] = __('Last login');
        $table->head[3] = __('Last contact');

        $rowPair = true;
        $iterator = 0;

        // Get data
        foreach ($rows as $row) {
            // Get data of user's last login.
            switch ($config['dbtype']) {
                case 'mysql':
                case 'postgresql':
                    $last_login_data = db_get_row_sql(
                        sprintf(
                            "SELECT ip_origen, utimestamp 
						FROM tsesion 
						WHERE id_usuario = '%s'
						AND descripcion = '".io_safe_input('Logged in')."' 
						ORDER BY fecha DESC",
                            $row['id_user']
                        )
                    );
                break;

                case 'oracle':
                    $last_login_data = db_get_row_sql(
                        sprintf(
                            "SELECT ip_origen, utimestamp 
						FROM tsesion
						WHERE id_usuario = '%s'
						AND to_char(descripcion) = '".io_safe_input('Logged in')."' 
						ORDER BY fecha DESC",
                            $row['id_user']
                        )
                    );
                break;
            }

            if ($rowPair) {
                $table->rowclass[$iterator] = 'rowPair';
            } else {
                $table->rowclass[$iterator] = 'rowOdd';
            }

            $rowPair = !$rowPair;
            $iterator++;

            $data = [];
            $data[0] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_user&amp;id='.$row['id_user'].'">'.$row['id_user'].'</a>';
            $data[1] = $last_login_data['ip_origin'];
            $data[2] = date($config['date_format'], $last_login_data['utimestamp']);
            $data[3] = date($config['date_format'], $row['last_connect']);
            array_push($table->data, $data);
        }

        html_print_table($table);
    }
}


extensions_add_operation_menu_option(__('Users connected'), 'workspace', 'users/icon.png', 'v1r1', '', 'UM');

extensions_add_godmode_function('users_extension_main_god');
extensions_add_main_function('users_extension_main');
