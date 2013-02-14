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
require_once($config['homedir'] . "/include/functions_snmp_browser.php");

// AJAX call
if (is_ajax()) {
	
	// Read the action to perform
	$action = (string) get_parameter ("action", "");
	$target_ip = (string) get_parameter ("target_ip", '');
	$community = (string) get_parameter ("community", '');
	$starting_oid = (string) get_parameter ("starting_oid", '.');
	$target_oid = htmlspecialchars_decode (get_parameter ("oid", ""));
	
	// SNMP browser
	if ($action == "snmptree") {
		$snmp_tree = snmp_browser_get_tree ($target_ip, $community, $starting_oid);
		if (! is_array ($snmp_tree)) {
			echo $snmp_tree;
		} else {
			snmp_browser_print_tree ($snmp_tree);
		}
		return;
	}
	// SNMP get
	else if ($action == "snmpget") {
		$oid = snmp_browser_get_oid ($target_ip, $community, $target_oid);
		snmp_browser_print_oid ($oid);
		return;
	}
	
	return;
}

// Check login and ACLs
check_login ();
if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// Read parameters
//$target_ip = (string) get_parameter ("target_ip", '');
//$community = (string) get_parameter ("community", '');

// Header
$url = 'index.php?sec=estado&sec2=operation/snmpconsole/snmp_browser&refr=' . $config["refr"] . '&pure=' . $config["pure"];
if ($config["pure"]) {
	// Windowed
	$link = '<a target="_top" href="'.$url.'&pure=0&refr=30">' . html_print_image("images/normalscreen.png", true, array("title" => __('Normal screen')))  . '</a>';
} else {
	// Fullscreen
	$link = '<a target="_top" href="'.$url.'&pure=1&refr=0">' . html_print_image("images/fullscreen.png", true, array("title" => __('Full screen'))) . '</a>';
}
ui_print_page_header (__("SNMP Browser"), "images/computer_error.png", false, "", false, $link);

// Target selection
$table->width = '100%';
$table->size = array ();
$table->data = array ();

