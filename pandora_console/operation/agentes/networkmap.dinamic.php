<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars.
global $config;

check_login();
if (defined('_id_') && _id_ != '_id_') {
    $id = _id_;
} else {
    define('_id_', '_id_');
    $id = get_parameter('id_networkmap', true);
}

$activeTab = get_parameter('activeTab', true);

if (enterprise_installed()) {
    if ($id_networkmap) {
        $id = $id_networkmap;
    }

    if ($tab) {
        $activeTab = $tab;
    }
}

if (defined('_activeTab_') && _activeTab_ != '_activeTab_') {
    $activeTab = _activeTab_;
    $tab = $activeTab;
}

// Networkmap id required.
if (!isset($id)) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access node graph builder'
    );
    include 'general/noaccess.php';
    exit;
}

// Get the group for ACL.
if (!isset($store_group)) {
    $store_group = db_get_value('id_group', 'tmap', 'id', $id);
    if ($store_group === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to accessnode graph builder'
        );
        include 'general/noaccess.php';
        exit;
    }
}

// ACL for the networkmap permission.
if (!isset($networkmap_read)) {
    $networkmap_read = check_acl($config['id_user'], $store_group, 'MR');
}

if (!isset($networkmap_write)) {
    $networkmap_write = check_acl($config['id_user'], $store_group, 'MW');
}

if (!isset($networkmap_manage)) {
    $networkmap_manage = check_acl($config['id_user'], $store_group, 'MM');
}

if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access node graph builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once 'include/functions_networkmap.php';

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$networkmap = db_get_row('tmap', 'id', $id);
$pure = (int) get_parameter('pure', 0);

// Main code.
if ($pure == 1) {
    $buttons['screen'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=network&amp;'.'sec2=operation/agentes/networkmap.dinamic&amp;'.'activeTab=radial_dynamic&amp;id_networkmap='.$id.'">'.html_print_image(
            'images/exit_fullscreen@svg.svg',
            true,
            [
                'title' => __('Normal screen'),
                'class' => 'invert_filter',
            ]
        ).'</a>',
    ];
} else {
    if (!$dash_mode) {
        $buttons['screen'] = [
            'active' => false,
            'text'   => '<a href="index.php?sec=network&amp;'.'sec2=operation/agentes/networkmap.dinamic&amp;'.'pure=1&amp;activeTab=radial_dynamic&amp;id_networkmap='.$id.'">'.html_print_image(
                'images/fullscreen@svg.svg',
                true,
                [
                    'title' => __('Full screen'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ];
        $buttons['list'] = [
            'active' => false,
            'text'   => '<a href="index.php?sec=networkmapconsole&amp;'.'sec2=operation/agentes/pandora_networkmap">'.html_print_image(
                'images/file-collection@svg.svg',
                true,
                [
                    'title' => __('List of networkmap'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ];
    }
}

if ($dashboard != 1) {
    // Header.
    ui_print_standard_header(
        io_safe_output($networkmap['name']),
        'images/bricks.png',
        false,
        'network_map_enterprise_list',
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Topology maps'),
            ],
            [
                'link'  => '',
                'label' => __('Network maps'),
            ],
        ]
    );
}

if (empty($width)) {
    $width = 600;
}

if (empty($height)) {
    $height = 650;
}

if ($activeTab == 'radial_dynamic') {
    include_once 'include/functions_graph.php';

    echo "<div class='wauto center'>";

    $filter = [];
    if ($networkmap['source'] == 0) {
        $filter['group'] = $networkmap['source_data'];
    }

    $map_filter = json_decode($networkmap['filter'], true);
    if (isset($map_filter['dont_show_subgroups']) && $map_filter['dont_show_subgroups']) {
        $filter['dont_show_subgroups'] = true;
    } else {
        $filter['dont_show_subgroups'] = false;
    }

    if (!empty($module_group)) {
        $filter['module_group'] = $module_group;
    }

    echo graph_monitor_wheel($width, $height, $filter, $strict_user);

    echo '</div>';
    return;
}

$networkmap = db_get_row('tmap', 'id', $id);

switch ($networkmap['generation_method']) {
    case 0:
        $layout = 'circular';
    break;

    case 1:
        $layout = 'flat';
    break;

    case 2:
        $layout = 'radial';
    break;

    case 3:
        $layout = 'neato';
    break;

    case 4:
        $layout = 'spring1';
    break;

    case 5:
        $layout = 'spring2';
    break;
}

// Set filter.
$filter = networkmap_get_filter($layout);

if (!isset($text_filter)) {
    $text_filter = '';
}

// Generate dot file.
$graph = networkmap_generate_hash(
    __('Pandora FMS'),
    $group,
    $simple,
    $font_size,
    $layout,
    $nooverlap,
    $zoom,
    $ranksep,
    $center,
    $regen,
    $pure,
    $id,
    $show_snmp_modules,
    true,
    true,
    $text_filter,
    $strict_user
);

networkmap_print_jsdata($graph);

$zoom_default = file($config['homedir'].'/images/zoom_default.svg');
?>
<div class="invisible">
    <?php
    echo implode("\n", $zoom_default);
    ?>
</div>
<?php
include_javascript_d3();

echo '<div id="dinamic_networkmap" style="overflow: hidden;"></div>';
?>
<style type="text/css">
    #tooltip_networkmap {
        text-align: left !important;
        padding: 5px;
        -webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);
        -moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);
        box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);
    }
    
    #tooltip_networkmap h3 {
        text-align: center !important;
        background-color: #B1B1B1;
        color: #FFFFFF;
    }
