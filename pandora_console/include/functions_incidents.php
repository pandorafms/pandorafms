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
 * @subpackage Incidents
 */


/**
 * Gets all the possible priorities for incidents in an array
 *
 * @return array The several priorities with their values
 */
function incidents_get_priorities($priority=false)
{
    $fields = [];
    $fields[0] = __('Informative');
    $fields[1] = __('Low');
    $fields[2] = __('Medium');
    $fields[3] = __('Serious');
    $fields[4] = __('Very serious');
    $fields[10] = __('Maintenance');

    if ($priority === false) {
        return $fields;
    } else {
        return $fields[$priority];
    }
}


/**
 * Prints the image tag for passed status
 *
 * @param integer $id_status Which status to return the image to
 *
 * @return string The string with the image tag
 */
function incidents_print_priority_img($id_priority, $return=false)
{
    switch ($id_priority) {
        case 0:
            $img = html_print_image('images/dot_green.png', true, ['title' => __('Informative')]).html_print_image('images/dot_green.png', true, ['title' => __('Informative')]).html_print_image('images/dot_yellow.png', true, ['title' => __('Informative')]);
        break;

        case 1:
            $img = html_print_image('images/dot_green.png', true, ['title' => __('Low')]).html_print_image('images/dot_yellow.png', true, ['title' => __('Low')]).html_print_image('images/dot_yellow.png', true, ['title' => __('Low')]);
        break;

        case 2:
            $img = html_print_image('images/dot_yellow.png', true, ['title' => __('Medium')]).html_print_image('images/dot_yellow.png', true, ['title' => __('Medium')]).html_print_image('images/dot_red.png', true, ['title' => __('Medium')]);
        break;

        case 3:
            $img = html_print_image('images/dot_yellow.png', true, ['title' => __('Serious')]).html_print_image('images/dot_red.png', true, ['title' => __('Serious')]).html_print_image('images/dot_red.png', true, ['title' => __('Serious')]);
        break;

        case 4:
            $img = html_print_image('images/dot_red.png', true, ['title' => __('Very serious')]).html_print_image('images/dot_red.png', true, ['title' => __('Very serious')]).html_print_image('images/dot_red.png', true, ['title' => __('Very serious')]);
        break;

        case 10:
            $img = html_print_image('images/dot_green.png', true, ['title' => __('Maintenance')]).html_print_image('images/dot_green.png', true, ['title' => __('Maintenance')]).html_print_image('images/dot_green.png', true, ['title' => __('Maintenance')]);
        break;
    }

    if ($return === false) {
        echo $img;
    }

    return $img;
}


/**
 * Gets all the possible status for incidents in an array
 *
 * @return array The several status with their values
 */
function incidents_get_status()
{
    $fields = [];
    $fields[0] = __('Active incidents');
    $fields[1] = __('Active incidents, with comments');
    $fields[2] = __('Rejected incidents');
    $fields[3] = __('Expired incidents');
    $fields[13] = __('Closed incidents');

    return $fields;
}


/**
 * Prints the image tag for passed status
 *
 * @param integer $id_status: Which status to return the image to
 *
 * @return string The string with the image tag
 */
function incidents_print_status_img($id_status, $return=false)
{
    switch ($id_status) {
        case 0:
            $img = html_print_image('images/dot_red.png', true, ['title' => __('Active incidents')]);
        break;

        case 1:
            $img = html_print_image('images/dot_yellow.png', true, ['title' => __('Active incidents, with comments')]);
        break;

        case 2:
            $img = html_print_image('images/dot_blue.png', true, ['title' => __('Rejected incidents')]);
        break;

        case 3:
            $img = html_print_image('images/dot_green.png', true, ['title' => __('Expired incidents')]);
        break;

        case 13:
            $img = html_print_image('images/dot_white.png', true, ['title' => __('Closed incidents')]);
        break;
    }

    if ($return === false) {
        echo $img;
    }

    return $img;
}


/**
 * Updates the last user (either by adding an attachment, note or the incident itself)
 * Named after the UNIX touch utility
 *
 * @param integer $id_incident: A single incident or an array of incidents
 *
 * @return boolean True if it was done, false if it wasn't
 */
function incidents_process_touch($id_incident)
{
    global $config;

    $id_incident = (array) safe_int($id_incident, 1);
    // Make sure we have all positive int's
    if (empty($id_incident)) {
        return false;
    }

    if (empty($id_incident)) {
        return false;
    }

    return db_process_sql_update('tincidencia', ['id_lastupdate' => $config['id_user']], ['id_incidencia' => $id_incident]);
}


/**
 * Updates the owner (named after the UNIX utility chown)
 *
 * @param integer $id_incident: A single incident or an array of incidents
 *
 * @return boolean True if it was done, false if it wasn't
 */
function incidents_process_chown($id_incident, $owner=false)
{
    if ($owner === false) {
        global $config;
        $owner = $config['id_user'];
    }

    $id_incident = (array) safe_int($id_incident, 1);
    // Make sure we have all positive int's
    if (empty($id_incident)) {
        return false;
    }

    $id_incident = implode(',', $id_incident);
    $sql = sprintf("UPDATE tincidencia SET id_usuario = '%s' WHERE id_incidencia IN (%s)", $owner, $id_incident);

    return db_process_sql($sql);
}


/**
 * Get the author of an incident.
 *
 * @param integer $id_incident Incident id.
 *
 * @return string The author of an incident
 */
function incidents_get_author($id_incident)
{
    if ($id_incident < 1) {
        return '';
    }

    return (string) db_get_value('id_creator', 'tincidencia', 'id_incidencia', (int) $id_incident);
}


/**
 * Get the owner of an incident.
 *
 * @param integer $id_incident Incident id.
 *
 * @return string The last updater of an incident
 */
