<?php

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", "Trying to access agent main list view");
	require ("general/noaccess.php");
	
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_clusters.php');
require_once ('enterprise/include/functions_clustermap.php');

$id_cluster = get_parameter('id',0);

$buttons['list'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster">' . 
    html_print_image("images/list.png", true, array ("title" => __('Clusters list'))) .'</a>');
    
    
$buttons['editor'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=godmode/reporting/cluster_builder.cluster_editor&id='.$id_cluster.'">' . 
    html_print_image("images/gm_setup.png", true, array ("title" => __('Cluster editor'))) .'</a>');
    
    
ui_print_page_header ( __("Cluster detail").' » '.clusters_get_name($id_cluster), "images/agent_mc.png", false, "agent_status", false, $buttons);

$font_size = 10;
$width = "100%";
$height = "500";
$node_radius = 40;
$baseurl = ui_get_full_url(false, false, false, false);

echo "<table style='width:100%;'>";
  echo "<tr>";

	$cluster_type = clusters_get_cluster_id_type($id_cluster);


	if($cluster_type[$id_cluster] == 'AP'){

    echo "<td style='border:1px solid black;min-width:900px;min-height:500px;vertical-align: top;width:55%;'>";
      
	}
	else{
		
		echo "<td style='border:1px solid black;min-width:1200px;min-height:500px;vertical-align: top;width:55%;'>";
		
	}
			
			echo "<div id='cluster_map' style='min-width:900px;width:100%;height:500px;'>";
			echo '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" pointer-events="all" width="' . $width . '" height="' . $height . 'px">';
		
			echo '</svg></div>';
    echo "</td>";

    echo "<td style='width:45%;min-width:390px;min-height:500px;vertical-align: top;'>";
		
      echo "<div style='width:50%;min-width:390px;float:left;'>";
        
        echo "<div style='float:left;width:100px;margin-left:25px;margin-top:25px;font-size:2em;text-align:center;'>".__('CLUSTER STATUS')."</div>";
				
				$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = (select id_agent from tcluster where id = '.$id_cluster.') and nombre = "Cluster status"');
				
				$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
				
				switch ($cluster_module_status) {
					case 1:
					
					echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
					
					<div class='status_animation' style='position:relative;width:100px;background-color:".COL_CRITICAL.";'></div>
					
					</div>";
					
						break;
					case 2:
					
					echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
					
					<div class='status_animation' style='position:relative;width:100px;background-color:".COL_WARNING.";'></div>
					
					</div>";
					
						break;
					case 4:
					
					echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
					
					<div class='status_animation' style='position:relative;width:100px;background-color:".COL_NOTINIT.";'></div>
					
					</div>";
					
						break;
					case 3:
					
					echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
					
					<div class='status_animation' style='position:relative;width:100px;background-color:".COL_UNKNOWN.";'></div>
					
					</div>";
					
						break;
					case 5:
					
					echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
					
					<div class='status_animation' style='position:relative;width:100px;background-color:".COL_NOTINIT.";'></div>
					
					</div>";
					
						break;
					case 0:
					
						echo "<div style='border:3px gray groove;float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;'>
						
						<div class='status_animation' style='position:relative;width:100px;background-color:".COL_NORMAL.";'></div>
						
						</div>";
					
						break;
						
					default:
					
						break;
				}
        
				
				
				
				if($cluster_type[$id_cluster] == 'AP'){
				
				
        echo "<div style='border:1px solid lightgray;float:left;min-width:350px;width:350px;margin-left:20px;margin-right:20px;margin-top:20px;height:200px;margin-bottom:20px;'>";
					
					echo "<div style='float:left;width:100%;height:25px;background-color:#373737;text-align:center;'><span style='color:#e7e9ea;display:block;margin:5px;font-size:1.5em;'>".__('Balanced modules')."</span></div>";
					
					echo "<div style='float:left;width:100%;height:175px;text-align:center;overflow-y:auto;overflow-x:hidden;'>";
					
						$balanced_modules_in = items_get_cluster_items_id_name($id_cluster,'AP');
						
						foreach ($balanced_modules_in as $key => $value) {
							$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$key);
							
							$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
														
							echo "<div style='float:left;margin-left:20px;margin-top:10px;width:330px;'>";
														
							if($cluster_module_status == 5){
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/exito.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							else{
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/error_1.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							
							echo '</div>';
							
						}
						
					echo "</div>";
					
				echo "</div>";
			
      echo "</div>";
			
		}
			
      
			echo "<div style='width:50%;min-width:390px;max-width:390px;float:left;'>";
			
			$last_update = db_process_sql('select timestamp from tagente_estado where id_agente_modulo = '.$cluster_module[0]['id_agente_modulo']);
      
			$last_update_value = $last_update[0]['timestamp'];
				
			echo "<div style='float:left;width:100px;px;margin-left:20px;margin-top:25px;font-size:2em;text-align:center;'>".__('LAST UPDATE')."</div>";
			echo "<div style='float:left;width:220px;margin-left:20px;margin-top:40px;font-size:1.5em;text-align:center;'>".$last_update_value."</div>";
        
        echo "<div style='border:1px solid lightgray;float:left;width:350px;margin-left:20px;margin-right:20px;margin-top:20px;height:200px;margin-bottom:20px;'>";
					
					echo "<div style='float:left;width:100%;height:25px;background-color:#373737;text-align:center;'><span style='color:#e7e9ea;display:block;margin:5px;font-size:1.5em;'>".__('Common modules')."</span></div>";
					
					echo "<div style='float:left;width:100%;height:175px;text-align:center;overflow-y:auto;overflow-x:hidden;'>";
					
						$modules_in = items_get_cluster_items_id_name($id_cluster,'AA');
						
						foreach ($modules_in as $key => $value) {
							$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$key);
							
							$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
														
							echo "<div style='float:left;margin-left:20px;margin-top:10px;width:330px;'>";
														
							if($cluster_module_status == 5){
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/exito.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							else{
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/error_1.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							
							echo '</div>';
							
						}
						
					echo "</div>";
					
				echo "</div>";
			
      echo "</div>";
			
			
			echo "<div style='width:100%;height:140px;min-width:390px;max-width:780px;float:left;margin-top:50px;'>";
			
			$id_agent = db_process_sql('select id_agent from tcluster where id = '.$id_cluster);
			
			$id_agent_value = $id_agent[0]['id_agent'];
			
			$table = new stdClass();
			$table->id = 'agent_details';
			$table->width = '100%';
			$table->cellspacing = 0;
			$table->cellpadding = 0;
			$table->class = 'agents';
			$table->style = array_fill(0, 3, 'vertical-align: top;');

			$data = array();
			$data[0][0] = html_print_table($table_agent, true);
			$data[0][0] .=
				'<br /> <table width=90% class="databox agente" style="margin-left:5%;">
					<tr><th>' .
						__('Events (24h)') .
					'</th></tr>' .
					'<tr><td style="text-align:center;padding-left:20px;padding-right:20px;"><br />' .
					graph_graphic_agentevents ($id_agent_value, 450, 40, SECONDS_1DAY, '', true, true) . 
					'<br /></td></tr>' . 
				'</table>';

			$table->style[0] = 'width:100%; vertical-align:top;';
			$data[0][1] = html_print_table($table_contact, true);
			$data[0][1] .= empty($table_data->data) ?
				'' :
				'<br>' . html_print_table($table_data, true);
			$data[0][1] .= !isset($table_incident) ?
				'' :
				'<br>' . html_print_table($table_incident, true);

			$table->rowspan[1][0] = 0;
			
			$table->data = $data;
			$table->rowclass[] = '';

			$table->cellstyle[1][0] = 'text-align:center;';

			html_print_table($table);
			$data2[1][0] = !isset($table_interface) ?
				'' :
				html_print_table($table_interface, true);
			$table->data = $data2;
			$table->styleTable = '';
			html_print_table($table);

			unset($table);
			
			echo "</div>";


    echo "</td>";

    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' style='min-width:400px;min-height:600px;vertical-align: top;'>";
	    echo "<div id='module_list' style='min-height:150px;margin-top:20px;'>";
			echo "</div>";
    echo "</td>";
  echo "</tr>";
echo "</table>";

$id_agent = db_process_sql('select id_agent from tcluster where id = '.$id_cluster);

$id_agent_value = $id_agent[0]['id_agent'];


ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');

echo "<div id='module_details_dialog' style='display: none;'></div>";

ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");


$layout = "fdp";

$graph = cluster_create_map($layout, $font_size, $zoom , $id_cluster);

if ($graph === false) {
	ui_print_error_message (__('Map could not be generated'));
	echo '<div class="nf">' . __('No agents found') . '</div>';
	return;
}

// Generate image and map
// If image was generated just a few minutes ago, then don't regenerate (it takes long) unless regen checkbox is set
$filename_map = safe_url_extraclean ($config["attachment_store"])."/vmware_map_" . $config['id_user'];
$filename_img = "attachment/vmware_map_" . $config['id_user'];
$filename_dot = safe_url_extraclean ($config["attachment_store"])."/vmware_map_" . $config['id_user'];

$filename_map .= ".map";
$filename_img .= ".png";
$filename_dot .= ".dot";

$filename_plain = sys_get_temp_dir() . "/plain.txt";

file_put_contents($filename_dot, $graph);

$cmd = "$layout -Tcmapx -o".$filename_map." -Tplain -o".$filename_plain." ".$filename_dot;

system ($cmd);

unlink($filename_dot);

$nodes = cluster_loadfile($filename_plain, $graph, $id_cluster);


foreach ($nodes['nodes'] as $key => $node) {
	if ($node['id'] !== '1') {
		$sql_AP = "SELECT am.nombre, am.id_agente_modulo, am.post_process, ae.datos, ae.estado FROM tagente_modulo am 
			INNER JOIN tagente_estado ae ON am.id_agente_modulo = ae.id_agente_modulo 
			WHERE nombre IN (SELECT name FROM tcluster_item WHERE id_cluster = $id_cluster AND item_type like 'AP') AND am.id_agente = $node[id_agent]";
		
		$modules_AP = db_get_all_rows_sql ($sql_AP);
		
		if ($modules_AP) {
			$nodes['nodes'][$key]['passive'] = 1;
		}
	}
}

?>
<script type="text/javascript">
	var controller_map = null;
	<?php
	echo "var nodes = " . json_encode($nodes['nodes']) . ";";
	echo "var arrows = " . json_encode($nodes['arrows']) . ";";
	echo "var id_cluster = " . json_encode($id_cluster) . ";";
	echo "var height = " . json_encode($height) . ";";
	echo "var node_radius = " . json_encode($node_radius) . ";";
	echo "var font_size = " . json_encode($font_size) . ";";
	echo "var homedir = " . json_encode($baseurl) . ";";
	?>
</script>
<?php

$requires_js = array();
$requires_js[] = "include/javascript/d3.3.5.14.js";
$requires_js[] = "enterprise/include/javascript/ClusterMapController.js";
$requires_js[] = "enterprise/include/javascript/tooltipster.bundle.min.js";
$requires_js[] = "include/javascript/jquery.svg.js";
$requires_js[] = "include/javascript/jquery.svgdom.js";

foreach ($requires_js as $js) {
	echo "<script type='text/javascript' src='$js'></script>" . "\n";
}

echo '<link rel="stylesheet" type="text/css" href="' . $baseurl . 'enterprise/include/styles/tooltipster.bundle.min.css" />' . "\n";

?>

<script>


function pagination_list_modules() {
	var parameters = {};
	
	parameters["list_modules"] = 1;
	parameters["id_agente"] = <?php echo $id_agent_value; ?>;
	parameters["filter_monitors"] = 0;
	parameters["monitors_change_filter"] = 0;
	parameters["page"] = "include/ajax/module";
	
	
	$("#module_list").empty();
	$("#module_list_loading").show();
	
	
	jQuery.ajax ({
		data: parameters,
		type: 'POST',
		url: "ajax.php",
		dataType: 'html',
		success: function (data) {
			$("#module_list_loading").hide();
			
			$("#module_list").empty();
			$("#module_list").html(data);
			$('.forced_title[alt=Force]').css('display','none');
		}
	});
}

$("a.tag_details").cluetip ({
		arrows: true,
		clickThrough: false,
		attribute: 'href',
		cluetipClass: 'default'
	});
$("a.relations_details").cluetip ({
		width: 500,
		arrows: true,
		clickThrough: false,
		attribute: 'href',
		cluetipClass: 'default',
		sticky: true,
		mouseOutClose: 'both',
		closeText: '<?php html_print_image("images/cancel.png") ?>'
	});
	
// Show the modal window of an module
function show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name) {
	var server_name = '';
	var extra_parameters = '';
	if ($('input[name=selection_mode]:checked').val()) {
		
		period = $('#period').val();
		
		var selection_mode = $('input[name=selection_mode]:checked').val();
		var date_from = $('#text-date_from').val();
		var time_from = $('#text-time_from').val();
		var date_to = $('#text-date_to').val();
		var time_to = $('#text-time_to').val();
		
		extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
	}

	// Get the free text in both options
	var freesearch = $('#text-freesearch').val();
	if (freesearch != null && freesearch !== '') {
		var free_checkbox = $('input[name=free_checkbox]:checked').val();
		extra_parameters += '&freesearch=' + freesearch;
		if (free_checkbox == 1) {
			extra_parameters += '&free_checkbox=1';
		} else {
			extra_parameters += '&free_checkbox=0';
		}
	}
	
	title = <?php echo "\"" . __("Module: ") . "\"" ?>;
	$.ajax({
		type: "POST",
		url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
		data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period + extra_parameters,
		dataType: "html",
		success: function(data) {
			$("#module_details_dialog").hide ()
				.empty ()
				.append (data)
				.dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					title: title + module_name,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 650,
					height: 500
				})
				.show ();
				refresh_pagination_callback (module_id, id_agent, "",module_name);
				datetime_picker_callback();
				forced_title_callback();
		}
	});
}
function datetime_picker_callback() {
	
	$("#text-time_from, #text-time_to").timepicker({
		showSecond: true,
		timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'});
		
	$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
	
	$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
}
datetime_picker_callback();

function refresh_pagination_callback (module_id, id_agent, server_name,module_name) {
	$(".binary_dialog").click( function() {
		
		var classes = $(this).attr('class');
		classes = classes.split(' ');
		var offset_class = classes[2];
		offset_class = offset_class.split('_');
		var offset = offset_class[1];
		
		var period = $('#period').val();
		
		show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name);
		return false;
	});
}


$(document).ready(function(){
	$('.tooltip').tooltipster();
	var controller = null
	$(function() {
		controller = new ClusterMapController("#cluster_map");
		
		controller.init_map();
	});
	pagination_list_modules();
	
	$( ".status_animation" ).css({'height':'5px','top':'0px'});
	
	$( ".status_animation" ).animate({
    top: "45px"
  }, 500, 'linear', function() {
		
		$( ".status_animation" ).css({'height':'5px','top':'0px'});
		
		$( ".status_animation" ).animate({
	    top: "45px"
	  }, 500, 'linear', function() {
			
			$( ".status_animation" ).css({'height':'0px','top':'25px'});
			
			$( ".status_animation" ).animate({
		    top: "0px",
				height: "50px"
		  }, 500, 'linear', function() {
			
		  });
			
	  });
		
	});

});

</script>