</style>

<style>

.node {
    stroke: #fff;
    stroke-width: 1.5px;
}

.select_node {
    stroke: #000;
    stroke-width: 1.5px;
}

.link {
    stroke: #999;
    stroke-opacity: 1;
    stroke-width: 1;
}

.select_link {
    stroke: #000;
    stroke-opacity: 1;
    stroke-width: 1;
}

</style>
<script>
var width = $("#dinamic_networkmap").width();
if ($("#main").height()) {
    var height = $("#main").height();
}
else {
    //Set the height in the pure view (fullscreen).
    
    var height = $(window).height() -
        $("#menu_tab_frame_view").height() -
        80; // 80 of margin
}


var color = d3.scale.category20();

var force = d3.layout.force()
    .charge(-60)
    .linkDistance(20)
    .friction(0.9)
    //.gravity(0.2)
    .size([width, height]);

var zoom_obj = d3.behavior.zoom();
zoom_obj.scaleExtent([0.3, 3]).on("zoom", zoom);

var svg = d3.select("#dinamic_networkmap").append("svg")
    .attr("id", "dinamic_networkmap_svg_" + "<?php echo $id; ?>")
    .attr("width", width)
    .attr("height", height)
    .attr("pointer-events", "all")
    .call(zoom_obj)
    .append('svg:g')

///Added default zoom buttom
d3.select("#dinamic_networkmap svg")
    .append("g")
    .attr("id", "zoom_control");

zoom_default = $("#zoom_default").clone();
$("#zoom_default").remove();

$("#zoom_control").append(zoom_default);

d3.select("#zoom_default")
    .on("click", click_zoom_default)
    .on("mouseover", over_zoom_default)
    .on("mouseout", out_zoom_default);

force
    .nodes(graph.nodes)
    .links(graph.links)
    .start();

var link = svg.selectAll(".link")
    .data(graph.links)
    .enter().append("line")
    .attr("id", function(d) {
        var id_text = 'link_'
            + d.source.id
            + "_" + d.target.id;
        
        return id_text;
    })
    .attr("class", function(d) {
        var class_text = 'link';
        
        class_text += " source_" + d.source.id;
        class_text += " target_" + d.target.id;
        
        return class_text;
    });

var node = svg.selectAll(".node")
    .data(graph.nodes)
    .enter().append("circle")
    .attr("id", function(d) { return "node_" + d.id})
    .attr("tooltip", function(d) { return d.tooltip})
    .attr("class", "node")
    .attr("r", 5)
    .style("fill", function(d) { return d.color; })
    .on("mouseover", over)
    .on("mouseout", out)
    .on("mousedown", mousedown)
    .on("mouseup", mouseup)
    //.on("click", click)
    .call(force.drag);

