<?php
// Pandora FMS - https://pandorafms.com
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

/**
 * @package    Include
 * @subpackage php_to_js_values
 */

// Hidden div to forced title
html_print_div(['id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true]);

// ======= Store values to be retrieved from javascript code ============
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
set_js_value('homeurl', $config['homeurl']);
set_js_value('homedir', $config['homedir'].'/');
// Prevent double request message.
set_js_value('prepareDownloadTitle', __('Generating content'));
set_js_value('prepareDownloadMsg', __('Generating content, please wait'));

// ======================================================================
