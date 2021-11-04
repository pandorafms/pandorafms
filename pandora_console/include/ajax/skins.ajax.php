<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Login check
global $config;

$get_image_path = get_parameter('get_image_path', 0);

// skins image checks
if ($get_image_path) {
    $img_src = get_parameter('img_src');
    $only_src = get_parameter('only_src', 0);

    echo html_print_image($img_src, true, '', $only_src);
}
