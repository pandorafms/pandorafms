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
// Function that includes all graph dependencies
function include_graphs_dependencies($home_url='', $serialize_ttl=1)
{
    global $config;
    global $ttl;
    global $homeurl;

    $ttl = $serialize_ttl;
    $homeurl = $home_url;
    include_once $homeurl.'include/functions_io.php';
    include_once $homeurl.'include/functions.php';
    include_once $homeurl.'include/functions_html.php';

    if (!defined('AJAX') && !get_parameter('static_graph', 0)) {
        include_once $homeurl.'include/graphs/functions_flot.php';
    }

    include_once $homeurl.'include/graphs/functions_gd.php';
    include_once $homeurl.'include/graphs/functions_utils.php';
}
