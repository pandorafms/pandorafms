<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

// Check login and ACLs
check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Tag Management");
	require ("general/noaccess.php");
	return;
}

//Include functions code
require_once ($config['homedir'].'/include/functions_tags.php');

// Get parameters
$delete = (int) get_parameter ("delete_tag", 0);
$search = (int) get_parameter ("search_tag", 0);
$tag_name = (string) get_parameter ("tag_name","");
$tab = (string) get_parameter ("tab", "list");

//Ajax tooltip to deploy module's count info of a tag.
if (is_ajax ()) {
	$get_tag_tooltip = (bool) get_parameter ('get_tag_tooltip', 0);
	
	if ($get_tag_tooltip) {
		$id_tag = (int) get_parameter ('id_tag');
		$tag = tags_search_tag_id ($id_tag);
		if ($tag === false)
			return;
		
		echo '<h3>'.$tag['name'].'</h3>';
		echo '<strong>'.__('Number of modules').': </strong> ' . 
		tags_get_local_modules_count($id_tag);
		echo '<br>';
		echo '<strong>'.__('Number of policy modules').': </strong>' . 
		tags_get_policy_modules_count($id_tag);
		
		return;
	}
	return;
}

if (defined('METACONSOLE')) 
	$sec = 'advanced';
else
	$sec = 'gmodules';

$buttons = array(
	'list' => array(
		'active' => false,
		'text' => '<a href="index.php?sec='.$sec.'&sec2=godmode/tag/tag&tab=list">' . 
			html_print_image ("images/list.png", true, array ("title" => __('List tags'))) .'</a>'));

$buttons[$tab]['active'] = true;

if (defined('METACONSOLE')) {
	
	// Print header
	ui_meta_print_header(__('Tags'), "", $buttons);
	
}
else {
	
	// Header
	ui_print_page_header (__('Tags configuration'), "images/tag.png", false, "tags_config", true, $buttons);
	
}

enterprise_hook('open_meta_frame');

// Two actions can performed in this page: search and delete tags

// Delete action: This will delete a tag
if ($delete != 0) {
	$return_delete = tags_delete_tag ($delete);
	
	if ($return_delete === false) {
		db_pandora_audit("Tag management", "Fail try to delete tag #$delete");
		echo '<h3 class="error">'.__('Error deleting tag').'</h3>';
	}
	else {
		db_pandora_audit("Tag management", "Delete tag #$delete");
		echo '<h3 class="suc">'.__('Successfully deleted tag').'</h3>';
	}
}

// statements for pagination
$url = ui_get_url_refresh ();
$total_tags = tags_get_tag_count();

$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];
// Search action: This will filter the display tag view
$result = false;
// Filtered view? 
if ($search != 0) {
	$result = tags_search_tag($tag_name, $filter);
}
else {
	$result = tags_search_tag(false, $filter);
}

// Form to add new tags or search tags
echo "<table border=0 cellpadding=4 cellspacing=4 class=databox width=98%>";
echo "<tr>";
echo "<td>";
	echo '<b>' . __("Name") . "/" . __("Description") . '</b>';
echo "</td>";
echo "<td align=center>";
	echo '<form method=post action="index.php?sec='.$sec.'&sec2=godmode/tag/tag&delete_tag=0">';
	html_print_input_hidden ("search_tag", "1");
	html_print_input_text ('tag_name', $tag_name, '', 30, 255, false);
	echo "&nbsp;&nbsp;&nbsp;";
	html_print_submit_button (__('Filter'), 'filter_button', false, 'class="sub search"');
	echo "</form>";
echo "</td>";
echo "<td align=right>";
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/tag/edit_tag&action=new">';
	html_print_input_hidden ("create_tag", "1", true);
	html_print_submit_button (__('Create tag'), 'create_button', false, 'class="sub next"');
	echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";

// Prepare pagination
ui_pagination ($total_tags, $url);

// Display tags previously filtered or not
$rowPair = true;
$iterator = 0;

if (!empty($result)) {
	
	$table->width = '98%';
	$table->data = array ();
	$table->head = array ();
	$table->align = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold; text-align:center';
	$table->style[1] = 'text-align:center';
	$table->style[2] = 'text-align:center';
	$table->style[3] = 'text-align:center';
	$table->style[5] = 'text-align:center';
	$table->head[0] = __('Tag name');
	$table->head[1] = __('Description');
	$table->head[2] = __('Detail information');
	$table->head[3] = __('Number of modules affected');
	$table->head[4] = __('Email');
	$table->head[5] = __('Actions');
	
	foreach ($result as $tag) {
		if ($rowPair)
			$table->rowclass[$iterator] = 'rowPair';
		else
			$table->rowclass[$iterator] = 'rowOdd';
		$rowPair = !$rowPair;
		$iterator++;
		
		$data = array ();
		
		$data[0] = "<a href='index.php?sec=".$sec."&sec2=godmode/tag/edit_tag&action=update&id_tag=" . $tag["id_tag"] . "'>" . $tag["name"] . "</a>";  
		$data[1] = ui_print_truncate_text($tag["description"], 'description', false);
		$data[2] = '<a href="' . $tag["url"] . '">' . $tag["url"] . '</a>';
		$data[3] = ' <a class="tag_details"
			href="' . ui_get_full_url(false, false, false, false) . '/ajax.php?page=godmode/tag/tag&get_tag_tooltip=1&id_tag=' . $tag['id_tag'] . '">' .
			html_print_image("images/zoom.png", true, array("id" => 'tag-details-'.$tag['id_tag'], "class" => "img_help")) . '</a> ';
		
		$data[3] .= tags_get_modules_count($tag["id_tag"]);
		
		$email_large = io_safe_output($tag["email"]);
		$email_small = substr($email_large,0, 24);
		if ($email_large == $email_small) {
			$output = $email_large;
		}
		else {
			$output = 
				"<div title='" . sprintf(__('Emails for the tag: %s'), $tag['name']) . "' style='display: none;' class='email_large' id='email_large_" . $tag["id_tag"] . "'>" .
					$email_large . "</div>" . 
				'<span id="value_' . $tag["id_tag"] . '">' .
				$email_small . '</span> ' .
				"<a href='javascript: show_dialog(" . $tag["id_tag"] . ")'>" . html_print_image("images/rosette.png", true) . "" . "</a></span>";
		}
		$data[4] = $output;
		
		$data[5] = "<a href='index.php?sec=".$sec."&sec2=godmode/tag/edit_tag&action=update&id_tag=".$tag["id_tag"] . "'>" . html_print_image("images/config.png", true, array("title" => "Edit")) . "</a>&nbsp;&nbsp;";
		$data[5] .= '<a  href="index.php?sec='.$sec.'&sec2=godmode/tag/tag&delete_tag='.$tag["id_tag"] . '"onclick="if (! confirm (\''.__('Are you sure?').'\')) return false">' . html_print_image("images/cross.png", true, array("title" => "Delete")) . '</a>';
		array_push ($table->data, $data);
	}
	
	html_print_table ($table);
}

enterprise_hook('close_meta_frame');

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');

?>

<script type="text/javascript">
/* <![CDATA[ */
	$("a.tag_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});
/* ]]> */
</script>
<script type="text/javascript">
	$(document).ready(function () {
		$(".email_large").dialog(
			{
				autoOpen: false,
				resizable: true,
				width: 400,
				height: 200
			});
	});
	function show_dialog(id) {
		$("#email_large_" + id).dialog("open");
	}
</script>
