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


/*
 *
 * All this block has been retrieved from 'estado_generalagente.php' as
 * described in issue #5755.
 *
 */



/*
 *
 *
 * CLUSTER AGENT DETAILS.
 *
 */

// Prepare information for view.
$alive_animation = agents_get_status_animation(
    agents_get_interval_status($cluster->agent()->toArray(), false)
);


$agent_name = ui_print_agent_name(
    $cluster->agent()->id_agente(),
    true,
    500,
    'font-size: medium;font-weight:bold',
    true,
    '',
    '',
    false,
    false
);
$in_planned_downtime = db_get_sql(
    'SELECT executed FROM tplanned_downtime 
	INNER JOIN tplanned_downtime_agents 
	ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
	WHERE tplanned_downtime_agents.id_agent = '.$cluster->agent()->id_agente().' AND tplanned_downtime.executed = 1'
);

if ($cluster->agent()->disabled()) {
    if ($in_planned_downtime) {
        $agent_name = '<em>'.$agent_name.ui_print_help_tip(__('Disabled'), true);
    } else {
        $agent_name = '<em>'.$agent_name.'</em>'.ui_print_help_tip(__('Disabled'), true);
    }
} else if ($cluster->agent()->quiet()) {
    if ($in_planned_downtime) {
        $agent_name = "<em'>".$agent_name.'&nbsp;'.html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']);
    } else {
        $agent_name = "<em'>".$agent_name.'&nbsp;'.html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']).'</em>';
    }
} else {
    $agent_name = $agent_name;
}

if ($in_planned_downtime && !$cluster->agent()->disabled() && !$cluster->agent()->quiet()) {
    $agent_name .= '<em>&nbsp;'.ui_print_help_tip(
        __('Agent in scheduled downtime'),
        true,
        'images/minireloj-16.png'
    ).'</em>';
} else if (($in_planned_downtime && !$cluster->agent()->disabled())
    || ($in_planned_downtime && !$cluster->agent()->quiet())
) {
    $agent_name .= '&nbsp;'.ui_print_help_tip(
        __('Agent in scheduled downtime'),
        true,
        'images/clock.svg'
    ).'</em>';
}


$table_agent_header = '<div class="agent_details_agent_alias">';
$table_agent_header .= $agent_name;
$table_agent_header .= '</div>';
$table_agent_header .= '<div class="agent_details_agent_name mrgn_lft_10px">';
if (!$config['show_group_name']) {
    $table_agent_header .= ui_print_group_icon(
        $cluster->agent()->id_grupo(),
        true,
        'groups_small',
        'padding-right: 6px;'
    );
}

$table_agent_header .= '</div>';

$status_img = agents_detail_view_status_img(
    $cluster->agent()->critical_count(),
    $cluster->agent()->warning_count(),
    $cluster->agent()->unknown_count(),
    $cluster->agent()->total_count(),
    $cluster->agent()->notinit_count()
);

$table_agent_header .= '<div class="icono_right">'.$status_img.'</div>';
$table_agent_header .= '&nbsp;&nbsp;';
$table_agent_header .= '<a href="'.$model->url.'&op=force&id='.$cluster->id();
$table_agent_header .= '">'.html_print_image(
    'images/force@svg.svg',
    true,
    [
        'title' => __('Force cluster status calculation'),
        'alt'   => '',
        'class' => 'main_menu_icon invert_filter',

    ]
).'</a>';
// Fixed width non interactive charts.
$status_chart_width = 180;
$graph_width = 180;

$table_agent_graph = '<div id="status_pie" style="width: '.$status_chart_width.'px;">';
$table_agent_graph .= graph_agent_status(
    $cluster->agent()->id_agente(),
    $graph_width,
    $graph_width,
    true,
    false,
    false,
    true
);
$table_agent_graph .= '</div>';

$table_agent_os = '<p>'.ui_print_os_icon(
    $cluster->agent()->id_os(),
    false,
    true,
    true,
    false,
    false,
    false,
    ['title' => __('OS').': '.get_os_name($cluster->agent()->id_os())]
);
$table_agent_os .= (empty($cluster->agent()->os_version()) === true) ? get_os_name((int) $cluster->agent()->id_os()) : $cluster->agent()->os_version().'</p>';



$addresses = agents_get_addresses($cluster->agent()->id_agente());
$address = agents_get_address($cluster->agent()->id_agente());

foreach ($addresses as $k => $add) {
    if ($add == $address) {
        unset($addresses[$k]);
    }
}

