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
global $config;

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

$searchUsers = check_acl($config['id_user'], 0, 'UM');

if (!$users || !$searchUsers) {
    echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
} else {
    $table = new stdClass();
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'databox';

    $table->align = [];
    $table->align[4] = 'center';

    $table->headstyle = [];
    $table->headstyle[0] = 'text-align: left';
    $table->headstyle[1] = 'text-align: left';
    $table->headstyle[2] = 'text-align: left';
    $table->headstyle[3] = 'text-align: left';
    $table->headstyle[4] = 'text-align: center';
    $table->headstyle[5] = 'text-align: left';

    $table->head = [];
    $table->head[0] = __('User ID').' '.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=id_user&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectUserIDUp]).'</a>'.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=id_user&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectUserIDDown]).'</a>';
    $table->head[1] = __('Name').' '.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectNameUp]).'</a>'.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectNameDown]).'</a>';
    $table->head[2] = __('Email').' '.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=email&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectEmailUp]).'</a>'.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=email&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectEmailDown]).'</a>';
    $table->head[3] = __('Last contact').' '.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectLastContactUp]).'</a>'.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectLastContactDown]).'</a>';
    $table->head[4] = __('Profile').' '.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=profile&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectProfileUp]).'</a>'.'<a href="index.php?search_category=users&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=profile&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectProfileDown]).'</a>';
    $table->head[5] = __('Description');



    $table->data = [];

    foreach ($users as $user) {
        $userIDCell = "<a href='?sec=gusuarios&sec2=godmode/users/configure_user&id=".$user['id_user']."'>".$user['id_user'].'</a>';

        if ($user['is_admin']) {
            $profileCell = html_print_image(
                'images/user_suit.png',
                true,
                [
                    'alt'   => __('Admin'),
                    'title' => __('Administrator'),
                ]
            ).'&nbsp;';
        } else {
            $profileCell = html_print_image(
                'images/user_green.png',
                true,
                [
                    'alt'   => __('User'),
                    'title' => __('Standard User'),
                    'class' => 'invert_filter',
                ]
            ).'&nbsp;';
        }

        $result = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $user['id_user']);
        if ($result !== false) {
            foreach ($result as $row) {
                $text_tip .= profile_get_name($row['id_perfil']);
                $text_tip .= ' / ';
                $text_tip .= groups_get_name($row['id_grupo']);
                $text_tip .= '<br />';
            }
        } else {
            $text_tip .= __('The user doesn\'t have any assigned profile/group');
        }

        $profileCell .= ui_print_help_tip($text_tip, true);

        array_push(
            $table->data,
            [
                $userIDCell,
                $user['fullname'],
                "<a href='mailto:".$user['email']."'>".$user['email'].'</a>',
                ui_print_timestamp($user['last_connect'], true),
                $profileCell,
                $user['comments'],
            ]
        );
    }

    echo '<br />';
    // ui_pagination($totalUsers);
    html_print_table($table);
    unset($table);
    ui_pagination($totalUsers);
}
