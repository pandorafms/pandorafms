<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once("include/functions_graph.php");
include_once("include/functions_ui.php");
include_once("include/functions_netflow.php");
ui_require_javascript_file ('calendar');

check_login ();

if (! check_acl ($config["id_user"], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

function exec_command ($start_date, $end_date, $command, $show){
	$command .= ' -t '.$start_date.'-'.$end_date.' -N';
	
	$values = array();
	exec($command, $string);

	$i = 0;
	if(isset($string) && is_array($string)&&($string!=null)){
		foreach($string as $line){
			$line = preg_replace('/\s+/',' ',$line);
			
			$val = explode(' ',$line);

			$values[$i]['date'] = $val[0];
			$values[$i]['time'] = $val[1];
			$values[$i]['duration'] = $val[2];
			$values[$i]['proto'] = $val[3];
			$values[$i]['srcip:port'] = $val[4];
			$val2 = explode(':', $val[4]);
			$values[$i]['srcip'] = $val2[0];
			// campo para mostrar grafica de tarta
			$values[$i]['agg'] = $val2[0];
			$values[$i]['srcport'] = $val2[1];
			$values[$i]['dstip:port'] = $val[6];
			$val2 = explode(':', $val[6]);
			$values[$i]['dstip'] = $val2[0];
			$values[$i]['dstport'] = $val2[1];
			
			switch ($show){
				case "packets":
					$values[$i]['data'] = $val[7];
					break;
				case "bytes":
					$values[$i]['data'] = $val[8];
					break;
				case "flows":
					$values[$i]['data'] = $val[9];
					break;
			}
			$i++;
		}
		return $values;
	}
}

function exec_command_aggregate ($start_date, $end_date, $command, $show){
	$command .= ' -t '.$start_date.'-'.$end_date.' -N';
	
	$values = array();
	exec($command, $string);

	$i = 0;
	if(isset($string) && is_array($string)&&($string!=null)){
		foreach($string as $line){
			if ($line=='')
				break;
			$line = preg_replace('/\s+/',' ',$line);
			$val = explode(' ',$line);

			$values[$i]['date'] = $val[0];
			$values[$i]['time'] = $val[1];
			
			//create field to sort array
			$date = $val[0];
			$time = $val[1];
			$date_time = strtotime ($date." ".$time);
			$values[$i]['datetime'] = $date_time;
			///
			$values[$i]['duration'] = $val[2];
			$values[$i]['proto'] = $val[3];
			$values[$i]['agg'] = $val[4];
			
			switch ($show){
				case "packets":
					$val[7]= str_replace('(','',$val[7]);
					$val[7]= str_replace(')','',$val[7]);
					$values[$i]['data'] = $val[7];
					break;
				case "bytes":
					$val[9]= str_replace('(','',$val[9]);
					$val[9]= str_replace(')','',$val[9]);
					$values[$i]['data'] = $val[9];
					break;
				case "bps":
					$values[$i]['data'] = $val[12];
					break;
				case "bpp":
					$values[$i]['data'] = $val[13];
					break;
			}	
			$i++;
		}
		return $values;
	}
}

$id = get_parameter('id');
$period = get_parameter('period', '86400');
$date_= get_parameter('date', date ("Y/m/d", get_system_time ()));
$time_= get_parameter('time', date ("H:i:s", get_system_time ()));
$report_name = db_get_value('id_name', 'tnetflow_report', 'id_report', $id);

$time_format = 'Y/m/d.H:i:s';

$update_date = get_parameter('update_date',0);

$buttons['report_list'] = '<a href="index.php?sec=netf&sec2=operation/netflow/nf_reporting">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
		. '</a>';
		
//Header
ui_print_page_header (__('Netflow'), "images/networkmap/so_cisco_new.png", false, "", false, $buttons);

echo"<h4>".__('Filter graph')."</h4>";

echo '<form method="post" action="index.php?sec=netf&sec2=operation/netflow/nf_view&amp;id='.$id.'">';

	$table->width = '60%';
	$table->border = 0;
	$table->cellspacing = 3;
	$table->cellpadding = 5;
	$table->class = "databox_color";
	$table->style[0] = 'vertical-align: top;';

	$table->data = array ();

	$table->data[0][0] = '<b>'.__('Date').'</b>';

	$table->data[0][1] = html_print_input_text ('date', $date_, false, 10, 10, true);
	$table->data[0][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-date'),this);"));
	$table->data[0][1] .= html_print_input_text ('time', $time_, false, 10, 5, true);

	$table->data[1][0] = '<b>'.__('Interval').'</b>';
		$values_period = array ('600' => __('10 mins'),
			'900' => __('15 mins'),
			'1800' => __('30 mins'),
			'3600' => __('1 hour'),
			'7200' => __('2 hours'),
			'18000' => __('5 hours'),
			'43200' => __('12 hours'),
			'86400' => __('1 day'),
			'172800' => __('2 days'),
			'432000' => __('5 days'),
			'1296000' => __('15 days'),
			'604800' => __('Last week'),
			'2592000' => __('Last month'),
			'5184000' => __('2 months'),
			'7776000' => __('3 months'),
			'15552000' => __('6 months'),
			'31104000' => __('Last year'),
			'62208000' => __('2 years')
					);
	$table->data[1][1] = html_print_select ($values_period, 'period', $period, '', '', 0, true, false, false);
	html_print_table ($table);

	echo '<div class="action-buttons" style="width:60%;">';
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	html_print_input_hidden ('update_date', 1);
	echo '</div>';
echo'</form>';


if ($id!=''){
	echo"<h3>$report_name</h3>";

	$sql1 = "select id_rc from tnetflow_report_content where id_report='".$id."'";
	$all_rcs = db_get_all_rows_sql($sql1);

	$x = 0;
	while(isset($all_rcs[$x]['id_rc'])) {
		$rc = $all_rcs[$x]['id_rc'];
		$sql = "SELECT * FROM tnetflow_report_content WHERE id_rc='".$rc."'";

		$content_report = db_get_row_sql($sql);
		$name_filter = $content_report['id_filter'];
		$max_val= $content_report['max'];
		$element = $content_report['show_graph'];

		if($update_date){
			$date = get_parameter_post ('date');
			$time = get_parameter_post ('time');
			$interval = get_parameter('period','86400');
		} else {
			$date = date ("Y/m/d", get_system_time ());
			$time = date ("H:i:s", get_system_time ());
			$interval ='86400';
		}
		$date = strtotime ($date." ".$time);
		$date_time = date($time_format, $date);
		$limit = $date - $interval;

		$date_limit = date ($time_format, $limit);

		$sql = "SELECT * FROM tnetflow_filter WHERE id_name = '".$name_filter."'";
		$result = db_get_row_sql($sql,false,true);

		$assign_group = $result['group'];
		$ip_dst = $result['ip_dst'];
		$ip_src = $result['ip_src'];
		$dst_port = $result['dst_port'];
		$src_port = $result['src_port'];
		$aggregate = $result['aggregate'];
		$show_packets = $result['show_packets'];
		$show_bytes = $result['show_bytes'];
		$show_bps = $result['show_bps'];
		$show_bpp = $result['show_bpp'];
	
		if(isset($ip_dst)){
			$val_ipdst = explode(',',$ip_dst);
			$count_ipdst = count($val_ipdst);
		}
		if(isset($ip_src)){
			$val_ipsrc = explode(',',$ip_src);
			$count_ipsrc = count($val_ipsrc);
		}
		if(isset($dst_port)&&($dst_port!='0')){
			$val_dstport = explode(',',$dst_port);
			$count_dstport = count($val_dstport);
		}
		if(isset($src_port)&&($src_port!='0')){
			$val_srcport = explode(',',$src_port);
			$count_srcport = count($val_srcport);
		}

//// Build command line
		$command = 'nfdump -q';

		if (isset($config['netflow_path']))
			$command .= ' -R '.$config['netflow_path'];
		
		if (isset($aggregate)&&($aggregate!='none')){
			$command .= ' -s '.$aggregate;
			if (isset($max_val))
				$command .= ' -n '.$max_val;
		}

	//filter options
		if (isset($ip_dst)&&($ip_dst!='')){
			$command .= ' "';
			for($i=0;$i<$count_ipdst;$i++){
				if ($i==0)
					$command .= 'dst ip '.$val_ipdst[$i];
				else
					$command .= ' or dst ip '.$val_ipdst[$i];
			}
			if (isset($ip_src)&&($ip_src!='')){
				$command .= ' and (';

				for($i=0;$i<$count_ipsrc;$i++){
					if ($i==0)
						$command .= 'src ip '.$val_ipsrc[$i];
					else
						$command .= ' or src ip '.$val_ipsrc[$i];
				}
				$command .= ')';
			}
			if (isset($dst_port)&&($dst_port!='')&&($dst_port!='0')){
				$command .= ' and (';
				for($i=0;$i<$count_dstport;$i++){
					if ($i==0)
						$command .= 'dst port '. $val_dstport[$i];
					else
						$command .= ' or dst port '.$val_dstport[$i];
				}
				$command .= ')';
			}
			if (isset($src_port)&&($src_port!='')&&($src_port!='0')){
				$command .= ' and (';
				for($i=0;$i<$count_srcport;$i++){
					if ($i==0)
						$command .= 'src port '. $val_srcport[$i];
					else
						$command .= ' or src port '.$val_srcport[$i];
				}
				$command .= ')';
			}
		$command .= '"';
		
		} else if (isset($ip_src)&&($ip_src!='')){
			$command .= ' "';
			for($i=0;$i<$count_ipsrc;$i++){
				if ($i==0)
					$command .= 'src ip '.$val_ipsrc[$i];
				else
					$command .= ' or src ip '.$val_ipsrc[$i];
			}
			if (isset($dst_port)&&($dst_port!='')&&($dst_port!='0')){
				$command .= ' and (';
				for($i=0;$i<$count_dstport;$i++){
					if ($i==0)
						$command .= 'dst port '. $val_dstport[$i];
					else
						$command .= ' or dst port '.$val_dstport[$i];
				}
				$command .= ')';
			}
			if (isset($src_port)&&($src_port!='')&&($src_port!='0')){
				$command .= ' and (';
				for($i=0;$i<$count_srcport;$i++){
					if ($i==0)
						$command .= 'src port '. $val_srcport[$i];
					else
						$command .= ' or src port '.$val_srcport[$i];
				}
				$command .= ')';
			} else {
				$command .= '"'; 
			}

	} else if (isset($dst_port)&&($dst_port!='')&&($dst_port!='0')){
			$command .= ' "';
			for($i=0;$i<$count_dstport;$i++){
			if ($i==0)
				$command .= 'dst port '.$val_dstport[$i];
			else
				$command .= ' or dst port '.$val_dstport[$i];
			}
			if (isset($src_port)&&($src_port!='')&&($src_port!='0')){
				$command .= ' and (';
				for($i=0;$i<$count_srcport;$i++){
					if ($i==0)
						$command .= 'src port '. $val_srcport[$i];
					else
						$command .= ' or src port '.$val_srcport[$i];
				}
				$command .= ')';
			} else {
				$command .= '"'; 
			}
			
	} else {
		if (isset($src_port)&&($src_port!='')&&($src_port!='0')){
				$command .= ' "(';
			for($i=0;$i<$count_ipdst;$i++){
				if ($i==0)
					$command .= 'dst ip '.$val_ipdst[$i];
				else
					$command .= ' or dst ip '.$val_ipdst[$i];
			}
			$command .= ' )"';
		}
	}

	if ($show_packets)
		$show = 'packets';
	if ($show_bytes)
		$show = 'bytes';
	if ($show_bps)
		$show = 'bps';
	if ($show_bpp)
		$show = 'bpp';

	//create interval to divide command execution
	$inter = $config['graph_res'] * 100;
	if ($aggregate!='none')
		$inter = 1;

	$fecha_limite = date ($time_format, $limit);
	$res = $interval/$inter;

	// Data iterator
	$j = 0;
	$values = array();
			
	// Calculate interval date
	for ($i = 0; $i < $inter; $i++) {
		$timestamp = $limit + ($res * $i);
		$timestamp_short = date($time_format, $timestamp);
		
		$end_date = $timestamp + $res;
		$end = date ($time_format, $end_date);

		if($aggregate!='none'){
			$result = exec_command_aggregate($timestamp_short, $end, $command, $show);
			$result = orderMultiDimensionalArray($result, 'datetime');
		} else {
			$result = exec_command($timestamp_short, $end, $command, $show);
		}

		$total = 0;
		$count = 0;

	if(!empty($result)){
		foreach($result as $data){
			$dates = $data['date'];
			$times = $data['time'];
			$total += $data['data'];	
			$count++;
		}
			$values[$j]['date'] = $dates;
			$values[$j]['time'] = $times;

			if ($count > 0) {
				$values[$j]['data'] = $total / $count;
				$var = $values[$j]['data'];
			} else {
				$values[$j]['data'] = 0;
			}
			$j++;
		}			
	}
		if($aggregate!='none'){
			switch ($element){
				case '0':
					echo grafico_netflow_aggregate_area($result, $interval, 880, 540, '', '','','',$date);
					break;
				case '1':
					echo grafico_netflow_aggregate_pie($result);
					break;
				case '2':
					echo netflow_show_table_values($result, $date_limit, $date_time);
					break;
				case '3':
					echo netflow_show_total_period($result, $date_limit, $date_time);
					break;
			}
		}else{
			switch ($element){
				case '0':
					echo grafico_netflow_total_area($values, $interval, 660, 320, '', '','','',$date);
					break;
			}
		}
		$x++;
	}
}

?>


