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

require_once ('include/functions_events.php');
require_once ('include/functions_db.php');
require_once ('include/functions_io.php');
require_once ('include/functions.php');

$get_events_details = (bool) get_parameter ('get_events_details');
if($get_events_details) {
	$event_ids = explode(',',get_parameter ('event_ids'));
	$events = db_get_all_rows_filter ('tevento',
		array ('id_evento' => $event_ids,
			'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp', 'estado', 'criticity'));

	$out = '<table class="eventtable" style="width:100%;height:100%;padding:0px 0px 0px 0px; border-spacing: 0px; margin: 0px 0px 0px 0px;">';
	$out .= '<tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td></td></tr>';
	foreach($events as $event) {
		switch($event["estado"]) {
			case 0:
				$img = "../../images/star.png";
				$title = __('New event');
				break;
			case 1:
				$img = "../../images/tick.png";
				$title = __('Event validated');
				break;
			case 2:
				$img = "../../images/hourglass.png";
				$title = __('Event in process');
				break;
		}
			
		$out .= '<tr class="'.get_priority_class ($event['criticity']).'"><td class="'.get_priority_class ($event['criticity']).'">';
		$out .= '<img src="'.$img.'" alt="'.$title.'" title="'.$title.'">';
		$out .= '</td><td class="'.get_priority_class ($event['criticity']).'" style="font-size:7pt">';
		$out .= io_safe_output($event['evento']);
		$out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #999;"><td></td><td>';
		$out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td>';
		$out .= '</td></tr>';
	}
	$out .= '</table>';
	
	echo $out;
}

?>
