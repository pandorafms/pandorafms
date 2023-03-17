<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage folder_Functions
 */
require_once 'include/functions_graph.php';


global $config;function folder_get_folders()
{
    $folders = io_safe_output(
        db_get_all_rows_filter(
            'tcontainer',
            [
                'id_group' => array_keys(users_get_groups()),
                'order'    => 'parent, name',
            ]
        )
    );

    $ordered_folders = [];
    foreach ($folders as $folder) {
        $ordered_folders[$folder['id_container']] = $folder;
    }

    return $ordered_folders;
}


function folder_get_folders_tree_recursive($folders)
{
    $return = [];

    $tree = $folders;
    foreach ($folders as $key => $folder) {
        if ($folder['id_container'] == 0) {
            continue;
        }

        if (!in_array($folder['parent'], array_keys($folders))) {
            $folder['parent'] = 0;
        }

        $tree[$folder['parent']]['hash_branch'] = 1;
        $tree[$folder['parent']]['branch'][$key] = &$tree[$key];
    }

    if (isset($folders[0])) {
        $tree = [$tree[0]];
    } else {
        $tree = $tree[0]['branch'];
    }

    return $tree;

}


function folder_flatten_tree_folders($tree, $deep)
{
    foreach ($tree as $key => $folder) {
        $return[$key] = $folder;
        unset($return[$key]['branch']);
        $return[$key]['deep'] = $deep;

        if (!empty($folder['branch'])) {
            $return = ($return + folder_flatten_tree_folders($folder['branch'], ($deep + 1)));
        }
    }

    return $return;
}


function folder_get_select($folders_tree)
{
    $fields = [];

    foreach ($folders_tree as $folder_tree) {
        $folderName = ui_print_truncate_text($folder_tree['name'], GENERIC_SIZE_TEXT, false, true, false);

        $fields[$folder_tree['id_container']] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $folder_tree['deep']).$folderName;
    }

    return $fields;
}


function folder_togge_tree_folders($tree)
{
    $return = [];
    foreach ($tree as $key => $folder) {
        $folderName = ui_print_truncate_text($folder['name'], GENERIC_SIZE_TEXT, false, true, false);
        $table = '';

        if (!empty($folder['branch'])) {
            $togge = $table.folder_togge_tree_folders($folder['branch']);
            if ($folder['parent'] === '0') {
                $return[$key] .= "<div id='$folderName'>".ui_toggle_container($togge, $folderName, '', true, true, $folder['id_group'], $folder['id_container'], $folder['parent']).'</div>';
            } else {
                $return[$key] .= "<div id='$folderName' class='mrgn_lft_23px'>".ui_toggle_container($togge, $folderName, '', true, true, $folder['id_group'], $folder['id_container'], $folder['parent']).'</div>';
            }
        } else {
            if ($folder['parent'] === '0') {
                $return[$key] = "<div id='$folderName'>";
            } else {
                $return[$key] = "<div id='$folderName' class='mrgn_lft_23px'>";
            }

            $return[$key] .= ui_toggle_container($table, $folderName, '', true, true, $folder['id_group'], $folder['id_container'], $folder['parent']);
            $return[$key] .= '</div>';
        }
    }

    $retorno = implode('', $return);
    return $retorno;
}


function folder_table($graphs)
{
    global $config;
    $report_r = check_acl($config['id_user'], 0, 'RR');
    $report_w = check_acl($config['id_user'], 0, 'RW');
    $report_m = check_acl($config['id_user'], 0, 'RM');
    $access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox data';
    $table->align = [];
    $table->head = [];
    $table->head[0] = __('Graph name');
    $table->head[1] = __('Description');
    $table->head[2] = __('Number of Graphs');
    $table->head[3] = __('Group');
    $table->size[0] = '30%';
    $table->size[2] = '200px';
    $table->size[3] = '200px';
    $table->align[2] = 'left';
    $table->align[3] = 'left';
    if ($report_w || $report_m) {
        $table->align[4] = 'left';
        $table->head[4] = __('Op.').html_print_checkbox(
            'all_delete',
            0,
            false,
            true,
            false,
            'check_all_checkboxes();'
        );
        $table->size[4] = '90px';
    }

    $table->data = [];

    // $result_graphs = array_slice($graphs, $offset, $config['block_size']);
    foreach ($graphs as $graph) {
        $data = [];

        $data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.$graph['id_graph'].'">'.ui_print_truncate_text($graph['name'], 70).'</a>';

        $data[1] = ui_print_truncate_text($graph['description'], 70);

        $data[2] = $graph['graphs_count'];
        $data[3] = ui_print_group_icon($graph['id_group'], true);

        if (($report_w || $report_m) && users_can_manage_group_all($access)) {
            $data[4] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.$graph['id_graph'].'">'.html_print_image(
                'images/edit.svg',
                true,
                ['class' => 'invert_filter']
            ).'</a>';

            $data[4] .= '&nbsp;';

            $data[4] .= '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs&delete_graph=1&id='.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;">'.html_print_image('images/delete.svg', true, ['alt' => __('Delete'), 'title' => __('Delete'), 'class' => 'invert_filter']).'</a>'.html_print_checkbox_extended('delete_multiple[]', $graph['id_graph'], false, false, '', 'class="check_delete mrgn_lft_2px"', true);
        }

        array_push($table->data, $data);
    }

    return $table;
}


