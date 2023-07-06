<?php

// Pandora FMS- https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_profile.php';

// Clean the possible blanks introduced by the included files.
ob_clean();

$search_profile_name = (bool) get_parameter('search_profile_nanme');

if ($search_profile_name) {
    $profile_name = (string) get_parameter('profile_name');

    echo json_encode(profile_exist($profile_name));

    return;
}
