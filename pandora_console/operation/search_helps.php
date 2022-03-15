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

if ($helps === false || !$searchHelps) {
    echo "<br><div class='nf'>".__('Zero results found.').sprintf(
        __('You can find more help in the <a class="underline" href="%s">wiki</a>'),
        'https://pandorafms.com/manual/start?do=search&id=start&q='.$config['search_keywords']
    )."</div>\n";
} else {
    $table = new stdClass();
    $table->width = '98%';
    $table->class = 'databox';

    $table->size = [];
    $table->size[0] = '95%';
    $table->size[1] = '5%';

    $table->head = [];
    $table->head[0] = __('Name');
    $table->head[1] = __('Matches');

    $table->align = [];
    $table->align[1] = 'center';

    $table->data = [];
    foreach ($helps as $iterator => $help) {
        if (is_numeric($iterator)) {
            $name = $help['id'];
        } else {
            $name = $iterator;
        }

        $table->data[] = [
            "<a href=\"javascript: open_help('".$help['id']."','');\">".$name.'</a>',
            $help['count'],
        ];
    }

    echo '<br />';
    html_print_table($table);
    unset($table);
}
