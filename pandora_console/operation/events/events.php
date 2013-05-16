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

require_once ($config['homedir']."/include/functions_events.php"); //Event processing functions
require_once ($config['homedir']."/include/functions_alerts.php"); //Alerts processing functions
require_once ($config['homedir'].'/include/functions_agents.php'); //Agents functions
require_once ($config['homedir'].'/include/functions_users.php'); //Users functions
require_once ($config['homedir'].'/include/functions_graph.php');
require_once ($config['homedir'].'/include/functions_ui.php');

check_login ();

if (! check_acl ($config["id_user"], 0, "ER")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

// Set metaconsole mode
$meta = false;
if (enterprise_installed() && defined("METACONSOLE")) {
	$meta = true;
}

// Get the history mode
$history = (bool) get_parameter('history', 0);

$readonly = false;

if (!$meta) {
	if (isset($config['event_replication']) &&  
		$config['event_replication'] == 1) {
		
		if ($config['show_events_in_local'] == 0) {
			db_pandora_audit("ACL Violation",
				"Trying to access event viewer. View disabled due event replication.");
			ui_print_info_message('Event viewer is disabled due event replication. For more information, please contact with the administrator');
			return;
		}
		else {
			$readonly = true;
		}
	}
}

if (is_ajax ()) {
	$get_event_tooltip = (bool) get_parameter ('get_event_tooltip');
	$validate_event = (bool) get_parameter ('validate_event');
	$delete_event = (bool) get_parameter ('delete_event');
	$get_events_fired = (bool) get_parameter('get_events_fired');
	$standby_alert = (bool) get_parameter('standby_alert');
	$meta = get_parameter('meta', 0);
	$history = get_parameter('history', 0);
	
	if ($get_event_tooltip) {
		$id = (int) get_parameter ('id');
		$event = events_get_event ($id);
		if ($event === false)
			return;
		
		echo '<h3>' . __('Event') . '</h3>';
		echo '<strong>' . __('Type') . ': </strong><br />';
		
		events_print_type_img ($event["event_type"]);
		echo ' ';
		if ($event["event_type"] == "system") {
			echo __('System');
		}
		elseif ($event["id_agente"] > 0) {
			// Agent name
			echo agents_get_name ($event["id_agente"]);
		}
		else {
			echo '';
		}
		echo '<br />';
		echo '<strong>' . __('Timestamp') . ': </strong><br />';
		ui_print_timestamp ($event['utimestamp']);
		
		echo '<br />';
		echo '<strong>' . __('Description') . ': </strong><br />';
		echo $event['evento'];
		
		return;
	}
	
	if ($validate_event) {
		$id = (int) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		$comment = (string) get_parameter ('comment');
		$new_status = get_parameter ('new_status');
		
		// Set off the standby mode when close an event
		if ($new_status == 1) {
			$event = events_get_event ($id);
			alerts_agent_module_standby ($event['id_alert_am'], 0);
		}
		
		$return = events_change_status ($id, $new_status, $meta);
		if ($return)
			echo 'ok';
		else
			echo 'error';
		return;
	}
	
	if ($delete_event) {
		$id = (array) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		
		$return = events_delete_event ($id, $similars, $meta, $history);
		if ($return)
			echo 'ok';
		else
			echo 'error';
		return;
	}
	
	if ($get_events_fired) {
		
		$id = get_parameter('id_row');
		$idGroup = get_parameter('id_group');
		
		$query = ' AND id_evento > ' . $id;
		
		$type = array();
		$alert = get_parameter('alert_fired');
		if ($alert == 'true') {
			$resultAlert = alerts_get_event_status_group($idGroup,
				'alert_fired', $query);
		}
		$critical = get_parameter('critical');
		if ($critical == 'true') {
			$resultCritical = alerts_get_event_status_group($idGroup,
				'going_up_critical', $query);
		}
		$warning = get_parameter('warning');
		if ($warning == 'true') {
			$resultWarning = alerts_get_event_status_group($idGroup,
				'going_up_warning', $query);
		}
		
		if ($resultAlert) {
			$return = array('fired' => $resultAlert,
				'sound' => $config['sound_alert']);
		}
		else if ($resultCritical) {
			$return = array('fired' => $resultCritical,
				'sound' => $config['sound_critical']);
		}
		else if ($resultWarning) {
			$return = array('fired' => $resultWarning,
				'sound' => $config['sound_warning']);
		}
		else {
			$return = array('fired' => 0);
		}
		
		echo json_encode($return);
	}
	
	return;
}


$offset = (int) get_parameter ("offset", 0);
$ev_group = (int) get_parameter ("ev_group", 0); //0 all
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 3); // -1 all, 0 only new, 1 only validated, 2 only in process, 3 only not validated,
$id_agent = (int) get_parameter ("id_agent", 0);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$event_view_hr = (int) get_parameter ("event_view_hr", $history ? 0 : $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$group_rep = (int) get_parameter ("group_rep", 1);
$delete = (bool) get_parameter ("delete");
$validate = (bool) get_parameter ("validate", 0);
$section = (string) get_parameter ("section", "list");
$text_agent = (string) get_parameter('text_agent', __("All"));
$filter_only_alert = (int) get_parameter('filter_only_alert', -1);
$filter_id = (int) get_parameter('filter_id', 0);
$id_name = (string) get_parameter('id_name', '');
$id_group = (int) get_parameter('id_group', 0);
$open_filter = (int) get_parameter('open_filter', 0);

$text_agent = (string) get_parameter("text_agent", __("All"));

$tag_with_json = base64_decode(get_parameter("tag_with", '')) ;
$tag_with_json_clean = io_safe_output($tag_with_json);
$tag_with_base64 = base64_encode($tag_with_json_clean);
$tag_with = json_decode($tag_with_json_clean, true);
if (empty($tag_with)) $tag_with = array();
$tag_with = array_diff($tag_with, array(0 => 0));

$tag_without_json = base64_decode(get_parameter("tag_without", ''));
$tag_without_json_clean = io_safe_output($tag_without_json);
$tag_without_base64 = base64_encode($tag_without_json_clean);
$tag_without = json_decode($tag_without_json_clean, true);
if (empty($tag_without)) $tag_without = array();
$tag_without = array_diff($tag_without, array(0 => 0));	

$search = io_safe_output(preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&", rawurldecode (get_parameter ("search"))));

users_get_groups ($config["id_user"], "ER");

$ids = (array) get_parameter ("eventid", -1);

$params = "search=" . rawurlencode(io_safe_input($search)) . 
	"&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity . 
	"&amp;status=" . $status . 
	"&amp;ev_group=" . $ev_group . 
	"&amp;refr=" . (int)get_parameter("refr", 0) . 
	"&amp;id_agent=" . $id_agent . 
	"&amp;pagination=" . $pagination . 
	"&amp;group_rep=" . $group_rep . 
	"&amp;event_view_hr=" . $event_view_hr . 
	"&amp;id_user_ack=" . $id_user_ack .
	"&amp;tag_with=". $tag_with_base64 . 
	"&amp;tag_without=" . $tag_without_base64 . 
	"&amp;filter_only_alert" . $filter_only_alert .
	"&amp;offset=" . $offset .
	"&amp;toogle_filter=no" .
	"&amp;filter_id=" . $filter_id .
	"&amp;id_name=" . $id_name .
	"&amp;id_group=" . $id_group .
	"&amp;history=" . (int)$history .
	"&amp;section=" . $section .
	"&amp;open_filter=" . $open_filter .
	"&amp;pure=" . $config["pure"];

if ($meta) {
	$params .= "&amp;text_agent=" . $text_agent;
}

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;" . $params;

// Header
if ($config["pure"] == 0 || $meta) {
	$pss = get_user_info($config['id_user']);
	$hashup = md5($config['id_user'] . $pss['password']);
	
	// Fullscreen
	$fullscreen['active'] = false;
	$fullscreen['text'] = '<a href="'.$url.'&amp;pure=1">' . 
		html_print_image("images/full_screen.png", true, array ("title" => __('Full screen'))) .'</a>';
	
	// Event list
	$list['active'] = false;
	$list['text'] = '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'">' . 
		html_print_image("images/events_list.png", true, array("title" => __('Event list'))) . '</a>';
	
	// History event list
	$history_list['active'] = false;
	$history_list['text'] = '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&amp;section=history&amp;history=1">' . 
		html_print_image("images/books.png", true, array("title" => __('History event list'))) . '</a>';
	
	// RSS
	$rss['active'] = false;
	$rss['text'] = '<a href="operation/events/events_rss.php?user=' . $config['id_user'] . '&hashup=' . $hashup . '&'.$params.'">' . 
		html_print_image("images/rss.png", true, array ("title" => __('RSS Events'))) .'</a>';
	
	// Marquee
	$marquee['active'] = false;
	$marquee['text'] = '<a href="operation/events/events_marquee.php">' . 
		html_print_image("images/heart.png", true, array ("title" => __('Marquee display'))) .'</a>';
	
	// CSV
	$csv['active'] = false;
	$csv['text'] = '<a href="operation/events/export_csv.php?' . $params . '">' . 
		html_print_image("images/csv_mc.png", true, array ("title" => __('Export to CSV file'))) .'</a>';
	
	// Sound events
	$sound_event['active'] = false;
	$sound_event['text'] = '<a href="javascript: openSoundEventWindow();">' . html_print_image('images/sound.png', true, array('title' => __('Sound events'))) . '</a>';
	
	// If the user has administrator permission display manage tab
	if (check_acl ($config["id_user"], 0, "EW")) {
		// Manage events
		$manage_events['active'] = false;
		$manage_events['text'] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=filter&amp;pure='.$config['pure'].'">' .
			html_print_image("images/setup.png", true, array ("title" => __('Manage events'))) . '</a>';
		
		$manage_events['godmode'] = true;
		
		$onheader = array(
			'manage_events' => $manage_events,
			'fullscreen' => $fullscreen,
			'list' => $list,
			'history' => $history_list,
			'rss' => $rss,
			'marquee' => $marquee,
			'csv' => $csv,
			'sound_event' => $sound_event) ;
	}
	else {
		$onheader = array('fullscreen' => $fullscreen,
			'list' => $list,
			'history' => $history_list,
			'rss' => $rss,
			'marquee' => $marquee,
			'csv' => $csv,
			'sound_event' => $sound_event) ;
	}
	
	// If the history event is not ebabled, dont show the history tab
	if(!isset($config['metaconsole_events_history']) || $config['metaconsole_events_history'] != 1) {
		unset($onheader['history']);
	}
	
	switch ($section) {
		case 'sound_event':
			$onheader['sound_event']['active'] = true;
			$section_string = __('Sound events');
			break;
		case 'history':
			$onheader['history']['active'] = true;
			$section_string = __('History');
			break;
		default:
			$onheader['list']['active'] = true;
			$section_string = __('List');
			break;
	}
	
	if (! defined ('METACONSOLE')) {
		unset($onheader['history']);
		ui_print_page_header (__("Events"), "images/op_events.png",
			false, "eventview", false, $onheader);
	}
	else {
		unset($onheader['rss']);
		unset($onheader['marquee']);
		unset($onheader['csv']);
		unset($onheader['sound_event']);
		unset($onheader['fullscreen']);
		ui_meta_print_header(__("Events"), $section_string, $onheader);
	}
	
	?>
	<script type="text/javascript">
		function openSoundEventWindow() {
			url = '<?php
				echo ui_get_full_url('operation/events/sound_events.php');
				?>';
			
			window.open(url, '<?php __('Sound Alerts'); ?>','width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes'); 
		}
		
		function openURLTagWindow(url) {
			window.open(url, '','width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no'); 
		}
	</script>
	<?php
}
else {
	// Fullscreen
	echo "<h2>" . __('Events') . " &raquo; " . __('Main event view') . "&nbsp;";
	echo ui_print_help_icon ("eventview", true);
	echo "&nbsp;";
	
	echo '<a target="_top" href="' . $url . '&amp;pure=0">';
	html_print_image ("images/normalscreen.png", false,
		array("title" => __('Back to normal mode')));
	echo '</a>';
	echo "</h2>";
}

// Error div for ajax messages
echo "<div id='show_message_error'>";
echo "</div>";


if (($section == 'validate') && ($ids[0] == -1)) {
	$section = 'list';
	ui_print_error_message (__('No events selected'));
}

//Process validation (pass array or single value)
if ($validate) {
	$ids =  get_parameter ("eventid", -1);
	$comment =  get_parameter ("comment", '');
	$new_status =  get_parameter ("select_validate", 1);
	$ids = explode(',',$ids);
	$standby_alert = (bool) get_parameter("standby-alert");
	
	// Avoid to re-set inprocess events
	if ($new_status == 2) {
		foreach ($ids as $key => $id) {
			$event = events_get_event($id);
			if ($event['estado'] == 2) {
				unset($ids[$key]);
			}
		}
	}
	
	if (isset($ids[0]) && $ids[0] != -1) {
		$return = events_change_status ($ids, $new_status, $meta);
		
		if ($new_status == 1) {
			ui_print_result_message ($return,
				__('Successfully validated'),
				__('Could not be validated'));
		}
		elseif ($new_status == 2) {
			ui_print_result_message ($return,
				__('Successfully set in process'),
				__('Could not be set in process'));
		}
	}
}

//Process deletion (pass array or single value)
if ($delete) {
	$ids = (array) get_parameter ("validate_ids", -1);
	
	if ($ids[0] != -1) {
		$return = events_delete_event ($ids, ($group_rep == 1), $meta);
		ui_print_result_message ($return,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
	require_once($config['homedir'].'/operation/events/events_list.php');
}
else {
	switch ($section) {
		case 'list':
		case 'history':
			require_once($config['homedir'].'/operation/events/events_list.php');
			break;
	}
}

echo "<div id='event_details_window'></div>";
echo "<div id='event_response_window'></div>";

ui_require_jquery_file ('bgiframe');
ui_require_javascript_file('pandora_events');

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready( function() {
	
	$("input[name=all_validate_box]").change (function() {
		if ($(this).is(":checked")) {
			$("input[name='validate_ids[]']").check();
		}
		else {
			$("input[name='validate_ids[]']").uncheck();
		}
		
		$("input[name='validate_ids[]']").trigger('change');
	});
	
	// If some of the checkbox checked cahnnot be deleted disable the delete button
	$("input[name='validate_ids[]']").change (function() {
		var canDeleted = 1;
		$("input[name='validate_ids[]']").each(function() {
			if($(this).attr('checked') == 'checked') {
				var classs = $(this).attr('class');
				classs = classs.split(' ');
				if(classs[0] != 'candeleted') {
					canDeleted = 0;
				}
			}
		});
		
		if(canDeleted == 0) {
			$('#button-delete_button').attr('disabled','disabled');
		}
		else {
			$('#button-delete_button').removeAttr('disabled');
		}
	});
	
	$('#select_validate').change (function() {
		$option = $('#select_validate').val();
	});
	
	$("#tgl_event_control").click (function () {
		$("#event_control").toggle ();
		// Trick to don't collapse filter if autorefresh button has been pushed
		if ($("#hidden-toogle_filter").val() == 'true') {
			$("#hidden-toogle_filter").val('false');
		}
		else {
			$("#hidden-toogle_filter").val('true');
		}
		return false;
	});
	
	$("a.validate_event").click (function () {
		$tr = $(this).parents ("tr");
		
		id = this.id.split ("-").pop ();
		
		var comment = $('#textarea_comment_'+id).val();
		var select_validate = $('#select_validate_'+id).val(); // 1 validate, 2 in process, 3 add comment
		var similars = $('#group_rep').val();
		
		if (!select_validate) {
			select_validate = 1;
		}
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events",
			"validate_event" : 1,
			"id" : id,
			"comment" : comment,
			"new_status" : select_validate,
			"similars" : similars
			},
			function (data, status) {
				if (data == "ok") {
					
					// Refresh interface elements, don't reload (awfull)
					// Validate
					if (select_validate == 1) {
						$("#status_img_"+id).attr ("src", "images/spinner.gif");
						// Change status description
						$("#status_row_"+id).html(<?php echo "'" . __('Event validated') . "'"; ?>);
						
						// Get event comment
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_row_"+id).html(data);
							});
						
						// Get event comment in header
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment_header" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_header_"+id).html(data);
							});
						
						// Change state image
						$("#validate-"+id).css("display", "none");
						$("#status_img_"+id).attr ("src", "images/tick.png");
						$("#status_img_"+id).attr ("title", <?php echo "'" . __('Event validated') . "'"; ?>);
						$("#status_img_"+id).attr ("alt", <?php echo "'" . __('Event validated') . "'"; ?>);
						
						// Remove row due to new state
						if (($("#status").val() == 2)
							|| ($("#status").val() == 0)
							|| ($("#status").val() == 3)) {
							
							$.each($tr, function(index, value) {
								row = value;
								
								if ($(row).attr('id') != '') {
									
									row_id_name = $(row).attr('id').split('-').shift();
									row_id_number = $(row).attr('id').split('-').pop() - 1;
									row_id_number_next = parseInt($(row).attr('id').split('-').pop()) + 1;
									previous_row_id = $(row).attr('id');
									current_row_id = row_id_name + "-" + row_id_number;
									selected_row_id = row_id_name + "-" + row_id_number + "-0";
									next_row_id = row_id_name + '-' + row_id_number_next;
									
									$("#"+previous_row_id).css('display', 'none');
									$("#"+current_row_id).css('display', 'none');
									$("#"+selected_row_id).css('display', 'none');
									$("#"+next_row_id).css('display', 'none');
								}
							});
						}
						
					} // In process
					else if (select_validate == 2) {
						$("#status_img_"+id).attr ("src", "images/spinner.gif");
						// Change status description
						$("#status_row_"+id).html(<?php echo "'" . __('Event in process') . "'"; ?>);
						
						// Get event comment
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_row_"+id).html(data);
							});
						
						// Get event comment in header
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment_header" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_header_"+id).html(data);
							});
						
						// Remove delete link (if event is not grouped and there is more than one event)
						if ($("#group_rep").val() == 1) {
							if (parseInt($("#count_event_group_"+id).text()) <= 1) {
								$("#delete-"+id).replaceWith('<img alt="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" title="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" src="images/cross.disabled.png">');
							}
						}
						else { // Remove delete link (if event is not grouped)
							$("#delete-"+id).replaceWith('<img alt="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" title="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" src="images/cross.disabled.png">');
						}
						
						// Change state image
						$("#status_img_"+id).attr ("src", "images/hourglass.png");
						$("#status_img_"+id).attr ("title", <?php echo "'" . __('Event in process') . "'"; ?>);
						$("#status_img_"+id).attr ("alt", <?php echo "'" . __('Event in process') . "'"; ?>);
						
						// Remove row due to new state
						if (($("#status").val() == 0) || ($("#status").val() == 1)) {
							
							$.each($tr, function(index, value){
								row = value;
								
								if ($(row).attr('id') != ''){
									
									row_id_name = $(row).attr('id').split('-').shift();
									row_id_number = $(row).attr('id').split('-').pop() - 1;
									row_id_number_next = parseInt($(row).attr('id').split('-').pop()) + 1;
									previous_row_id = $(row).attr('id');
									current_row_id = row_id_name + "-" + row_id_number;
									selected_row_id = row_id_name + "-" + row_id_number + "-0";
									next_row_id = row_id_name + '-' + row_id_number_next;
									
									$("#"+previous_row_id).css('display', 'none');
									$("#"+current_row_id).css('display', 'none');
									$("#"+selected_row_id).css('display', 'none');
									$("#"+next_row_id).css('display', 'none');
								}
							});
							
						}
					} // Add comment
					else if (select_validate == 3) {
						// Get event comment
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_row_"+id).html(data);
							});
							
						// Get event comment in header
						jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
							{"page" : "operation/events/events",
							"get_comment_header" : 1,
							"id" : id
							},
							function (data, status) {
								$("#comment_header_"+id).html(data);
							});
					}
					
					//location.reload();
				}
				else {
					$("#result")
						.showMessage ("<?php echo __('Could not be validated')?>")
						.addClass ("error");
				}
			},
			"html"
		);
	});
	
	$("a.delete_event").click (function () {
		confirmation = confirm("<?php echo __('Are you sure?'); ?>");
		if (!confirmation) {
			return;
		}
		meta = $('#hidden-meta').val();
		history = $('#hidden-history').val();
		
		$tr = $(this).parents ("tr");
		id = this.id.split ("-").pop ();
		
		$("#delete_cross_"+id).attr ("src", "images/spinner.gif");
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events",
			"delete_event" : 1,
			"id" : id,
			"similars" : <?php echo ($group_rep ? 1 : 0) ?>,
			"meta" : meta,
			"history" : history
			},
			function (data, status) {
				if (data == "ok") {
					$tr.remove ();
					$('#show_message_error').html('<h3 class="suc"> <?php echo __('Successfully delete'); ?> </h3>');
				}
				else
					$('#show_message_error').html('<h3 class="error"> <?php echo __('Error deleting event'); ?> </h3>');
			},
			"html"
		);
		return false;
	});
	
	function toggleDiv (divid) {
		if (document.getElementById(divid).style.display == 'none') {
			document.getElementById(divid).style.display = 'block';
		}
		else {
			document.getElementById(divid).style.display = 'none';
		}
	}
});
	
	function toggleCommentForm(id_event) {
		display = $('.event_form_' + id_event).css('display');
		
		$('#select_validate_' + id_event).change (function() {
			$option = $('#select_validate_' + id_event).val();
		});
		
		if (display != 'none') {
			$('.event_form_' + id_event).css('display', 'none');
			// Hide All showed rows
			$('.event_form').css('display', 'none');
			$(".select_validate").find('option:first').attr('selected', 'selected').parent('select');
		}
		else {
			$('.event_form_' + id_event).css('display', '');
		}
	}
	
	function validate_event_advanced(id, new_status) {
		$tr = $('#validate-'+id).parents ("tr");
		
		var grouped = $('#group_rep').val();
		
		var similar_ids;
		similar_ids = $('#hidden-similar_ids_'+id).val();
		meta = $('#hidden-meta').val();
		var history = $('#hidden-history').val();
		
		$("#status_img_"+id).attr ("src", "images/spinner.gif");
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "include/ajax/events",
			"change_status" : 1,
			"event_ids" : similar_ids,
			"new_status" : new_status,
			"meta" : meta,
			"history" : history
			},
			function (data, status) {
				if (data == "status_ok") {
					// Refresh interface elements, don't reload (awful)
					// Validate
					if (new_status == 1) {
						// Change status description
						$("#status_row_"+id).html(<?php echo "'" . __('Event validated') . "'"; ?>);
						
						// Change state image
						$("#validate-"+id).css("display", "none");
						$("#status_img_"+id).attr ("src", "images/tick.png");
						$("#status_img_"+id).attr ("title", <?php echo "'" . __('Event validated') . "'"; ?>);
						$("#status_img_"+id).attr ("alt", <?php echo "'" . __('Event validated') . "'"; ?>);
					} // In process
					else if (new_status == 2) {
						// Change status description
						$("#status_row_"+id).html(<?php echo "'" . __('Event in process') . "'"; ?>);
						
						// Remove delete link (if event is not grouped and there is more than one event)
						if (grouped == 1) {
							if (parseInt($("#count_event_group_"+id).text()) <= 1) {
								$("#delete-"+id).replaceWith('<img alt="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" title="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" src="images/cross.disabled.png">');
							}
						}
						else { // Remove delete link (if event is not grouped)
							$("#delete-"+id).replaceWith('<img alt="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" title="' + <?php echo "'" . __('Is not allowed delete events in process') . "'"; ?> + '" src="images/cross.disabled.png">');
						}
						
						// Change state image
						$("#status_img_"+id).attr ("src", "images/hourglass.png");
						$("#status_img_"+id).attr ("title", <?php echo "'" . __('Event in process') . "'"; ?>);
						$("#status_img_"+id).attr ("alt", <?php echo "'" . __('Event in process') . "'"; ?>);
						
						// Remove row due to new state
						if (($("#status").val() == 0)
							|| ($("#status").val() == 1)) {
							
							$.each($tr, function(index, value) {
								row = value;
								
								if ($(row).attr('id') != '') {
									
									row_id_name = $(row).attr('id').split('-').shift();
									row_id_number = $(row).attr('id').split('-').pop() - 1;
									row_id_number_next = parseInt($(row).attr('id').split('-').pop()) + 1;
									previous_row_id = $(row).attr('id');
									current_row_id = row_id_name + "-" + row_id_number;
									selected_row_id = row_id_name + "-" + row_id_number + "-0";
									next_row_id = row_id_name + '-' + row_id_number_next;
									
									$("#"+previous_row_id).css('display', 'none');
									$("#"+current_row_id).css('display', 'none');
									$("#"+selected_row_id).css('display', 'none');
									$("#"+next_row_id).css('display', 'none');
								}
							});
							
						}
					}
				}
				else {
					$("#result")
						.showMessage ("<?php echo __('Could not be validated')?>")
						.addClass ("error");
				}
			},
			"html"
		);
	}
	
	
	// Autoload event giving the id as POST/GET parameter
	<?php
	$load_event = get_parameter('load_event',0);
	
	if ($load_event) {
	?>
		show_event_dialog(<?php echo $load_event; ?>, 1);
	<?php
	}
	?>
	/* ]]> */
</script>
