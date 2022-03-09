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

require_once 'include/functions_custom_graphs.php';

$searchGraphs = check_acl($config['id_user'], 0, 'RR');

if ($graphs === false || !$searchGraphs) {
    echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
} else {
    $table = new stdClass();
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'databox';

    $table->head = [];
    $table->head[0] = __('Graph name');
    $table->head[1] = __('Description');

    $table->headstyle = [];
    $table->headstyle[0] = 'text-align: left';
    $table->headstyle[1] = 'text-align: left';

    $table->data = [];
    foreach ($graphs as $graph) {
        array_push(
            $table->data,
            [
                "<a href='?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=".$graph['id_graph']."'>".$graph['name'].'</a>',
                $graph['description'],
            ]
        );
    }

    echo '<br />';
    ui_pagination($totalGraphs);
    html_print_table($table);
    unset($table);
    ui_pagination($totalGraphs);
}
