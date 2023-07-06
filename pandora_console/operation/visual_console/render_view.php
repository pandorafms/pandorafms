<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

$legacy = (bool) get_parameter('legacy', $config['legacy_vc']);
if ($legacy === false) {
    include_once $config['homedir'].'/operation/visual_console/view.php';
} else {
    include_once $config['homedir'].'/operation/visual_console/legacy_view.php';
}
