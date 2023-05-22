<?php
/**
 * Custom graph containers
 *
 * @category   Reporting
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

// Check user credentials.
check_login();

if (! check_acl($config['id_user'], 0, 'RR') || enterprise_installed() === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Graph container'
    );
    include 'general/noaccess.php';
    return;
}



$report_r = check_acl($config['id_user'], 0, 'RR');
$report_w = check_acl($config['id_user'], 0, 'RW');
$report_m = check_acl($config['id_user'], 0, 'RM');
$access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));

require_once 'include/functions_container.php';

$delete_container = get_parameter('delete_container', 0);

if ($delete_container) {
    $id_container = get_parameter('id', 0);
    $child = folder_get_all_child_container($id_container);

    if ($child) {
        foreach ($child as $key => $value) {
            $parent = ['parent' => 1];
            db_process_sql_update('tcontainer', $parent, ['id_container' => $value['id_container']]);
        }
    }

    db_process_sql_delete('tcontainer', ['id_container' => $id_container]);
}

$max_graph = $config['max_graph_container'];

$buttons['graph_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('Graph list'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
    $enterpriseEnable = true;
}

html_print_input_hidden('custom_graph', 1);
if ($enterpriseEnable) {
    $buttons = reporting_enterprise_add_template_graph_tabs($buttons);
}

$subsection = reporting_enterprise_add_graph_template_subsection('', $buttons);

$buttons['graph_container'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container">'.html_print_image(
        'images/graph-container.svg@svg',
        true,
        [
            'title' => __('Graph container'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

// Header.
ui_print_standard_header(
    __('Graph container'),
    '',
    false,
    '',
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom graphs'),
        ],
    ]
);

$container = folder_get_folders();

$tree = folder_get_folders_tree_recursive($container);
echo folder_togge_tree_folders($tree);
if ($report_r && $report_w) {
    $ActionButtons[] = '<form method="post" class="right" action="index.php?sec=reporting&sec2=godmode/reporting/create_container">';
    $ActionButtons[] = '<div class="action-buttons">';
    $ActionButtons[] = html_print_submit_button(
        __('Create container'),
        'create',
        false,
        [
            'class' => 'sub ok submitButton',
            'icon'  => 'next',
        ],
        true
    );
    $ActionButtons[] = '</div>';
    $ActionButtons[] = '</form>';

    html_print_action_buttons(implode('', $ActionButtons), ['type' => 'form_action']);
}
?>

<script type="text/javascript">
    function get_graphs_container (id_container,hash,time){
        $.ajax({
            async:false,
            type: "POST",
            url: "ajax.php",
            data: {"page" : "include/ajax/graph.ajax",
                "get_graphs_container" : 1,
                "id_container" : id_container,
                "hash" : hash,
                "time" : time,
                },
            success: function(data) {
                $("#div_"+hash).remove(); 
                $("#tgl_div_"+hash).prepend("<div id='div_"+hash+"' class='graph_conteiner_inside w99p pdd_l_36px ppd_t_7px'>"+data+"</div>");
                
                if($('div[class *= graph]').length == 0  && $('div[class *= bullet]').length == 0 && $('div[id *= gauge_]').length == 0){
                    $("#div_"+hash).remove();
                }
                
                $('div[class *= bullet]').css('margin-left','0');
                $('div[class = graph]').css('margin-left','0');
                $('div[id *= gauge_]').css('width','100%');

                $('select[id *= period_container_'+hash+']').change(function() {
                    var id = $(this).attr("id");
                    if(!/unit/.test(id)){
                        var time = $('select[id *= period_container_'+hash+']').val();
                        get_graphs_container(id_container,hash,time);
                    } 
                });
                
                $('input[id *= period_container_'+hash+']').keypress(function(e) {
                    if(e.which == 13) {
                        var time = $('input[id *= hidden-period_container_'+hash+']').val();
                        get_graphs_container(id_container,hash,time);
                    }
                });

                $("div[id^=period_container_] a").on('click', function(e){
                    if ($("div[id^=period_container_][id$=_default]").css('display') == 'none') {
                        $('#refresh_custom_time').show();
                        $('#refresh_custom_time').on('click', function(e){
                            var time = $('input[id *= hidden-period_container_'+hash+']').val();
                            get_graphs_container(id_container,hash,time);
                        });                
                    } 
                    else if ($("div[id^=period_container_][id$=_manual]").css('display') == 'none') {
                        $('#refresh_custom_time').hide();
                    }
                });

            }
        });
    }
    
    $(document).ready (function () {
        $('a[id *= tgl]').click(function(e) {
            var id = e.currentTarget.id;
            hash = id.replace("tgl_ctrl_","");
            var down = document.getElementById("image_"+hash).src;
            if (down.search("down") !== -1){
                var max_graph = "<?php echo $max_graph; ?>";
                var id_container = $("#hidden-"+hash).val();
                get_graphs_container(id_container,hash,'0');
            } else {
                $("#div_"+hash).remove(); 
            }
        });
    });
</script>