svg.style("opacity", 1e-6)
    .transition()
    .duration(1000)
    .style("opacity", 1);

force.on("tick", function() {
link.attr("x1", function(d) { return d.source.x; })
    .attr("y1", function(d) { return d.source.y; })
    .attr("x2", function(d) { return d.target.x; })
    .attr("y2", function(d) { return d.target.y; });

node.attr("cx", function(d) { return d.x; })
    .attr("cy", function(d) { return d.y; });
});

function click_zoom_default() {
    zoom([0, 0], 1);
}
function over_zoom_default() {
}
function out_zoom_default() {
}

function over(d) {
    $("#node_" + d.id).attr('class', 'select_node');
    $.each($(".source_" + d.id), function(i, line) {
        class_txt = $(line).attr('class');
        id_txt = $(line).attr('id');
        
        $("#" + id_txt).attr('class',
            class_txt.replace("link", "select_link"));
    });
    show_tooltip(d);
}

function out(d) {
    $("#node_" + d.id).attr('class', 'node');
    
    $.each($(".source_" + d.id), function(i, line) {
        class_txt = $(line).attr('class');
        id_txt = $(line).attr('id');
        
        $("#" + id_txt).attr('class',
            class_txt.replace("select_link", "link"));
    });
    
    hide_tooltip(d);
}

function click(d) {
    window.location = d.url;
}

var mouse_x = -1;
var mouse_y = -1;

function mousedown(d) {
    mouse_x = d3.event.clientX;
    mouse_y = d3.event.clientY;
}

function mouseup(d) {
    if ((d3.event.clientX == mouse_x) &&
        (d3.event.clientY == mouse_y)) {
        
        //The drag is diferent to click in the same position.
        click(d);
    }
}

function zoom(translate_param, scale_param) {
    var scale;
    var translate;
    
    if (typeof(translate_param) == "undefined") {
        scale = d3.event.scale;
        translate = d3.event.translate;
    }
    else {
        translate = translate_param;
        scale = scale_param;
        var zoom_obj = d3.behavior.zoom();
        zoom_obj.setScale(scale);
        zoom_obj.setTranslate(translate);
    }
    
    svg.attr("transform", "translate(" + translate + ")scale(" + scale + ")");
}

function create_tooltip(d, x, y) {
    if ($("#tooltip_networkmap").length == 0) {
        $("body")
            .append($("<div></div>")
            .attr('id', 'tooltip_networkmap')
            .html(d.tooltip_content));
    }
    else {
        $("#tooltip_networkmap").html(d.tooltip_content);
    }
    
    $("#tooltip_networkmap").attr('style', 'background: #fff;' + 
        'position: absolute;' + 
        'display: block;' + 
        'width: 275px;' + 
        'left: ' + x + 'px;' + 
        'top: ' + y + 'px;');
}

function create_loading_tooltip(d, x, y) {
    if ($("#tooltip_networkmap_loading").length == 0) {
        $("body")
            .append($("<div></div>")
            .attr('id', 'tooltip_networkmap_loading')
            .html(d.tooltip_content));
    }
    else {
        $("#tooltip_networkmap_loading").html(d.tooltip_content);
    }
    
    $("#tooltip_networkmap_loading").attr('style', 'background: #fff;' + 
        'position: absolute;' + 
        'display: block;' + 
        'left: ' + x + 'px;' + 
        'top: ' + y + 'px;');
}

function show_tooltip(d) {
    x = d3.event.clientX  100;
    y = d3.event.clientY  100;

    if (d.default_tooltip) {
        create_loading_tooltip(d, x, y);
        
        
        $.get(d.tooltip, function(data) {
            $("#tooltip_networkmap_loading").hide();
            
            create_tooltip(d, x, y);
            
            graph.nodes[d.id].tooltip_content = data;
            graph.nodes[d.id].default_tooltip = 0;
            $("#tooltip_networkmap").html(data);
        });
    }
    else {
        create_tooltip(d, x, y);
    }
}

function hide_tooltip(d) {
    $("#tooltip_networkmap").hide();
    $("#tooltip_networkmap_loading").hide();
}
</script>
