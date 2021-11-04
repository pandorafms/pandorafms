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
$sql = 'SELECT link, name FROM tlink ORDER BY name';
$result = db_get_all_rows_sql($sql);
if ($result !== false) {
    echo '<div class="tit bg4">:: '.__('Links').' ::</div>';
    echo '<div class="menu"><ul>';
    foreach ($result as $link) {
        echo '<li class="links"><a href="'.$link['link'].'" target="_blank">'.$link['name'].'</a></li>';
    }

    echo '</ul></div>';
}
