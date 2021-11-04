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
 * @subpackage Event Responses
 */


/**
 * Get all event responses with all values that user can access
 *
 * @return array With all table values
 */
function event_responses_get_responses()
{
    global $config;
    $filter = [];

    // Apply a filter if user cannot see all groups
    if (!users_can_manage_group_all()) {
        $id_groups = array_keys(users_get_groups(false, 'PM'));
        $filter = ['id_group' => $id_groups];
    }

    return db_get_all_rows_filter('tevent_response', $filter);
}


/**
 * Validate the responses data to store in database
 *
 * @param array (by reference) Array with values to validate and modify
 */
function event_responses_validate_data(&$values)
{
    if ($values['type'] != 'command' || !enterprise_installed()) {
        $values['server_to_exec'] = 0;
    }

    if ($values['new_window'] == 1) {
        $values['modal_width'] = 0;
        $values['modal_height'] = 0;
    }
}


/**
 * Create an event response
 *
 * @param array With all event response data
 *
 * @return True if successful insertion
 */
function event_responses_create_response($values)
{
    event_responses_validate_data($values);
    return db_process_sql_insert('tevent_response', $values);
}


/**
 * Update an event response
 *
 * @param array With all event response data
 *
 * @return True if successful insertion
 */
function event_responses_update_response($response_id, $values)
{
    event_responses_validate_data($values);
    return db_process_sql_update(
        'tevent_response',
        $values,
        ['id' => $response_id]
    );
}
