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

require_once ("include/functions_events.php"); //Event processing functions
require_once ("include/functions_alerts.php"); //Alerts processing functions
require_once ($config['homedir'].'/include/functions_agents.php'); //Agents functions
require_once ($config['homedir'].'/include/functions_users.php'); //Users functions
require_once ($config['homedir'].'/include/functions_graph.php');
require_once ($config['homedir'].'/include/functions_ui.php');

check_login ();

if (! check_acl ($config["id_user"], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_event_tooltip = (bool) get_parameter ('get_event_tooltip');
	$validate_event = (bool) get_parameter ('validate_event');
	$delete_event = (bool) get_parameter ('delete_event');
	$get_events_fired = (bool) get_parameter('get_events_fired');
	$standby_alert = (bool) get_parameter('standby_alert');
	
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
	
	if ($standby_alert) {
		$id = (int) get_parameter ('id');
		$event = events_get_event ($id);
		if ($event === false)
			return;
		
		alerts_agent_module_standby ($event['id_alert_am'], 1);
		return;
	}
	
	if ($validate_event) {
		$id = (int) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		$comment = (string) get_parameter ('comment');
		$new_status = get_parameter ('new_status');
		$validated_limit_time = get_parameter("validated_limit_time", 0);
		$event_rep = get_parameter("event_rep", 1);
		
		// Set off the standby mode when close an event
		if ($new_status == 1) {
			$event = events_get_event ($id);
			alerts_agent_module_standby ($event['id_alert_am'], 0);
		}
		
		// If the event is not repited, the similars will be disabled
		if($event_rep == 1) {
			$similars = false;
		}
		
		$return = events_validate_event ($id, $similars, $comment, $new_status, $validated_limit_time);
		if ($return)
			echo 'ok';
		else
			echo 'error';
		return;
	}
	
	if ($delete_event) {
		$id = (array) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		$validated_limit_time = get_parameter("validated_limit_time", 0);
		$event_rep = get_parameter("event_rep", 1);
		
		// If the event is not repited, the similars will be disabled
		if($event_rep == 1) {
			$similars = false;
		}
		
		$return = events_delete_event ($id, $similars, $validated_limit_time);
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
$ev_group = (int) get_parameter ("ev_group", 0); //0 = all
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 3); // -1 all, 0 only new, 1 only validated, 2 only in process, 3 only not validated,
$id_agent = (int) get_parameter ("id_agent", -2); //-2 search by text, -1 all, 0 system
$id_event = (int) get_parameter ("id_event", -1);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$event_view_hr = (int) get_parameter ("event_view_hr", $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$group_rep = (int) get_parameter ("group_rep", 1);
$delete = (bool) get_parameter ("delete");
$validate = (bool) get_parameter ("validate", 0);
$section = (string) get_parameter ("section", "list");
$text_agent = (string)get_parameter('text_agent', __("All"));
$filter_only_alert = (int)get_parameter('filter_only_alert', -1);

$search = io_safe_output(preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&", rawurldecode (get_parameter ("search"))));

users_get_groups ($config["id_user"], "IR");

$ids = (array) get_parameter ("eventid", -1);

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=" .
	io_safe_input($search) . "&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity . "&amp;status=" . $status . "&amp;ev_group=" .
	$ev_group . "&amp;refr=" . $config["refr"] . "&amp;id_agent=" .
	$id_agent . "&amp;id_event=" . $id_event . "&amp;pagination=" .
	$pagination . "&amp;group_rep=" . $group_rep . "&amp;event_view_hr=" .
	$event_view_hr . "&amp;id_user_ack=" . $id_user_ack;

// Header
if ($config["pure"] == 0) {
	$pss = get_user_info($config['id_user']);
	$hashup = md5($config['id_user'] . $pss['password']);
	
	$buttons = array(
		'fullscreen' => array('active' => false,
			'text' => '<a href="'.$url.'&amp;pure=1">' . 
				html_print_image("images/fullscreen.png", true, array ("title" => __('Full screen'))) .'</a>'),
		'rss' => array('active' => false,
			'text' => '<a href="operation/events/events_rss.php?user=' . $config['id_user'] . '&hashup=' . $hashup . 
				'&text_agent=' . $text_agent . '&ev_group='.$ev_group.'&amp;event_type='.$event_type.'&amp;search='.io_safe_input($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;event_view_hr='.$event_view_hr.'&amp;id_agent='.$id_agent.'">' . 
				html_print_image("images/rss.png", true, array ("title" => __('RSS Events'))) .'</a>'),
		'marquee' => array('active' => false,
			'text' => '<a href="operation/events/events_marquee.php">' . 
				html_print_image("images/heart.png", true, array ("title" => __('Marquee display'))) .'</a>'),
		'csv' => array('active' => false,
			'text' => '<a href="operation/events/export_csv.php?ev_group=' . $ev_group . 
				'&text_agent=' . $text_agent . '&amp;event_type='.$event_type.'&amp;search='.io_safe_input($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;event_view_hr='.$event_view_hr.'&amp;id_agent='.$id_agent.'">' . 
				html_print_image("images/disk.png", true, array ("title" => __('Export to CSV file'))) .'</a>'),
		'sound_event' => array('active' => false,
			'text' => '<a href="javascript: openSoundEventWindow();">' . html_print_image('images/music_note.png', true, array('title' => __('Sound events'))) . '</a>')
		);
	
	ui_print_page_header (__("Events"), "images/lightning_go.png", false, "eventview", false, $buttons);

	?>
	<script type="text/javascript">
	function openSoundEventWindow() {
		<?php
		$url = ui_get_full_url(false);
		?>
		url = '<?php echo $url . 'operation/events/sound_events.php'; ?>';
		
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
	$new_status =  get_parameter ("select_validate", 1);
	$comment =  get_parameter ("comment", '');
	// Ids contains ids and count of the events separated by low bar
	$ids =  get_parameter ("eventid", -1);
	$ids_array = explode(',',$ids);
	if(count($ids_array) == 1 && $ids_array[0] == -1) {
		$ids = $ids_array;
		$events_rep = array();
	}
	else {
		$ids = array();
		$events_rep = array();
		foreach($ids_array as $id) {
			$id_count = explode('_',$id);
			$ids[] = $id_count[0];
			$events_rep[] = $id_count[1];
		}
	}
	
	$standby_alert = (bool) get_parameter("standby-alert");
	$validated_limit_time = get_parameter("validated_limit_time", 0);
	
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
		$return = events_validate_event ($ids, true, $comment, $new_status, $validated_limit_time, $events_rep);
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
	
	if ($standby_alert) {
		foreach ($ids as $id) {
			$event = events_get_event ($id);
			if ($event !== false) {
				alerts_agent_module_standby ($event['id_alert_am'], 1);
			}
		}
	}
}

//Process deletion (pass array or single value)
if ($delete) {
	// Ids contains ids and count of the events separated by low bar
	$ids_array = get_parameter ("eventid", -1);

	if(count($ids_array) == 1 && $ids_array[0] == -1) {
		$ids = $ids_array;
		$events_rep = array();
	}
	else {
		$ids = array();
		$events_rep = array();
		foreach($ids_array as $id) {
			$id_count = explode('_',$id);
			$ids[] = $id_count[0];
			$events_rep[] = $id_count[1];
		}
	}

	if ($ids[0] != -1) {
		$return = events_delete_event ($ids, ($group_rep == 1), $event_view_hr, $events_rep);
		ui_print_result_message ($return,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
	require_once('operation/events/events_list.php');
}
else {
	switch ($section) {
		case 'list':
			require_once('operation/events/events_list.php');
			break;
		case 'validate':
			require_once('operation/events/events_validate.php');
			break;
	}
}

ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready( function() {

	$("#text_id_agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					add: '<?php echo json_encode(array('-1' => "All", '0' => "System"));?>',
					id_group: function() { return $("#id_group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text_id_agent").css ('background-color', '#cc0000');
					else
						$("#text_id_agent").css ('background-color', '');
					if (data == "")
						return false;
					
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);
	
	$("input[name=allbox]").change (function() {
		$("input[name='eventid[]']").attr('checked', $(this).attr('checked'));
	});
	
	$('.select_validate').change (function() {
		display = $(".standby_alert_checkbox").css('display');

		if (display != 'none') {
			$(".standby_alert_checkbox").css('display', 'none');
		}
		else {
			$(".standby_alert_checkbox").css('display', '');
		}
	});
	
	$("#tgl_event_control").click (function () {
		$("#event_control").toggle ();
		return false;
	});
	
	$("a.validate_event").click (function () {
		$tr = $(this).parents ("tr");
		
		id = this.id.split ("-").pop ();
		
		var comment = $('#textarea_comment_'+id).val();
		var select_validate = $('#select_validate_'+id).val(); // 1 validate, 2 in process
		var checkbox_standby_alert = $('#checkbox-standby-alert-'+id).attr('checked');
		var similars = $('#group_rep').val();
		var event_rep = $('#hidden-event_rep_'+id).val();
		var validated_limit_time = $('#hidden-validated_limit_time_'+id).val();
		
		if (!select_validate) {
			select_validate = 1;
		}
		
		if (checkbox_standby_alert) {
			jQuery.post ("ajax.php",
				{"page" : "operation/events/events",
				"standby_alert" : 1,
				"id" : id
				},
				function (data, status) {
					if (data != "ok") {
						$("#result")
							.showMessage ("<?php echo __('Could not set standby alert')?>")
							.addClass ("error");
					}
				},
				"html"
			);
		}
		
		jQuery.post ("ajax.php",
			{"page" : "operation/events/events",
			"validate_event" : 1,
			"id" : id,
			"comment" : comment,
			"new_status" : select_validate,
			"similars" : similars,
			"event_rep" : event_rep,
			"validated_limit_time" : validated_limit_time
			},
			function (data, status) {
				if (data == "ok") {
					$("#status_img_"+id).attr ("src", "images/spinner.gif");
					location.reload();
				}
				else {
					$("#result")
						.showMessage ("<?php echo __('Could not be validated')?>")
						.addClass ("error");
				}
			},
			"html"
		);
		//toggleCommentForm(id);
	});
	
	$("a.delete_event").click (function () {
		confirmation = confirm("<?php echo __('Are you sure?'); ?>");
		if (!confirmation) {
			return;
		}
		$tr = $(this).parents ("tr");
		id = this.id.split ("-").pop ();
		
		var event_rep = $('#hidden-event_rep_'+id).val();
		var validated_limit_time = $('#hidden-validated_limit_time_'+id).val();
		var similars = $('#group_rep').val();
		
		jQuery.post ("ajax.php",
			{"page" : "operation/events/events",
			"delete_event" : 1,
			"id" : id,
			"similars" : similars,
			"event_rep" : event_rep,
			"validated_limit_time" : validated_limit_time
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
		
		if (display != 'none') {
			$('.event_form_' + id_event).css('display', 'none');
			// Hide All showed rows
			$('.event_form').css('display', 'none');
			$(".standby_alert_checkbox").css('display', 'none');
			$(".select_validate").find('option:first').attr('selected', 'selected').parent('select');
		}
		else {
			$('.event_form_' + id_event).css('display', '');
		}
	}
	
	function toggleVisibleExtendedInfo(id_event) {
		display = $('.event_info_' + id_event).css('display');

		if (display != 'none') {
			$('.event_info_' + id_event).css('display', 'none');
		}
		else {
			$('.event_info_' + id_event).css('display', '');
		}
	}
	/* ]]> */
</script>