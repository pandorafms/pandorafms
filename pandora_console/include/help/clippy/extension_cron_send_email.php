<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Clippy
 */

function clippy_extension_cron_send_email() {
	
	$return_tours = array();
	$return_tours['first_step_by_default'] = true;
	$return_tours['help_context'] = true;
	$return_tours['tours'] = array();
	
	//==================================================================
	//Help tour about extension cron send email
	//------------------------------------------------------------------
	$return_tours['tours']['extension_cron_send_email'] = array();
	$return_tours['tours']['extension_cron_send_email']['steps'] = array();
	$return_tours['tours']['extension_cron_send_email']['steps'][] = array(
		'init_step_context' => true,
		'intro' => '<table>' .
			'<tr>' .
			'<td class="context_help_body">' .
			__('The configuration of email for the task email is in the mail setup:') . '<br />' .
			__('Please check if the email configuration is correct.') .
			'</td>' .
			'</tr>' .
			'</table>'
		);
	$return_tours['tours']['extension_cron_send_email']['conf'] = array();
	$return_tours['tours']['extension_cron_send_email']['conf']['autostart'] = false;
	$return_tours['tours']['extension_cron_send_email']['conf']['show_bullets'] = 0;
	$return_tours['tours']['extension_cron_send_email']['conf']['show_step_numbers'] = 0;
	//==================================================================
	
	return $return_tours;
}
?>