if (empty($address) === false) {
    $table_agent_ip = '<p>'.html_print_image(
        'images/web@groups.svg',
        true,
        [
            'title' => __('IP address'),
            'class' => 'main_menu_icon invert_filter',
        ]
    );
    $table_agent_ip .= '<span class="align-top inline">';
    $table_agent_ip .= empty($address) ? '<em>'.__('N/A').'</em>' : $address;
    $table_agent_ip .= '</span></p>';
}

$table_agent_description = '<p>'.html_print_image(
    'images/logs@svg.svg',
    true,
    [
        'title' => __('Description'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$table_agent_description .= '<span class="align-top inline">';
$table_agent_description .= empty(
    $cluster->description()
) ? '<em>'.__('N/A').'</em>' : $cluster->description();
$table_agent_description .= '</span></p>';

$table_agent_count_modules = reporting_tiny_stats(
    $cluster->agent()->toArray(),
    true,
    'agent',
    // Useless.
    ':',
    true
);

$table_agent_version = '<p>'.html_print_image(
    'images/version.png',
    true,
    [
        'title' => __('Agent Version'),
        'class' => 'invert_filter',
    ]
);
$table_agent_version .= '<span class="align-top inline">';
$table_agent_version .= empty($cluster->agent()->agent_version()) ? '<i>'.__('Cluster agent').'</i>' : $cluster->agent()->agent_version();
$table_agent_version .= '</span></p>';

/*
 *
 *  MAP
 *
 */

$nodes = $cluster->getNodes();

$font_size = 20;
$width = '45%';
$height = '500';
$node_radius = 40;

// Generate map.
$map_manager = new NetworkMap(
    [
        'nodes'           => $nodes,
        'no_pandora_node' => 1,
        'pure'            => 1,
        'map_options'     => [
            'generation_method' => LAYOUT_SPRING1,
            'font_size'         => $font_size,
            'node_radius'       => $node_radius,
            'height'            => $height,
            'width'             => '100%',
            'tooltip'           => true,
            'size_image'        => 50,
            'z_dash'            => 0.5,
            'map_filter'        => [
                'node_sep'    => 7,
                'node_radius' => 50,
                'x_offs'      => 130,
                'y_offs'      => -70,
            ],
        ],
    ]
);


/*
 *
 * EVENTS 24h
 *
 */

$table_events = '<div class="white_table_graph" id="table_events" style="width:100%">';
$table_events .= '<div class="agent_details_header">';
$table_events .= '<b><span style="font-size: medium;font-weight:bold">';
$table_events .= __('Events (Last 24h)');
$table_events .= '</span></b>';
$table_events .= '</div>';
$table_events .= '<div class="white-table-graph-content">';
$table_events .= graph_graphic_agentevents(
    $cluster->agent()->id_agente(),
    95,
    70,
    SECONDS_1DAY,
    '',
    true,
    true,
    500
);
$table_events .= '</div>';
$table_events .= '</div>';

?>
<div id="agent_details_first_row" class="w100p cluster-agent-data">
    <div class="flex">
        <div class="box-flat agent_details_col agent_details_col_left" style="width:50%">
            <div class="agent_details_header">
                <?php echo $table_agent_header; ?>
            </div>
            <div class="agent_details_content pdd_l_50px">
                <div class="agent_details_graph">
                    <?php echo $table_agent_graph; ?>
                    <div class="agent_details_bullets">
                        <?php echo $table_agent_count_modules; ?>
                    </div>
                </div>
                <div class="agent_details_info">
                    <?php
                    echo $alive_animation;
                    echo $table_agent_os;
                    echo $table_agent_ip;
                    echo $table_agent_version;
                    echo $table_agent_description;
                    ?>
                </div>
            </div>
        </div>

        <div class="box-flat agent_details_col" style="width:50%">
            <?php echo $table_events; ?>
        </div>
    </div>
    <div class="box-flat agent_details_col agent_details_col_right">
        <div class="cluster-map">
            <?php $map_manager->printMap(); ?>
        </div>
    </div>
</div>

<div id='cluster-modules' class="w100p modules">
<?php
$id_agente = $cluster->agent()->id_agente();
require_once $config['homedir'].'/operation/agentes/estado_monitores.php';
?>
</div>


<?php
$buttons[] = html_print_submit_button(
    __('Reload'),
    'submit',
    false,
    [
        'class' => 'sub ok',
        'icon'  => 'next',
    ],
    true
);
echo '<form action="'.$model->url.'&op=view&id='.$cluster->id().'" method="POST">';
html_print_action_buttons(
    implode('', $buttons),
    ['type' => 'form_action']
);
echo '</form>';

// Print always go back button.
HTML::printForm($model->getGoBackForm(), false);