function folder_get_all_child_container($parent)
{
    $child_folders = db_get_all_rows_filter(
        'tcontainer',
        ['parent' => $parent]
    );

    return $child_folders;
}


/**
 * Get parent id
 */
function folder_get_parent_id($child)
{
    $child_folders = db_get_all_rows_filter(
        'tcontainer',
        ['id_container' => $child]
    );

    return $child_folders[0]['parent'];
}


/**
 * Get all si
 */
function folder_get_sibling($sibling)
{
    $parent_folders = db_get_all_rows_filter(
        'tcontainer',
        ['id_container' => $sibling]
    );

    $sibling_folders = db_get_all_rows_filter(
        'tcontainer',
        ['parent' => $parent_folders[0]['parent']]
    );

    return $sibling_folders;
}


function ui_toggle_container($code, $name, $title='', $hidden_default=true, $return=false, $group, $id_container, $parent=false)
{
    global $config;
    $report_r = check_acl($config['id_user'], 0, 'RR');
    $report_w = check_acl($config['id_user'], 0, 'RW');
    $report_m = check_acl($config['id_user'], 0, 'RM');
    $access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));

    // Generate unique Id
    $uniqid = uniqid('');

    // Options
    if ($hidden_default) {
        $style = 'display:none';
        $image_a = html_print_image('images/down.png', true, ['class' => 'invert_filter'], true);
        $image_b = html_print_image('images/go.png', true, ['class' => 'invert_filter'], true);
        $original = 'images/go.png';
    } else {
        $style = '';
        $image_a = html_print_image('images/down.png', true, ['class' => 'invert_filter'], true);
        $image_b = html_print_image('images/go.png', true, ['class' => 'invert_filter'], true);
        $original = 'images/down.png';
    }

    // Link to toggle
    $table = new stdClass();
    $table->id = 'container_table';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'dat';

    if (!$parent) {
        $table->class = 'default_container ';
    } else {
        $table->class = 'default_container_parent';
    }

    $table->style[0] = 'width: 30%';
    $table->style[1] = 'width: 30%';

    if (!$parent) {
        $table->style[0] = 'width: 30%';
        $table->style[1] = 'width: 30%';
        if ($id_container === '1') {
            $table->style[2] = 'padding-right: 34px';
        }

        $table->align[1] = 'center';
        $table->align[2] = 'center';
    } else {
        $id = folder_get_parent_id($id_container);
        $i = 0;
        while ($id !== '0') {
            $id = folder_get_parent_id($id);
            $i++;
        }

        $padding_group = (28 * $i);
        $padding_icon = (10 * $i);

        $table->style[0] = 'width: 30%';
        $table->style[1] = 'width: 30%;padding-right: '.$padding_group.'px';
        $table->style[2] = 'padding-right: '.$padding_icon.'px';
        $table->align[1] = 'center';
        $table->align[2] = 'center';
    }

    $table->data = [];

    $data = [];
    $data[0] = '<a href="javascript:" id="tgl_ctrl_'.$uniqid.'">'.html_print_image($original, true, ['title' => $title, 'id' => 'image_'.$uniqid, 'class' => 'invert_filter']).'&nbsp;&nbsp;<b>'.$name.'</b></a>';
    $data[1] = ui_print_group_icon($group, true);
    if ($report_r && $report_w) {
        $data[2] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&id='.$id_container.'">'.html_print_image('images/edit.svg', true, ['class' => 'invert_filter main_menu_icon']).'</a>';
    }

    if ($report_r && $report_w && $report_m) {
        if ($id_container !== '1') {
            $data[2] .= '&nbsp;&nbsp;&nbsp;&nbsp'.'<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container&delete_container=1&id='.$id_container.'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
              return false;">'.html_print_image('images/delete.svg', true, ['alt' => __('Delete'), 'title' => __('Delete'), 'class' => 'invert_filter main_menu_icon']).'</a>';
        }
    }

    $table->data[] = $data;
    $table->rowclass[] = '';

    $output .= html_print_table($table, true);

    // Code into a div
    $output .= "<div id='tgl_div_".$uniqid."' style='".$style."'>\n";
    $output .= html_print_input_hidden($uniqid, $id_container);
    $output .= $code;
    $output .= '</div>';

    // JQuery Toggle
    $output .= '<script type="text/javascript">'."\n";
    $output .= '	var hide_tgl_ctrl_'.$uniqid.' = '.(int) $hidden_default.";\n";
    $output .= '	/* <![CDATA[ */'."\n";
    $output .= "	$(document).ready (function () {\n";
    $output .= "		$('#tgl_ctrl_".$uniqid."').click(function() {\n";
    $output .= '			if (hide_tgl_ctrl_'.$uniqid.") {\n";
    $output .= '				hide_tgl_ctrl_'.$uniqid." = 0;\n";
    $output .= "				$('#tgl_div_".$uniqid."').toggle();\n";
    $output .= "				$('#image_".$uniqid."').attr({src: '".$image_a."'});\n";
    $output .= "			}\n";
    $output .= "			else {\n";
    $output .= '				hide_tgl_ctrl_'.$uniqid." = 1;\n";
    $output .= "				$('#tgl_div_".$uniqid."').toggle();\n";
    $output .= "				$('#image_".$uniqid."').attr({src: '".$image_b."'});\n";
    $output .= "			}\n";
    $output .= "		});\n";
    $output .= "	});\n";
    $output .= '/* ]]> */';
    $output .= '</script>';

    if (!$return) {
        echo $output;
    } else {
        return $output;
    }
}