// String search_string
$table->data[0][0] = '<strong>'.__('Target IP').'</strong>';
$table->data[0][1] = html_print_input_text ('target_ip', '', '', 25, 0, true);
$table->data[0][2] = '<strong>'.__('Community').'</strong>';
$table->data[0][3] = html_print_input_text ('community', '', '', 25, 0, true);
$table->data[0][4] = html_print_image ("images/fullscreen.png", true, array ('title' => __('Expand the tree') . ' (' . __('can be slow') . ')', 'style' => 'vertical-align: middle;', 'onclick' => 'expandAll();'));
$table->data[0][4] .= '&nbsp;' . html_print_image ("images/normalscreen.png", true, array ('title' => __('Collapse the tree'), 'style' => 'vertical-align: middle;', 'onclick' => 'collapseAll();'));
$table->data[1][0] = '<strong>'.__('Starting OID').'</strong>';
$table->data[1][1] = html_print_input_text ('starting_oid', '', '', 25, 0, true);
$table->data[1][2] = '<strong>'.__('Search text').'</strong>';
$table->data[1][3] = html_print_input_text ('search_text', '', '', 25, 0, true);
$table->data[1][4] = html_print_image ("images/lupa.png", true, array ('title' => __('Search'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchText();'));
$table->data[1][4] .= '&nbsp;' . html_print_image ("images/go_first.png", true, array ('title' => __('First match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchFirstMatch();'));
$table->data[1][4] .= '&nbsp;' . html_print_image ("images/go_previous.png", true, array ('title' => __('Previous match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchPrevMatch();'));
$table->data[1][4] .= '&nbsp;' . html_print_image ("images/go_next.png", true, array ('title' => __('Next match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchNextMatch();'));
$table->data[1][4] .= '&nbsp;' . html_print_image ("images/go_last.png", true, array ('title' => __('Last match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchLastMatch();'));

echo '<div style="width: 95%">';
echo html_print_table($table, true);
html_print_input_hidden ('search_count', 0, false);
html_print_input_hidden ('search_index', -1, false);
echo '<div>';
echo html_print_button(__('Browse'), 'browse', false, 'snmpBrowse()', 'class="sub upd"', true);
echo '</div>';
echo '</div>';

// SNMP tree
echo '<div style="width: 95%; margin-top: 5px; background-color: #F4F5F4; border: 1px solid #E2E2E2; border-radius: 4px; position: relative">';
echo   '<div id="search_results" style="display:none; padding: 5px; background-color: #EAEAEA;"></div>';
echo   '<div id="spinner" style="position: absolute; top:0; left:0px; display:none;">' . html_print_image ("images/spinner.gif", true) . '</div>';
echo   '<div id="snmp_browser" style="height: 600px; overflow: auto;"></div>';
echo   '<div id="snmp_data" style="width: 40%; position: absolute; top:0; right:20px"></div>';
echo '</div>';

?>

<script language="JavaScript" type="text/javascript">

// Load the SNMP tree via AJAX
function snmpBrowse () {

	// Empty the SNMP tree
	$("#snmp_browser").html('');

	// Hide the data div
	hideOIDData();
	
	// Show the spinner
	$("#spinner").css('display', '');

	// Read the target IP and community
	var target_ip = $('#text-target_ip').val();
	var community = $('#text-community').val();
	var starting_oid = $('#text-starting_oid').val();
	
	// Prepare the AJAX call
	var params = [
		"target_ip=" + target_ip,
		"community=" + community,
		"starting_oid=" + starting_oid,
		"action=" + "snmptree",
		"page=operation/snmpconsole/snmp_browser"
	];

	// Browse!
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
		async: true,
		timeout: 120000,
		success: function (data) {
			
			// Hide the spinner
			$("#spinner").css('display', 'none');
			
			// Load the SNMP tree
			$("#snmp_browser").html(data);
		}
	});
}

// Expand or collapse an SNMP tree node
function toggleTreeNode(node) {

	var display = $("#ul_" + node).css('display');
	var src = $("#anchor_" + node).children("img").attr('src');
	
	// Show the expanded or collapsed square
	if (display == "none") {
		src = src.replace("closed", "expanded");
	} else {
		src = src.replace("expanded", "closed");
	}
	$("#anchor_" + node).children("img").attr('src', src);
	
	// Hide or show leaves
	$("#ul_" + node).toggle();
}

// Expand an SNMP tree node
function expandTreeNode(node) {

	if (node == 0) {
		return;
	}
	
	// Show the expanded square
	var src = $("#anchor_" + node).children("img").attr('src');
	src = src.replace("closed", "expanded");
	$("#anchor_" + node).children("img").attr('src', src);
	
	// Show leaves
	$("#ul_" + node).css('display', '');
}

// Expand an SNMP tree node
function collapseTreeNode(node) {

	if (node == 0) {
		return;
	}
	
	// Show the collapsed square
	var src = $("#anchor_" + node).children("img").attr('src');
	src = src.replace("expanded", "closed");
	$("#anchor_" + node).children("img").attr('src', src);
	
	// Hide leaves
	$("#ul_" + node).css('display', 'none');
}

// Expand all tree nodes
function expandAll(node) {

	$('#snmp_browser').find('ul').each ( function () {
		var id = $(this).attr('id').substr(3);
		expandTreeNode (id);
	});
}

// Collapse all tree nodes
function collapseAll(node) {

	$('#snmp_browser').find('ul').each ( function () {
		var id = $(this).attr('id').substr(3);
		collapseTreeNode (id);
	});
}

// Perform an SNMP get request via AJAX
function snmpGet (oid) {

	// Empty previous OID data
	$("#snmp_data").html()

	// Read the target IP and community
	var target_ip = $('#text-target_ip').val();
	var community = $('#text-community').val();
	
	// Prepare the AJAX call
	var params = [
		"target_ip=" + target_ip,
		"community=" + community,
		"oid=" + oid,
		"action=" + "snmpget",
		"page=operation/snmpconsole/snmp_browser"
	];

	// SNMP get!
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
		async: true,
		timeout: 60000,
		success: function (data) {
			$("#snmp_data").html(data);
		}
	});
	
	// Show the data div
	showOIDData();
}

// Show the div that displays OID data
function showOIDData() {
	$("#snmp_data").css('display', '');
}

// Hide the div that displays OID data
function hideOIDData() {
	$("#snmp_data").css('display', 'none');
}

// Search the SNMP tree for a matching string
function searchText() {

	var text = $('#text-search_text').val();
	var regexp = new RegExp(text);

	// Hide previous search result count
	$("#search_results").css('display', '');

	// Show the spinner
	$("#spinner").css('display', '');

	// Collapse previously searched nodes
	$('.expanded').each( function () {
		$(this).removeClass('expanded');
		
		// Remove the leading ul_
		var node_id = $(this).attr('id').substr(3);
		
		collapseTreeNode(node_id);
	});
	
	// Un-highlight previously searched nodes
	$('match').removeClass('match');
	$('span').removeClass('group_view_warn');

	// Hide values
	$('span.value').css('display', 'none');

	// Disable empty searches				
	var count = 0;
	if (text != '') {
		count = searchTreeNode($('#snmp_browser'), regexp);
	}
	
	// Hide the spinner
	$("#spinner").css('display', 'none');

	// Show and save the search result count
	$("#hidden-search_count").val(count);
	$("#search_results").text("<?php echo __("Search matches"); ?>" + ': ' + count);
	$("#search_results").css('display', '');

	// Reset the search index
	$("#hidden-search_index").val(-1);

	// Focus the first match
	searchNextMatch ();
}

// Recursively search an SNMP tree node trying to match the given regexp
function searchTreeNode(obj, regexp) {
	
	// For each node tree
	var count = 0;
	$(obj).children("ul").each( function () {
		var ul_node = this;
		
		// Expand if regexp matches one of its children
		$(ul_node).addClass('expand')
		
		// Search children for matches
		$(ul_node).children("li").each( function () {
			var li_node = this;
			var text = $(li_node).text();

			// Match!
			if (regexp.test(text) == true) {
		
				count++;
				
				// Highlight in yellow
				$(li_node).children('span').addClass('group_view_warn');
				$(li_node).addClass('match');
				
				// Show the value
				$(li_node).children('span.value').css('display', '');
				
				// Expand all nodes that lead to this one
				$('.expand').each( function () {
					$(this).addClass('expanded');
					
					// Remove the leading ul_
					var node_id = $(this).attr('id').substr(3);
					
					expandTreeNode(node_id);
				});
			}
		});
		
		// Search sub nodes
		count += searchTreeNode(ul_node, regexp);
		
		// Do not expand this node if it has not been expanded already
		$(ul_node).removeClass('expand');
	});
	
	return count;
}

// Focus the next search match
function searchNextMatch () {
	var search_index = $("#hidden-search_index").val();
	var search_count = $("#hidden-search_count").val();

	// Update the search index
	search_index++;
	if (search_index >= search_count) {
		search_index = 0;
	}

	// Get the id of the next element
	var id = $('.match:eq(' + search_index + ')').attr('id');
	
	// Scroll
	$('#snmp_browser').animate({
		scrollTop: $('#snmp_browser').scrollTop() + $('#' + id).offset().top - $('#snmp_browser').offset().top
	}, 1000);

	// Save the search index
	$("#hidden-search_index").val(search_index);
}

// Focus the previous search match
function searchPrevMatch () {
	var search_index = $("#hidden-search_index").val();
	var search_count = $("#hidden-search_count").val();

	// Update the search index
	search_index--;
	if (search_index < 0) {
		search_index = search_count - 1;
	}

	// Get the id of the next element
	var id = $('.match:eq(' + search_index + ')').attr('id');
	
	// Scroll
	$('#snmp_browser').animate({
		scrollTop: $('#snmp_browser').scrollTop() + $('#' + id).offset().top - $('#snmp_browser').offset().top
	}, 1000);

	// Save the search index
	$("#hidden-search_index").val(search_index);
}

// Focus the first search match
function searchFirstMatch () {

	// Reset the search index
	$("#hidden-search_index").val(-1);

	// Focus the first match
	searchNextMatch();
}

// Focus the last search match
function searchLastMatch () {

	// Reset the search index
	$("#hidden-search_index").val(-1);

	// Focus the last match
	searchPrevMatch();
}

</script>
