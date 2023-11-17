<?php
/**
 * Cluster View: View
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Cluster View
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

ui_require_css_file('discovery');
ui_require_css_file('agent_view');
ui_require_css_file('cluster_view');

$html = new HTML();

// Begin.
// Prepare header and breadcrums.
$i = 0;
$bc = [];

$bc[] = [
    'link'     => $model->url,
    'label'    => __('Cluster list'),
    'selected' => false,
];

$bc[] = [
    'link'     => $model->url.'&op=view&id='.$cluster->id(),
    'label'    => __('Cluster details'),
    'selected' => true,
];


$html->prepareBreadcrum($bc);

// Header.
$main_page = '<a href="'.$model->url.'">';
$main_page .= html_print_image(
    'images/logs@svg.svg',
    true,
    [
        'title' => __('Cluster list'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$main_page .= '</a>';

$edit = '<a href="'.$model->url.'&op=update&id='.$cluster->id().'">';
$edit .= html_print_image(
    'images/configuration@svg.svg',
    true,
    [
        'title' => __('Edit this cluster'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$edit .= '</a>';

ui_print_page_header(
    __('Cluster details').' &raquo; '.$cluster->name(),
    '',
    false,
    // Help link.
    'cluster_view',
    true,
    // Buttons.
    [
        [
            'active' => false,
            'text'   => $main_page,
        ],
        [
            'active' => false,
            'text'   => $edit,
        ],
    ],
    false,
    '',
    GENERIC_SIZE_TEXT,
    '',
    $html->printHeader(true)
);


if (empty($error) === false) {
    echo $error;
}

if (empty($message) === false) {
    echo $message;
}

if ($critical === true) {
    // Print always go back button.
    HTML::printForm($model->getGoBackForm(), false);
    return;
}

$table_events = '<div class="agent_event_chart_cluster">';
$table_events .= '<div>';
$table_events .= '<b>';
$table_events .= __('Events (Last 24h)');
$table_events .= '</b>';
$table_events .= '</div>';
$table_events .= '<div class="white-table-graph-content">';
$table_events .= graph_graphic_agentevents(
    $cluster->agent()->id_agente(),
    95,
    50,
    SECONDS_1DAY,
    '',
    true,
    true,
    500
);
$table_events .= '</div>';
$table_events .= '</div>';


$agentCountModules = html_print_div(
    [
        'class'   => 'agent_details_bullets_cluster',
        'content' => reporting_tiny_stats(
            $counters_bullet,
            true,
            'modules',
            // Useless.
            ':',
            true
        ),
    ],
    true
);

$alive_animation = agents_get_starmap(0, 180, 30, $module_involved_ids);
$output = '<div id="agent_details_first_row" class="w100p cluster-agent-data">';

$output .= '<div class="flex">';
$output .= '<div class="box-flat agent_details_col">';
$output .= get_resume_agent_status_header($cluster->agent()->toArray());
$output .= '<div class="agent_details_content_cluster">';
$output .= '<div class="agent_details_graph">';
$output .= '<div>';
$output .= get_status_agent_chart_pie($cluster->agent()->id_agente(), 150, $counters_chart);
$output .= $agentCountModules;
$output .= '</div>';
$output .= '<div>';
$output .= '<div><b>'.__('Cluster Status').'</b></div>';
$output .= '<div>';
$output .= agents_detail_view_status_div(
    $cluster->agent()->critical_count(),
    $cluster->agent()->warning_count(),
    $cluster->agent()->unknown_count(),
    $cluster->agent()->total_count(),
    $cluster->agent()->notinit_count()
);
$output .= '</div>';
$output .= '</div>';
$output .= '</div>';
$output .= '<div class="agent_details_info">';
$output .= $alive_animation;
$output .= '<div>';
$output .= '<div><b>'.__('Cluster Mode').' : '.$cluster->getStringTypeName().'</b></div>';
$output .= '<div><b>'.$cluster->name().'</b></div>';
$output .= '</div>';
$output .= '</div>';
$output .= '</div>';

$output .= $table_events;

$output .= '</div>';

$output .= '<div class="box-flat agent_details_col">';
$output .= get_resume_agent_concat(
    $cluster->agent()->id_agente(),
    $allGroups,
    $cluster->agent()->toArray()
);
$output .= '</div>';

$output .= '</div>';
$output .= '</div>';
echo $output;

echo '<div id="cluster-modules" class="w100p modules">';
$id_agente = $cluster->agent()->id_agente();
$id_cluster = $cluster->id();
$agent = $cluster->agent()->toArray();
require_once $config['homedir'].'/operation/agentes/estado_monitores.php';
echo '</div>';

require_once $config['homedir'].'/operation/agentes/alerts_status.php';

// Check permissions to read events.
if (check_acl($config['id_user'], 0, 'ER')) {
    include_once $config['homedir'].'/operation/agentes/status_events.php';
}

$buttons = [];
$reload = '<form action="'.$model->url.'&op=view&id='.$cluster->id().'" method="POST">';
$reload .= html_print_submit_button(
    __('Reload'),
    'submit',
    false,
    [
        'class' => 'sub ok',
        'icon'  => 'next',
    ],
    true
);
$reload .= '</form>';

$buttons[] = $reload;

// Print always go back button.
$buttons[] = HTML::printForm($model->getGoBackForm(), true);

html_print_action_buttons(
    implode('', $buttons),
    ['type' => 'form_action']
);
