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

enterprise_include_once('include/functions_policies.php');
require_once $config['homedir'].'/enterprise/include/functions_groups.php';

$searchpolicies = check_acl($config['id_user'], 0, 'AW');

if (!$policies || !$searchpolicies) {
    echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
} else {
    $table = new stdClass();
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'databox';

    $table->align = [];
    $table->align[4] = 'center';

    $table->head = [];
    // $table->head[0] = __('ID').' '.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=id_policie&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectpolicieIDUp]).'</a>'.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=id_policie&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectpolicieIDDown]).'</a>';
    $table->head[0] = __('Name').' '.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectNameUp]).'</a>'.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectNameDown]).'</a>';
    $table->head[1] = __('Description').' '.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=description&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectDescriptionUp]).'</a>'.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=description&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectDescriptionDown]).'</a>';
    $table->head[2] = __('Id_group').' '.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectId_groupUp]).'</a>'.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectId_groupDown]).'</a>';
    $table->head[3] = __('Status').' '.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=status&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectStatusUp]).'</a>'.'<a href="index.php?search_category=policies&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=status&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectstatusDown]).'</a>';

    $table->data = [];

    foreach ($policies as $policie) {
        $policieIDCell = "<a href='?sec=gmodules&sec2=enterprise/godmode/policies/policies&id=".$policies['id']."'>".$policies['id'].'</a>';

        switch ($policie['status']) {
            case POLICY_UPDATED:
                $status = html_print_image(
                    'images/policies_ok.png',
                    true,
                    ['title' => __('Policy updated')]
                );
            break;

            case POLICY_PENDING_DATABASE:
                $status = html_print_image(
                    'images/policies_error_db.png',
                    true,
                    ['title' => __('Pending update policy only database')]
                );
            break;

            case POLICY_PENDING_ALL:
                $status = html_print_image(
                    'images/policies_error.png',
                    true,
                    ['title' => __('Pending update policy')]
                );
            break;
        }

        $url = $config['homeurl'].'/index.php?'.'sec=gmodules&'.'sec2=enterprise/godmode/policies/policies&id='.$policie['id'].'';

        array_push(
            $table->data,
            [
                // $policie['id'],
                '<a href= '.$url.'>'.$policie['name'].'',
                $policie['description'],
                ui_print_group_icon($policie['id_group'], true),
                $status,

            ]
        );
    }

    $totalPolicies = count($policies);
    echo '<br />';
    html_print_table($table);
    unset($table);
    ui_pagination($totalPolicies);
}