function incidents_get_owner($id_incident)
{
    if ($id_incident < 1) {
        return '';
    }

    return (string) db_get_value('id_usuario', 'tincidencia', 'id_incidencia', (int) $id_incident);
}


/**
 * Get the last updater of an incident.
 *
 * @param integer $id_incident Incident id.
 *
 * @return string The last updater of an incident
 */
function incidents_get_lastupdate($id_incident)
{
    if ($id_incident < 1) {
        return '';
    }

    return (string) db_get_value('id_lastupdate', 'tincidencia', 'id_incidencia', (int) $id_incident);
}


/**
 * Get the group id of an incident.
 *
 * @param integer $id_incident Incident id.
 *
 * @return integer The group id of an incident
 */
function incidents_get_group($id_incident)
{
    if ($id_incident < 1) {
        return 0;
    }

    return (int) db_get_value('id_grupo', 'tincidencia', 'id_incidencia', (int) $id_incident);
}


/**
 * Delete an incident out the database.
 *
 * @param mixed $id_inc An int or an array of ints to be deleted
 *
 * @return boolean True if incident was succesfully deleted, false if not
 */
function incidents_delete_incident($id_incident)
{
    global $config;
    $ids = (array) safe_int($id_incident, 1);
    // Make the input an array
    $notes = [];
    $attachments = [];
    $errors = 0;

    foreach ($ids as $id_inc) {
        // Delete incident
        $ret = db_process_sql_delete('tincidencia', ['id_incidencia' => $id_inc]);
        if ($ret === false) {
            $errors++;
        }

        // We only need the ID's
        $notes = array_merge($notes, array_keys(incidents_get_notes($id_inc)));
        $attachments = array_merge($attachments, array_keys(incidents_get_attach($id_inc)));

        db_pandora_audit(
            AUDIT_LOG_INCIDENT_MANAGEMENT,
            $config['id_user'].' deleted incident #'.$id_inc
        );
    }

    // Delete notes
    $note_err = incidents_delete_note($notes, false);
    $attach_err = incidents_delete_attach($attachments, false);

    if ($note_err === false || $attach_err === false) {
        $errors++;
    }

    if ($errors > 0) {
        return false;
    }

    return true;
}


/**
 * Delete notes out the database.
 *
 * @param mixed   $id_note  An int or an array of ints to be deleted
 * @param boolean $transact true if a transaction should be started, false if not
 *
 * @return boolean True if note was succesfully deleted, false if not
 */
function incidents_delete_note($id_note, $transact=true)
{
    $id_note = (array) safe_int($id_note, 1);
    // cast as array
    $errors = 0;

    // Delete notes
    foreach ($id_note as $id) {
        $ret = db_process_sql_delete('tnota', ['id_nota' => $id]);
        if ($ret === false) {
            $errors++;
        }
    }

    if ($errors > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Delete attachments out the database and from the machine.
 *
 * @param mixed   $id_attach An int or an array of ints to be deleted
 * @param boolean $transact  true if a transaction should be started, false if not
 *
 * @return boolean True if attachment was succesfully deleted, false if not
 */
function incidents_delete_attach($id_attach, $transact=true)
{
    global $config;

    $id_attach = (array) safe_int($id_attach, 1);
    // cast as array
    $errors = 0;

    // Delete attachment
    foreach ($id_attach as $id) {
        $filename = db_get_value('filename', 'tattachment', 'id_attachment', $id);

        $ret = db_process_sql_delete('tattachment', ['id_attachment' => $id]);
        if ($ret === false) {
            $errors++;
        }

        unlink($config['attachment_store'].'/pand'.$id.'_'.$filename);
    }

    if ($errors > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Get notes based on the incident id.
 *
 * @param integer $id_incident An int with the incident id
 *
 * @return array An array of all the notes for that incident
 */
function incidents_get_notes($id_incident)
{
    $return = db_get_all_rows_field_filter('tnota', 'id_incident', (int) $id_incident);

    if ($return === false) {
        $return = [];
    }

    $notes = [];
    foreach ($return as $row) {
        $notes[$row['id_nota']] = $row;
    }

    return $notes;
}


/**
 * Get attachments based on the incident id.
 *
 * @param integer $id_incident An int with the incident id
 *
 * @return array An array of all the notes for that incident
 */
function incidents_get_attach($id_incident)
{
    $return = db_get_all_rows_field_filter('tattachment', 'id_incidencia', (int) $id_incident);

    if ($return === false) {
        $return = [];
    }

    $attach = [];
    foreach ($return as $row) {
        $attach[$row['id_attachment']] = $row;
    }

    return $attach;
}


/**
 * Get user id of a note.
 *
 * @param integer $id_note Note id.
 *
 * @return string User id of the given note.
 */
function incidents_get_notes_author($id_note)
{
    return (string) db_get_value('id_usuario', 'tnota', 'id_nota', (int) $id_note);
}


/**
 * Interface to Integria API functionality.
 *
 * @param string $url            Url to Integria API with user, password and option (function to use).
 * @param string $postparameters Additional parameters to pass.
 *
 * @return variant The function result called in the API.
 */
function incidents_call_api($url, $postparameters=false)
{
    $curlObj = curl_init();
    curl_setopt($curlObj, CURLOPT_URL, $url);
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    if ($postparameters !== false) {
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $postparameters);
    }

    $result = curl_exec($curlObj);
    curl_close($curlObj);

    return $result;
}


/**
 * Converts Xml format file to an array datatype.
 *
 * @param string $xml Xml file to convert.
 *
 * @return array A Json encoded array with xml content.
 */
function incidents_xml_to_array($xml)
{
    $xmlObj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    return json_decode(json_encode($xmlObj), true);
}
