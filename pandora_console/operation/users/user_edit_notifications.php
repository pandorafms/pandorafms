<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

// Includes.
include_once ($config['homedir'] . '/include/functions_notifications.php');

// Load the header
require($config['homedir'] . "/operation/users/user_edit_header.php");

// User notification table. It is just a wrapper.
$table_content = new StdClass();
$table_content->data = array();
$table_content->width = '100%';
$table_content->id = 'user-notifications-wrapper';
$table_content->class = 'databox filters';
$table_content->size[0] = '33%';
$table_content->size[1] = '33%';
$table_content->size[2] = '33%';

// Print the header.
$table_content->data[] = array (
    '',
    __('Enable'),
    __('Also receive an email')
);

$sources = notifications_get_all_sources();
foreach ($sources as $source) {
    $table_content->data[] = array(
        $source['description'],
        notifications_print_user_switch($source, $id, 'enabled'),
        notifications_print_user_switch($source, $id, 'also_mail'),
    );
}
html_print_table($table_content);

?>