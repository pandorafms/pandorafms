<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
include ("../include/config.php");
include ("../include/functions.php");
include ("../include/functions_db.php");
require ("../include/languages/language_".$language_code.".php");

function dame_fecha($mh){ 
	// Return a MySQL timestamp date, formatted with actual date MINUS X minutes, given as parameter
	$m_year = date("Y", time()-$mh*60); 
	$m_month = date("m", time()-$mh*60);
	$m_day = date ("d", time()-$mh*60);
	$m_hour = date ("H", time()-$mh*60);
	$m_min = date ("i", time()-$mh*60);
	$m = $m_year."-".$m_month."-".$m_day." ".$m_hour.":".$m_min.":00";
	return $m;	
}

function dame_fecha_grafico($mh){ // Devuelve fecha formateada en funcion de un numero de minustos antes de la fecha actual

	// Date 24x7x30 hours ago (one month)
	$m_year = date("Y", time()-$mh*60);
	$m_month = date("m", time()-$mh*60);
	$m_day = date ("d", time()-$mh*60);
	$m_hour = date ("H", time()-$mh*60);
	$m_min = date ("i", time()-$mh*60);
	$m = $m_month."/".$m_day." ".$m_hour.":".$m_min;
	return $m;
}

function dame_fecha_grafico_timestamp ($timestamp) {  return date('d/m H:i', $timestamp); }

function mysql_date ($timestamp) { return date('Y-m-d H:i:s', $timestamp); } 

function mysql_time ($date) {  
	// strptime is only for PHP 5  >:/
	// chapuza va!

	$a1 = explode(" ", $date);
	$a2 = explode("-", $a1[0]);
	$a3 = explode(":", $a1[1]);
	
	return mktime ( $a3[0], 
				$a3[1],
				$a3[2], 
				$a2[1], 
				$a2[2],
				$a2[0] ); 
}

function dame_id_agente_agentemodulo($id_agente_modulo){
        //require("config.php");
        $query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo;
        $resq1=mysql_query($query1);
        if ($rowdup=mysql_fetch_array($resq1))
                return $rowdup["id_agente"];
        else
                return NULL;
        
}



function gms_get_table_id ($abc_o, $abc_f, $period, $type, $id_agente_modulo)  {

	// this function checks if the temporal table associated with the $hash
	// exists in the tmp_fgraph_tables table

$sql = "SELECT * from tmp_fgraph_tables where 
					id_agente_modulo='" . $id_agente_modulo . "'  and 
					type='" . $type . "' and 
					period='" . $period . "' ;";
	if (!$result=mysql_query($sql)){
		if ( mysql_error() == "Table 'pandora.tmp_fgraph_tables' doesn't exist"  ) { 
			// if the table does not exist, let's create it
			// but, first, we remove any other temporal table
			$result=mysql_query( "show tables;");
			 while ($row=mysql_fetch_row($result)) { 
				if (strpos($row[0], 'tmp_fgraph')!==FALSE) { mysql_query ('drop table ' . $row[0] . ';');  } 
			}
			
			// tmp_fgraph_tables should not be a memory table. It is now just for
			// development reasons, but should be included in the stable version
			// in pandoradb.sql
			mysql_query("
				CREATE TABLE `pandora`.`tmp_fgraph_tables` (
				`id` integer  NOT NULL  AUTO_INCREMENT,
				`abc_o` INTEGER UNSIGNED  NOT NULL ,
				`abc_f` INTEGER UNSIGNED  NOT NULL ,
				`period` INTEGER UNSIGNED  NOT NULL ,
				`type` integer  NOT NULL ,
				`id_agente_modulo` integer  NOT NULL ,
				`last_modification` INTEGER UNSIGNED NOT NULL,
				PRIMARY KEY(`id`)
				)
				ENGINE = MEMORY
				COMMENT = 'temporals tables';
				");
			// now, other temporal tables must be removed
			
			return 0;
		 }
	} else {
		$max_useful_abc=0;		// useful stored values to build this table
		$id_to_return=0;		// chosen table to be returned by this function
		while ($row=mysql_fetch_array($result) ) {
			// let's see if this table has info of the interval requested
			// That's when useful_abc > 0, where
			$useful_abc = min($abc_f, $row['abc_f']) - max($abc_o, $row['abc_o']);
			
			if ( $useful_abc < 0 )  {
				// in this case, the stored table is useless, so we can delete it if it has not been 
				// used recently (1 hour)
				/*if ( (time() - $row['last_modification']) > 3600 ) {
					mysql_query( " DELETE FROM tmp_fgraph_tables WHERE id='" . $row['id'] . "' ;
									DROP TABLE tmp_fgraph_" . $row['id'] . " ; " );
				}*/
			} else {
				// ok! this table has values that we can reuse to build the graph
				// but I'm going to remember it only if it is the best we have seen so far
				$id_to_return = ( $useful_abc > $max_useful_abc )?$row['id']:$id_to_return;
				$max_useful_abc = ( $useful_abc > $max_useful_abc )?$useful_abc:$max_useful_abc;
			}
		}
	}

// if there is no id_to_return, let's see if the tmp tables need to be flushed
// This happens when pandora startup and there are a lot of empty tmp tables that must be removed
// This must to be replaced by other more robust 'clean-up' function or service
if (!$id_to_return) {
	// is tmp_fgraph_tables empty?
	$result = mysql_query('select count(*) from tmp_fgraph_tables;');
	$row=mysql_fetch_array($result);
	if (!$row[0]) { 
			// yes?!?   let's flush!
			$result=mysql_query( "show tables;");
			 while ($row=mysql_fetch_row($result)) { 
				if (strpos($row[0], 'tmp_fgraph')!==FALSE and strpos($row[0], 'tables')===FALSE) 
						{ mysql_query ('drop table ' . $row[0] . ';');  } 
			}
	} 
}

return $id_to_return ;

}

function gms_create_tmp_table ($abc_o, $abc_f, $period, $type, $id_agente_modulo, $number_ord) {
	
	// creates the temporal table for the graph associated with $hash.
	// returns the id assigned in tmp_fgraph_tables
	// the table will have one column for abscises and $number_ord for ordenates
	// columns for ordenates.
	
	
	//  registers the table in tmp_fgraph_table
	// I skip to check the existance of tmp_fgraph_table in sake of performance
	$sql = "INSERT INTO tmp_fgraph_tables ( abc_o, abc_f, period, type, id_agente_modulo , last_modification)
			VALUES ('$abc_o', '$abc_f', '$period', '$type', '$id_agente_modulo', '" . time() . "') ; ";
	
	if (!mysql_query($sql)) { return 0; }
	
	 if ( !$id = gms_get_table_id ($abc_o, $abc_f, $period, $type, $id_agente_modulo) ) { return 0;}
	
	// creates the table
	$sql = "	CREATE TABLE `pandora`.`tmp_fgraph_" . $id ."` (
  		`abc` INTEGER UNSIGNED NOT NULL,";
  		
  	for ($cc=1; $cc<=($number_ord); $cc++) {
  		$sql = $sql . "`ord". $cc . "` FLOAT DEFAULT NULL ,";
  	}
  	
  	$sql = $sql . "PRIMARY KEY(`abc`)
				)
				ENGINE = MEMORY;
				";

	if (!mysql_query($sql)) { return 0; }

	return $id;

}


function &gms_load_table ($graph_id) {

	// takes a graph id as argument, loads the corresponding table from the data base
	// to memory and returns an array.
	
	$sql = "SELECT * FROM tmp_fgraph_$graph_id ORDER BY abc;";
	if ($result=mysql_query($sql)){
		$cc = 0;
		while ( $row=mysql_fetch_row($result) ) {
			$table[$cc++] = $row;
		}
	} else { return NULL ; }
	
	return $table;
}

function &gms_load_interval ($graph_id) {

	// takes a graph id as argument and returns the intervals (like gms_get_interval)
	
	$sql = "SELECT * FROM tmp_fgraph_tables where id=$graph_id;";

	if ($result=mysql_query($sql) and $row = mysql_fetch_array($result) ){

		$intervals =& gms_get_intervals ( $row['abc_o'], $row['abc_f'], $row['period'] );
		return $intervals;	
		
	} else {  return NULL; }
}

function &gms_get_intervals ( $abc_i, $abc_f, $abc_per ) {
	// given an initial, final and interval abcises, this function returns an array (by reference)
	// with the initial points of every interval, calculated in such a way that:
	//   abc_n = int( abc_i / abc_per ) + (n+1) abc_per

	if (!$abc_per) { return ; }  // Notice: Only variable references should be returned by reference
	$abc_0 = intval ( $abc_i / $abc_per ) * $abc_per ;
	$n_total = intval (($abc_f - $abc_i)/$abc_per) +1;
	for ($cc=0; $cc < $n_total; $cc++) {
		$result[$cc] = $abc_0 + $cc * $abc_per;
	}

	return $result;
}


function gms_generate_MAM ($id, $intervals_cc, $period, $abc_o_cc, $abc_f_cc, $tnow) {

				// this is specific of MAM graphs
				
				// periodicity of the module
				$module_period = give_moduleinterval($id);
				
				// get data of the period + 1 point before + 1 point after
				$sql = 	"(SELECT timestamp, datos FROM tagente_datos 
						WHERE id_agente_modulo='$id' AND
						timestamp < '" . mysql_date($intervals_cc) . "' 
						ORDER BY timestamp DESC LIMIT 1 ) " .
						" UNION " .
						"(SELECT timestamp, datos FROM tagente_datos 
						WHERE id_agente_modulo='$id' AND
						timestamp > '" . mysql_date($intervals_cc) . "' AND
						timestamp < '" . mysql_date( $intervals_cc + $period )  . "' ) " .
						"ORDER BY timestamp DESC; ";
				if ($result=mysql_query($sql) ) {	// if (!  ...   return ;
							// note that Pandora does not distinguish here between measuring the same
							// value and not measuring due to any error.
							// NOTE: should i check if the agent was ok? or I should suggest to use special values
							// like NULL to indicate a failure of the agent? ;
					
					unset($max, $min, $last_date);
					$avg = 0;
					$n_measures_total = 0;
					
					while ($row = mysql_fetch_array($result)) {
						
						$row_timestamp = mysql_time( $row['timestamp'] );
						$row_timestamp = ($row_timestamp<$abc_o_cc)?$abc_o_cc:$row_timestamp;
						$last_date = ( isset($last_date))?$last_date:min($abc_f_cc, $tnow);
						
						if ( $row_timestamp < $abc_f_cc ) {
							$incr_time = $last_date - $row_timestamp; 	// always >= 0
							$n_measures = ($incr_time / $module_period) ;  // yes, it is a float
							$n_measures_total += $n_measures;
							
							$max = ( isset($max) and $row['datos']<$max )?$max:$row['datos'];
							$min = ( isset ($min) and $min<$row['datos'] )?$min:$row['datos'];
							$avg += $row['datos'] * $n_measures ;  // divided by $n_measures_total later
						
							$last_date = $row_timestamp;
						}
					}
					$avg = ($n_measures_total>0)?($avg/$n_measures_total):NULL;
					// finito
					return (isset($max))?array ($abc_o_cc, $max, $avg, $min):NULL;
				}
				return NULL;
}


function grafico_modulo_sparse(		$label,				// label of the graph
					$id_agente_modulo,		// array with modules id to be represented
					$graph_type, 			// array type of graph to be represented
					$abc_o, $abc_int, 		// origin abcise of graph and abscise interval
									// $abc_f - $abc_o = $abc_int
					$period,			// resolution of abc
					$ord_o, $ord_int,		// origin ordenade and interval
					$zoom=1, $draw_events=0,	// zoom and events
					$transparency = 0		// transparency (=0 auto)
					)
{
					
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");

	define('GMD_PLOT_AREA', 0);
	define('GMD_PLOT_IMPULSE', 1);
	define('GMD_ALERTZONE', 2);
	define('GMD_PLOT_STACKED', 3);
	define('GMD_PLOT_BAND', 4);
	
	$MGD_data_label 	= array();  // declaring arrays for array_push to work properly
	$MGD_data_type 		= array();
	$MGD_data_color		= array();
	
	$tnow = time();
	$abc_f = $abc_o + $abc_int;
	$intervals =& gms_get_intervals($abc_o, $abc_f, $period);
	
	if (is_array($id_agente_modulo)) {
		// TODO feo
		$id_array = $id_agente_modulo;
		$type_array = $graph_type;
	} else {
		$id_array = array( $id_agente_modulo );
		$type_array = array( $graph_type );

		$agent_name = dame_nombre_agente_agentemodulo($id_agente_modulo);
		$module_name = dame_nombre_modulo_agentemodulo($id_agente_modulo);
		$label = "          $label - $agent_name / $module_name";
	}

	$color_array = (count($id_array)==1)?
				array('blue', 'orange', 'yellow')
				:
				array(	'blue', 'deepskyblue', 'paleturquoise',
					'darkorange', 'gold', 'khaki',
					'green', 'limegreen', 'palegreen',
					'red', 'indianred', 'lightsalmon'
					);
	
	if (!$transparency) { 
		$transparency = (count($id_array)==1)?'1':'0.5';
	}
	$transparency = '@' . $transparency;
	
	if ($draw_events) {
		for ($xx=0; $xx < count($id_array); $xx++) {
			$id = $id_array[$xx];
			$type = $type_array[$xx];
			
			// let's draw the alerts zones
			$sql1 = "SELECT dis_min, dis_max FROM talerta_agente_modulo WHERE id_agente_modulo = ".$id.";";
			if ($result=mysql_query($sql1)){
				while ($row=mysql_fetch_array($result)) {
					$MGD_data[ count($MGD_data) ][0] = $row["dis_min"];
					$MGD_data[ count($MGD_data) ][0] = $row["dis_max"];
					$MGD_data_color[ count($MGD_data_color) ] = $color_array[ ($xx * 3) % count($color_array) ] . '@0.2';
					$MGD_data_type[ count($MGD_data_type) ] = 2;
					$MGD_data_label[ count($MGD_data_label) ] = 'alert zone';
				}
			}
		}
	}

	for ($xx=0; $xx < count($id_array); $xx++) {

		$id = $id_array[$xx];
		$type = $type_array[$xx];
		
		$agent_name = dame_nombre_agente_agentemodulo($id_agente_modulo[$xx]);
		$module_name = dame_nombre_modulo_agentemodulo($id_agente_modulo[$xx]);
		
		if (! $graph_id[$id]  = gms_get_table_id ( $abc_o, $abc_f, $period, $graph_type, $id ) ) {
			$graph_id[$id] = gms_create_tmp_table($abc_o, $abc_f, $period, $graph_type, $id, '3');
			}
		
		$table =& gms_load_table ($graph_id[$id]);
		if ($table) {
			$table_n_ord = count($table[0]) -1 ;
			$table_intervals =& gms_load_interval ($graph_id[$id]);
			for ($cc=0; $cc < count($table); $cc++) { $table_xs[$cc] = $table[$cc][0]; }
		} 
			
		unset($xdata);
		unset($ydata);
				
		for ($cc=0; $cc < count($intervals); $cc++) {
			
			// limits of the interval cc. Remember that $cc=0 is the older
			$abc_o_cc = $intervals[$cc];
			$abc_f_cc =  $intervals[$cc] + $period;

			$pointer = count($xdata);
			$key = (isset($table_xs))?array_search($intervals[$cc], $table_xs):FALSE; 
			if ( ( ($key === FALSE)  or  $cc == (count($intervals)-1)) or !$table_xs  ) {
				if ($results = gms_generate_MAM ($id, $intervals[$cc], $period, $abc_o_cc, $abc_f_cc, $tnow) ) {
					$xdata[$pointer] =  $results[0] ; 
					for ($nn = 1; $nn < count($results) ; $nn++ ) {
						$ydata[$nn-1][$pointer] = $results[$nn] ;
					}
					$sql = "INSERT INTO tmp_fgraph_" . $graph_id[$id] . " VALUES ( " . $xdata[$pointer]  ;
						for ($nn = 0; $nn < count($ydata); $nn++) { 
							$sql .=  ", " . $ydata[$nn][$pointer] ;
						}
						$sql .=  " ) ";
						$sql .= " ON DUPLICATE KEY UPDATE  "  ;
						for ($nn = 0; $nn < count($ydata); $nn++) { 
							$sql .=  " ord" .  ($nn+1) . " = '" . $ydata[$nn][$pointer] . "'" ;
							$sql .= ( $nn == (count($ydata) -1 ))?"":", ";
						}
						$sql .=  " ; ";
					if (!$result=mysql_query($sql)){
					print mysql_error(); }
					
				} 
			} else {
				if ($table) {
					$xdata[$pointer] =  $table[$key][0] ;
					for ($nn = 1; $nn <= $table_n_ord ; $nn++ ) {
						$ydata[$nn-1][$pointer] = $table[$key][$nn] ;
					}
				}
			}
		}
		
		// TODO tendría que se ser de todos los $ydata !!
		$valor_maximo = max($ydata[0]);		

		for ($cc=0; $cc < count($ydata); $cc++) {
			$MGD_data[ count($MGD_data) ] = $xdata;
			$MGD_data[ count($MGD_data) ] = $ydata[$cc];
		}
		

		// colors
		if ( is_numeric( array_search($type, array( GMD_PLOT_AREA, GMD_PLOT_STACKED, GMD_PLOT_BAND ) ) ) ) {
		
			array_push ($MGD_data_color, 	$color_array[ ($xx * 3) 	% count($color_array) ] . $transparency,
							$color_array[ ($xx * 3 + 1) 	% count($color_array) ] . $transparency,
							$color_array[ ($xx * 3 + 2)	% count($color_array) ] . $transparency
							);	
		}
		

		// legends and type
		if ( is_numeric( array_search($type, array( GMD_PLOT_AREA, GMD_PLOT_STACKED ) ) ) ) {
			array_push ($MGD_data_label, "$module_name Max", "$module_name Avg", "$module_name Min");
			array_push ($MGD_data_type, $type, $type, $type);
		}
		
		if ( $type == GMD_PLOT_BAND ) {
			// ugly repeating three times
			array_push ($MGD_data_label, "$module_name", "$module_name", "$module_name");
			array_push ($MGD_data_type, $type, $type, $type);
		}
		
	}		

	// if there are some events to draw let's scatter them!
	if ($draw_events) {
		
		for ($xx=0; $xx < count($id_array); $xx++) {
			$id = $id_array[$xx];
			$type = $type_array[$xx];
			
			// TODO if $type = al que toca ...
			
			$module_name = dame_nombre_modulo_agentemodulo($id);
			
			// careful here! next sql sentence looks for the module by name in the "evento" field
			// tevento database table SHOULD have module_id !!
			$sql1 = "SELECT id_evento, timestamp FROM tevento WHERE id_agente = ". dame_agente_id($agent_name) ." and timestamp > '" .
					 mysql_date($abc_o) . "'  and timestamp < '" .  mysql_date($abc_f) . "' and evento like '%" . $module_name . "%' ".
					 "  order by timestamp ASC;";
					
			// we populate two arrays with validated and no validated events of the module
			if ($result=mysql_query($sql1)){
				$x_in = count($MGD_data);  // index for x data
				$y_in = $x_in + 1;
				$y_value = $valor_maximo/7 + $valor_maximo - ($valor_maximo /10 * $xx);
				while ($row=mysql_fetch_array($result)) {
					//array_push ( $MGD_data[$x_in], mysql_time($row['timestamp']) );
					//array_push ( $MGD_data[$y_in], $y_value );

					$MGD_data[$x_in][ count($MGD_data[$x_in]) ] = mysql_time( $row['timestamp'] );
					$MGD_data[$y_in][ count($MGD_data[$y_in]) ] = $y_value;
				}

				if (isset( $MGD_data[$x_in][0] )) {
					//array_push ( $MGD_data_color, 'green' );
					//array_push ( $MGD_data_type, 1 );
					//array_push ( $MGD_data_label, 'cambiar' );

					$MGD_data_color[ count($MGD_data_color) ] = $color_array[ ($xx * 3) % count($color_array) ] . '@0.5';
					$MGD_data_type[ count($MGD_data_type) ] = 1;
					$MGD_data_label[ count($MGD_data_label) ] = 'events';
				}
			}
		
		}
	}
	
/*	print "<hr>debug: <br><br>";

	for ($cc=0; $cc<count($MGD_data); $cc=$cc+2) {
		
		print "<br>SERIE: " . ($cc/2) . "<br>";
		
		for ($dd=0; $dd<count($MGD_data[$cc]); $dd++) {
			print $MGD_data[$cc][$dd]." - ".$MGD_data[$cc+1][$dd]."<br>";
		}
	
	}
*/

	
	$Graph_param = array (
		'title' => $label,
		'size_x'	=> 550 ,
		'size_y'	=> 220 ,
		'zoom'		=> $zoom,
		'id_agente_modulo' => $id ,
		'id_agente' => dame_agente_id($agent_name),
		'valor_maximo'	=> $valor_maximo ,
		'periodo'	=> $abc_int/60
		);

	modulo_grafico_draw ( 	$Graph_param, 
				$intervals,
				$MGD_data_label,
				$MGD_data_type,
				$MGD_data_color,
				$MGD_data, 
				$intervals[0],
				$intervals[count($intervals)-1] + $period
				); 
}
	
	
function modulo_grafico_draw( $MGD_param, $MGD_labels, $MGD_data_label, $MGD_data_type, $MGD_data_color, $MGD_data, $MGD_xo="", $MGD_xf="" ) {	
	
// draws the graph corresponding to the data of a module
	// arguments:
	
	// $MGD_param = array (
	//	'title' 	=> title ,
	//	'size_x'	=> size of the graphic ,
	//	'size_y'	=> ,
	//	'id_agente_modulo' => agent-module id ,
	//	'id_agente'	=> agent id ,
	// 	'valor_maximo'	=> maximum value for y axis ,
	//	'periodo'	=> interval ,
	//	);

	// $MGD_labels = array ( $etiq_base )    // labels in numeric timestamp format

	// $MGD_data_label = array ( name1, name2, ...  )    // name of the datasets (for the legend only)

	// $MGD_data = array ( array (xdata1), array(ydata1), array(xdata2), ... );	// data to be represented
	
	// $MGD_event_data = array ( (notvalidated) &array(data_x), (validated) => &array(data_x) );
		
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
	include 'Image/Graph.php';
		
	define('GMD_PLOT_AREA', 0);
	define('GMD_PLOT_IMPULSE', 1);
	define('GMD_ALERTZONE', 2);
	define('GMD_PLOT_STACKED', 3);
	define('GMD_PLOT_BAND', 4);
/*	
	print "<hr>debug: <br><br>";

	for ($cc=0; $cc<count($MGD_data); $cc=$cc+2) {
		
		print "<br>SERIE: " . ($cc/2) . "<br>";
		
		for ($dd=0; $dd<count($MGD_data[$cc]); $dd++) {
			print $MGD_data[$cc][$dd]." - ".$MGD_data[$cc+1][$dd]."<br>";
		}
	
	}
*/
	// initializing parameters
		
	if (!isset( $MGD_param['title'] )) { $MGD_param['title'] = '- no title -'; }
	if (!isset( $MGD_param['size_x'] )) { $MGD_param['size_x'] = 550; }
	if (!isset( $MGD_param['size_y'] )) { $MGD_param['size_y'] = 220; }
	
	$MGD_param['size_x'] = intval($MGD_param['size_x'] * $MGD_param['zoom']); 
	$MGD_param['size_y'] = intval($MGD_param['size_y'] * $MGD_param['zoom']);
	
	$count_datasets = count( $MGD_data_label );    // number of datasets to represent
		
	// creating the graph with PEAR Image_Graph
	$Graph =& Image_Graph::factory('graph', 
		array( 	$MGD_param['size_x'], 
			$MGD_param['size_y']
			)
		); 
	$Font =& $Graph->addNew('font', $config_fontpath);
	$base_fontsize = 4 + intval( 4 * $MGD_param['zoom'] );
	$Font->setSize( $base_fontsize );
	$Graph->setFont($Font);
	$Graph->add(
		Image_Graph::vertical(
		 	$Title = Image_Graph::factory('title', array ($MGD_param['title'] , $base_fontsize + 2 )),
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea','axis'),
				$Legend = Image_Graph::factory('legend'),
				80
				),
			5
			)
		);

	$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	
 	$Grid =& $Plotarea->addNew('line_grid', false, IMAGE_GRAPH_AXIS_X);
	$Grid->setBackgroundColor('silver@0.3');
	$Grid->setBorderColor('black');
	$Plotarea->addNew('line_grid', false, IMAGE_GRAPH_AXIS_Y); 
	// the next grid is only necessary for drawing the right black line of the PlotArea
	$Grid_sec =& $Plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY); 
	$Grid_sec->setBorderColor('black');
	
	$Legend->setPlotarea($Plotarea);
	
	// now, datasets are created ...
	for ($cc=0; $cc<$count_datasets; $cc++) {
		$Datasets[$cc] = Image_Graph::factory('dataset') ;
		$Datasets[$cc]->setName( $MGD_data_label[$cc] );
		
		// and populated with data ...
		if ( $MGD_data_type[$cc] == GMD_PLOT_BAND ) {
			for ($dd=0; $dd < count($MGD_data[$cc*2]); $dd++) {
				$Datasets[$cc]->addPoint( $MGD_data[$cc*2][$dd], 
								array('high' => $MGD_data[($cc*2)+1][$dd], 
									'low' => $MGD_data[($cc*2)+5][$dd]) );
			}
			$cc = $cc + 2;     // ugly!
		} else {
			for ($dd=0; $dd < count($MGD_data[$cc*2]); $dd++) {
				$Datasets[$cc]->addPoint($MGD_data[$cc*2][$dd], $MGD_data[($cc*2)+1][$dd]);
			}
		}
	}
	
	// ... and added to the Graph
	for ($cc=0; $cc < $count_datasets; $cc++) {
		// and the most important: the plots!
		switch ($MGD_data_type[$cc]) {
			case GMD_PLOT_AREA :
				$Plot =& $Plotarea->addNew('Image_Graph_Plot_Area', array($Datasets[$cc])); 
				$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
				$FillArray->addColor( $MGD_data_color[$cc] );
				$Plot->setFillStyle( $FillArray ); 
				break;
			case GMD_PLOT_BAND :
				$Plot =& $Plotarea->addNew('Image_Graph_Plot_Band', array($Datasets[$cc])); 
				$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
				$FillArray->addColor( $MGD_data_color[$cc] );
				$Plot->setFillStyle( $FillArray ); 
				$cc = $cc + 2;
				break;
			case GMD_PLOT_STACKED :
				// ugly piece of code follows ...
				$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
				$stacked_Datasets[ count($stacked_Datasets) ] = $Datasets[$cc];
				$FillArray->addColor( $MGD_data_color[$cc] );
				
				while ( $MGD_data_type[$cc+1] == GMD_PLOT_STACKED ) {
					$cc++;	// ugly
					$stacked_Datasets[ count($stacked_Datasets) ] = $Datasets[$cc];
					$FillArray->addColor( $MGD_data_color[$cc] );
				}
				
				$Plot =& $Plotarea->addNew('Image_Graph_Plot_Area', array($stacked_Datasets, 'stacked')); 
				$Plot->setFillStyle( $FillArray ); 
				break;
			case GMD_PLOT_IMPULSE :
				$Plot =& $Plotarea->addNew('Plot_Impulse', array($Datasets[$cc])); 
				$Plot->setLineColor( $MGD_data_color[$cc] ); 
				$Marker_event =& Image_Graph::factory('Image_Graph_Marker_Diamond');
				$Plot->setMarker($Marker_event);
				$Marker_event->setFillColor( $MGD_data_color[$cc] );
				$Marker_event->setLineColor( 'black' );
				break;
			case GMD_ALERTZONE :
				$Plot =& $Plotarea->addNew('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
				$Plot->setFillColor( $MGD_data_color[$cc] );
				$Plot->setLowerBound( $MGD_data[$cc*2][0] );
				$Plot->setUpperBound( $MGD_data[($cc*2)+1][0] );
				break;
			default:
				break;
		}
	}
	
	$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
	$AxisX->setLabelInterval($MGD_param['periodo']*60/10);
	if ($MGD_xf!="") { $AxisX->forceMaximum($MGD_xf); }
	if ($MGD_xo!="") { $AxisX->forceMinimum($MGD_xo);}
 	$AxisX->setFontAngle(45);
	$AxisX->setLabelOption('offset', 15 + intval( 20 * $MGD_param['zoom'] )); 
 	$AxisX->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'dame_fecha_grafico_timestamp')); 
	$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
	$AxisY->forceMaximum(ceil($MGD_param['valor_maximo'] / 4) + $MGD_param['valor_maximo'], false);
	
	$Graph->done();
	//$Graph->done(array('filename' => '/tmp/jarl.png'));
}



function graphic_agentmodules($id_agent) {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$sql1="SELECT * FROM ttipo_modulo";
	$result=mysql_query($sql1);
	$ax = 0;
	while ($row=mysql_fetch_array($result)){
		$data_label[$ax]=$row["nombre"]; 
		$data[$ax]=0;
		$data_id[$ax] = $row["id_tipo"];
		$ax++;
	}
	$cx=0;
	$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent;
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$cx++;
		for ($bx=0;$bx<=$ax;$bx++){
			if (isset($data_id[$bx])){
				if ($data_id[$bx] == $row["id_tipo_modulo"]){
					$data[$bx]++;
				}
			}
		}		
	}

	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
		if ($data[$a] > $mayor_data){
			$mayor = $a;
			$mayor_data = $data[$a];
		}
	$bx=0;
	for ($a=0;$a < sizeof($data_label); $a++)
		if ($data[$a] > 0){
			$data_label2[$bx] = $data_label[$a];
			$data2[$bx] = $data[$a];
			$bx++;
		}


	$graph = new PieGraph(280,120,"auto");
	// $graph->SetMarginColor('white@0.2');
	$graph->SetMargin(15,4,2,2); 
	$graph->SetMarginColor('#f5f5f5');
	$graph->img->SetCanvasColor('#f5f5f5');
	$graph->SetFrame(True,'#f5f5f5',0);
	$graph->SetAlphaBlending();	
	if ($cx > 1){
		$p1 = new PiePlot3D($data2);
		$p1->SetLegends($data_label2);
	} else {
		$data_void[]="1";
		$legend_void[]="N/A";
		$p1 = new PiePlot3D($data_void);
		$p1->SetLegends($legend_void);
	}
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.3);
	$p1->value->SetColor("#f5f5f5"); // Invisible 
	$graph->legend->SetAbsPos(5,5,'right','top');
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();	
}


function graphic_agentaccess($id_agent, $periodo){
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_line.php");
	require ("../include/languages/language_".$language_code.".php");
	$color ="#437722"; // Green pandora 1.1 octopus

	$agent_interval = give_agentinterval($id_agent);
	$intervalo = 30 * $config_graph_res; // Desired interval / range between dates
	$intervalo_real = (86400 / $agent_interval); // 60x60x24 secs
	if ($intervalo_real < $intervalo ) {
		$intervalo = $intervalo_real;
		
	}
	$fechatope = dame_fecha($periodo);
	$horasint = $periodo / $intervalo;

	// $intervalo now stores "ideal" interval			}
	// interval is the number of rows that will store data. more rows, more resolution

	// Para crear las graficas vamos a crear un array de Ax4 elementos, donde
	// A es el numero de posiciones diferentes en la grafica (30 para un mes, 7 para una semana, etc)
	// y los 4 valores en el ejeY serian los detallados a continuacion:
	// Rellenamos la tabla con un solo select, y los calculos se hacen todos sobre memoria
	// esto acelera el tiempo de calculo al maximo, aunque complica el algoritmo :-)
	
	// Creamos la tabla (array) con los valores para el grafico. Inicializacion
	for ($x = 0; $x <$intervalo; $x++) {
		$valores[$x][0] = 0; // [0] Valor (contador)
		$valores[$x][1] = 0; // [0] Valor (contador)
		$valores[$x][2] = dame_fecha($horasint * $x); // [2] Rango superior de fecha para ese rango
		$valores[$x][3] = dame_fecha($horasint*($x+1)); // [3] Rango inferior de fecha para ese rango
	}
	$sql1="SELECT * FROM tagent_access WHERE id_agent = ".$id_agent." and timestamp > '".$fechatope."'";

	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		for ($i = 0; $i < $intervalo; $i++){
			if (($row["timestamp"] < $valores[$i][2]) and ($row["timestamp"] >= $valores[$i][3]) ){ 
				// entra en esta fila
				$valores[$i][0]++;
			}
		} 
		
	}
	$valor_maximo = 0;
	for ($i = 0; $i < $intervalo; $i++) { // 30 entries in graph, one by day
		$grafica[]=$valores[$i][0];

	}

	// Create graph 
	$graph = new Graph(280,70);     
	$graph-> img-> SetImgFormat("gif");
	$graph->SetMargin(25,5,3,3); 
	$graph->SetScale("textlin",0,0,0,0);
	$graph->SetAlphaBlending(true);

        $graph->yaxis->HideTicks(false);
	$graph->xaxis->HideTicks(true);
	$graph->xaxis->HideLabels(true);
	$graph->yaxis->HideLabels(false);
	
	$graph->SetMarginColor('#f5f5f5');
	$graph->img->SetCanvasColor('#f5f5f5');
	$graph->SetFrame(True,'#f5f5f5',0);
		
	
	// Linea del eje Y de color
	// $graph->ygrid->SetFill(true,'#EFEFEF@0.6','#BBCCFF@0.6');
	// $graph->xgrid->Show();
	
	// Titulo guay
	//$graph->tabtitle->Set("Access Access");
	//$graph->xaxis->SetTickLabels("Que ostias");
	$graph->xaxis->SetFont(FF_FONT0);
	$graph->xaxis->SetLabelAngle(90);
	//$graph->xaxis->SetTextLabelInterval(ceil($intervalo / 10));
	$graph->yaxis->SetFont(FF_FONT0);
	// Creacion de la linea de datos

	
	$line1=new LinePlot($grafica);
	$line1->SetColor($color);
	$line1->SetWeight(1);
	$line1->SetFillColor($color."@0.2");
	//$line1->SetLegend($lang_label["med"]); 
	
	// Aï¿½dimos la linea a la imagen
	$line1->SetFillColor($color."@0.2");
	$graph->Add($line1);
	
	//$graph->legend->Pos(0.01,0.2,"right","center");
	
	// Lineas eje Y por encima del grafico
	//$graph->SetGridDepth(DEPTH_BACK);
	// Antialias
	//$graph->img->SetAntiAliasing();
	// Mostramos la imagen 
	$graph->Stroke();
}


function grafico_incidente_estados() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array(0,0,0,0);
	// 0 - Abierta / Sin notas
	// 2 - Descartada
	// 3 - Caducada 
	// 13 - Cerrada
	$sql1="SELECT * FROM tincidencia";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ($row["estado"] == 0)
			$data[0]=$data[0]+1;
		if ($row["estado"] == 2)
			$data[1]=$data[1]+1;
		if ($row["estado"] == 3)
			$data[2]=$data[2]+1;
		if ($row["estado"] == 13)
			$data[3]=$data[3]+1;
	}
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
	$graph = new PieGraph(370,180,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["incident_status"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->setShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
 	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.3);
	$legend = array ("Open Incident", "Closed Incident", "Outdated", "Invalid");
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_incidente_prioridad() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array(0,0,0,0,0,0);
	// 0 - Abierta / Sin notas
	// 2 - Descartada
	// 3 - Caducada 
	// 13 - Cerrada
	$sql1="SELECT * FROM tincidencia";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ($row["prioridad"] == 0)
			$data[0]=$data[0]+1;
		if ($row["prioridad"] == 1)
			$data[1]=$data[1]+1;
		if ($row["prioridad"] == 2)
			$data[2]=$data[2]+1;
		if ($row["prioridad"] == 3)
			$data[3]=$data[3]+1;
		if ($row["prioridad"] == 4)
			$data[4]=$data[4]+1;
		if ($row["prioridad"] == 10)
			$data[5]=$data[5]+1;
	}
		
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }


	$graph = new PieGraph(370,180,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["incident_priority"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
  	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.3);
	$legend = array ("Informative","Low","Medium","Serious", "Very serious", "Maintance");
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function graphic_incident_group() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

        $data = array();
        $legend = array();
        $sql1="SELECT distinct id_grupo FROM tincidencia ";
        $result=mysql_query($sql1);
        while ($row=mysql_fetch_array($result)){
                $sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE id_grupo = ".$row[0];
                $result2=mysql_query($sql1);
                $row2=mysql_fetch_array($result2);
                $data[] = $row2[0];
                $legend[] = dame_nombre_grupo($row[0])."(".$row2[0].")";
        }
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
        $mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++){
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
	}

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');        
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_group"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_incident_user() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct id_usuario FROM tincidencia ";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
			$sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE id_usuario = '".$row[0]."'";
			$result2=mysql_query($sql1);
			$row2=mysql_fetch_array($result2);
			$data[] = $row2[0];
			$legend[] = $row[0]."(".$row2[0].")";
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
        $mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++){
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
        }

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_user"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_user_activity() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT DISTINCT ID_usuario FROM tsesion ";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
	$entrada= entrada_limpia($row[0]);
			$sql1='SELECT COUNT(ID_usuario) FROM tsesion WHERE ID_usuario = "'.$entrada.'"';
			$result2=mysql_query($sql1);
			$row2=mysql_fetch_array($result2);
			$data[] = $row2[0];
			$legend[] = substr($row[0],0,16)."(".$row2[0].")";
	}

	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
		for ($b=$a; $b <sizeof($data); $b++)
		if ($data[$b] > $data[$a]){
			$temp = $data[$a];
			$temp_label = $legend[$a];
			$data[$a] = $data[$b];
			$legend[$a] = $legend[$b];
			$data[$b] = $temp;
			$legend[$b] = $temp_label;
		}
	}

        $mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++){
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
        }

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(500,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["users_statistics"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
	$graph->legend->Pos(0.05,0.49,"right","center");
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_incident_source() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

        $data = array();
        $legend = array();
        $sql1="SELECT DISTINCT origen FROM tincidencia";
        $result=mysql_query($sql1);
        while ($row=mysql_fetch_array($result)){
                $sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE origen = '".$row[0]."'";
                $result2=mysql_query($sql1);
                $row2=mysql_fetch_array($result2);
                $data[] = $row2[0];
                $legend[] = $row[0]."(".$row2[0].")";
        }
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
        $mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++){
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
        }

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_source"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}
function grafico_db_agentes_modulos() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_bar.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tagente";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_agente = ".$row["id_agente"];;
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		$data[] = $row2[0];
		$legend[] = $row["nombre"];
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }

	$ajuste_altura = sizeof($data) * 20;	
	//$graph = new PieGraph(400,140+$ajuste_altura,"auto");
	$graph = new Graph(400,140+$ajuste_altura,'auto');
	$graph->SetScale("textlin");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["modules_per_agent"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->scale->SetGrace(0);
	$graph->yaxis->SetLabelAlign('center','bottom');
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$graph->xaxis->SetLabelMargin(5);
	$graph->Set90AndMargin(100,20,50,30);
	$p1 = new BarPlot($data);
	$p1->value->SetFormat('%.0f ');
	$p1->value->Show();
	$p1->value->SetAlign('left','center');
	$p1->SetFillColor("#00bf00");
	$p1->SetWidth(0.6);
	$p1->SetShadow();
	$graph->yaxis->SetLabelFormat('%d');
	$graph->xaxis->SetTickLabels($legend);
	$graph->legend->Pos(0.05,0.49,"right","center");
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();

}

function grafico_eventos_usuario() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tusuario";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_evento) FROM tevento WHERE id_usuario = '".$row["id_usuario"]."'";
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		if ($row2[0] > 0){
			$data[] = $row2[0];
			$legend[] = $row["id_usuario"]." ( $row2[0] )";
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
	if ($data[$a] > $mayor_data){
   		$mayor = $a;
   		$mayor_data = $data[$a];
	}

	$ajuste_altura = sizeof($data) * 17;
	$graph = new PieGraph(430,170+$ajuste_altura,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["events_per_user"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.2);
	$p1->SetCenter(0.3);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_eventos_total() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$total = 0;
	
	$sql1="SELECT COUNT(id_evento) FROM tevento WHERE estado = 1 ";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$data[] = $row[0];
	$legend[] = "Revised ( $row[0] )";
	$total = $row[0];
	
	$sql1="SELECT COUNT(id_evento) FROM tevento WHERE estado = 0 ";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$data[] = $row[0];
	$total = $total + $row[0];
	$legend[] = "Not Revised ( $row[0] )";

	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
	$mayor=0; $mayor_data=0;
        for ($a=0;$a < sizeof($data); $a++)
        if ($data[$a] > $mayor_data){
                $mayor = $a;
                $mayor_data = $data[$a];
        }
	
	$graph = new PieGraph(430,200,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["event_total"]." ( $total )");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
 	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.28);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_eventos_grupo() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tgrupo";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_evento) fROM tevento WHERE id_grupo = ".$row["id_grupo"];
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		if ($row2[0] > 0){
			$data[] = $row2[0];
			$legend[] = $row["nombre"]." ( $row2[0] )";
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        } 
        $mayor=0; $mayor_data=0;
        for ($a=0;$a < sizeof($data); $a++)
        if ($data[$a] > $mayor_data){
                $mayor = $a;
                $mayor_data = $data[$a];
        }
	$total_grupos = sizeof($data);
	$ajuste_altura = $total_grupos * 10;
	
	$graph = new PieGraph(430,150+$ajuste_altura,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["events_per_group"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.35);
	$p1->SetCenter(0.28);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_db_agentes_paquetes() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_bar.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct (id_agente) FROM tagente_datos";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if (! is_null($row["id_agente"])){
			$sql1="SELECT COUNT(id_agente) FROM tagente_datos WHERE id_agente = ".$row["id_agente"];
			$result3=mysql_query($sql1);
			if ($row3=mysql_fetch_array($result3)){
				$data[]= $row3[0];
				$legend[] = dame_nombre_agente($row[0]);
			}
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }	

        $ajuste_altura = sizeof($data) * 20;
        $graph = new Graph(400,140+$ajuste_altura,'auto');
        $graph->SetScale("textlin");
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["packets_by_agent"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->yaxis->scale->SetGrace(0);
        $graph->yaxis->SetLabelAlign('center','bottom');
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $graph->xaxis->SetLabelMargin(5);
        $graph->Set90AndMargin(100,20,50,30);
        $p1 = new BarPlot($data);
	$p1->value->SetFormat('%.0f ');
        $p1->value->Show();
        $p1->value->SetAlign('left','center');
        $p1->SetFillColor("#0000fd");
        $p1->SetWidth(0.6);
        $p1->SetShadow();
        $graph->yaxis->SetLabelFormat('%d');
        $graph->xaxis->SetTickLabels($legend);
        $graph->legend->Pos(0.05,0.49,"right","center");
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function grafico_db_agentes_purge($id_agente) {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

	// All data (now)
	$purge_all=date("Y-m-d H:i:s",time());
		
	// 1 day
	$d1_year = date("Y", time()-28800);
	$d1_month = date("m", time()-28800);
	$d1_day = date ("d", time()-28800);
	$d1_hour = date ("H", time()-28800);
	$minuto = date("i",time());
	$segundo = date("s",time());
	$d1 = $d1_year."-".$d1_month."-".$d1_day." ".$d1_hour.":".$minuto.":".$segundo."";
	
	// 3 days
	$d3_year = date("Y", time()-86400);
	$d3_month = date("m", time()-86400);
	$d3_day = date ("d", time()-86400);
	$d3_hour = date ("H", time()-86400);
	$d3 = $d3_year."-".$d3_month."-".$d3_day." ".$d3_hour.":".$minuto.":".$segundo."";
	
	// Fecha 24x7 Horas (una semana)
	$week_year = date("Y", time()-604800);
	$week_month = date("m", time()-604800);
	$week_day = date ("d", time()-604800);
	$week_hour = date ("H", time()-604800);
	$d7 = $week_year."-".$week_month."-".$week_day." ".$week_hour.":".$minuto.":".$segundo."";
	
	// Fecha 24x7x2 Horas (dos semanas)
	$week2_year = date("Y", time()-1209600);
	$week2_month = date("m", time()-1209600);
	$week2_day = date ("d", time()-1209600);
	$week2_hour = date ("H", time()-1209600);
	$d14 = $week2_year."-".$week2_month."-".$week2_day." ".$week2_hour.":".$minuto.":".$segundo."";
		
	// Fecha de hace 24x7x30 Horas (un mes)
	$month_year = date("Y", time()-2592000);
	$month_month = date("m", time()-2592000);
	$month_day = date ("d", time()-2592000);
	$month_hour = date ("H", time()-2592000);
	$d30 = $month_year."-".$month_month."-".$month_day." ".$month_hour.":".$minuto.":".$segundo."";
	
	// Three months
	$month3_year = date("Y", time()-7257600);
	$month3_month = date("m", time()-7257600);
	$month3_day = date ("d", time()-7257600);
	$month3_hour = date ("H", time()-7257600);
	$d90 = $month3_year."-".$month3_month."-".$month3_day." ".$month3_hour.":".$minuto.":".$segundo."";
	
	$data = array();
	$legend = array();

	$fechas= array($d90, $d30, $d7, $d1);
	$fechas_label = array("> 30 days","7-30 days","2-7 days","24Hr");

	// Calc. total packets
        $sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos";;
        $result2=mysql_query($sql1);
        $row2=mysql_fetch_array($result2);
        $total = $row2[0];

	for ($a=0;$a<sizeof($fechas);$a++){	// 4 x intervals will be enought, increase if your database is very very quickly :)
		if ($a==3)
			$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE timestamp >= '".$fechas[$a]."' ";
		else
			$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE timestamp >= '".$fechas[$a]."' AND timestamp < '".$fechas[$a+1]."' ";
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$data[] = $row[0];
		$legend[]=$fechas_label[$a]." ( ".$row[0]." )";
	}

	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
        // or much better, pay me to do a special version for you, highly optimized :-))))
        for ($a=0;$a < sizeof($data);$a++){
                for ($b=$a; $b <sizeof($data); $b++)
                if ($data[$b] > $data[$a]){
                        $temp = $data[$a];
                        $temp_label = $legend[$a];
                        $data[$a] = $data[$b];
                        $legend[$a] = $legend[$b];
                        $data[$b] = $temp;
                        $legend[$b] = $temp_label;
                }
        }
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
		if ($data[$a] > $mayor_data){
			$mayor = $a;
			$mayor_data = $data[$a];
		}
	
	$graph = new PieGraph(500,200,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["packets_by_date"]." ( Tot - $total ) ");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.35);
	$p1->SetCenter(0.3);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function drawWarning($width,$height) {
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
	if ($width == 0) {
		$width = 50;
	}
	if ($height == 0) {
		$height = 30;
	}
	
	
	$image = imagecreate($width,$height);
	//colors
	$back = ImageColorAllocate($image,255,255,255);
	$border = ImageColorAllocate($image,0,0,0);
	$red = ImageColorAllocate($image,255,60,75);
	$fill = ImageColorAllocate($image,44,81,150);

	ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
	ImageRectangle($image,0,0,$width-1,$height-1,$border);
	ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config_fontpath, $lang_label["no_data"]);
	imagePNG($image);
	imagedestroy($image);
}

function progress_bar($progress,$width,$height) {
   // Copied from the PHP manual:
   // http://us3.php.net/manual/en/function.imagefilledrectangle.php
   // With some adds from sdonie at lgc dot com
   // Get from official documentation PHP.net website. Thanks guys :-)
   // Code ripped from Babel Project :-)
   function drawRating($rating,$width,$height) {
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
	if ($width == 0) {
		$width = 150;
	}
	if ($height == 0) {
		$height = 20;
	}
	
	//$rating = $_GET['rating'];
	$ratingbar = (($rating/100)*$width)-2;
	
	$image = imagecreate($width,$height);
	//colors
	$back = ImageColorAllocate($image,255,255,255);
	$border = ImageColorAllocate($image,0,0,0);
	$red = ImageColorAllocate($image,255,60,75);
	$fill = ImageColorAllocate($image,44,81,150);

	ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
	ImageRectangle($image,0,0,$width-1,$height-1,$border);


	if (($rating > 100) || ($rating < 0)){
		ImageFilledRectangle($image,1,1,$width-1,$height-1,$red);
		ImageTTFText($image, 8, 0, ($width/3)-($width/10), ($height/2)+($height/5), $back, $config_fontpath,$lang_label["out_of_limits"]);
	}
	else {
		ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$fill);
		if ($rating > 50) 
				ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $back, $config_fontpath, $rating."%");
		else 
			ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config_fontpath, $rating."%");
	}
	imagePNG($image);
	imagedestroy($image);
   }
   Header("Content-type: image/png");
   drawRating($progress,$width,$height);
}



// *****************************************************************************************************************
//   MAIN Code
//   parse get parameters
// *****************************************************************************************************************

$ahora = time(); 

if (isset($_GET["tipo"])){
	if ($_GET["tipo"]=="sparse"){
		if (isset($_GET["id"]) and   (isset($_GET["label"])) and ( isset($_GET["periodo"])) and (isset ($_GET["intervalo"])) AND (isset ($_GET["color"])) ){
			$id = $_GET["id"];
			$color = $_GET["color"];
			$tipo = $_GET["tipo"];
			$periodo = $_GET["periodo"];
			$intervalo = $_GET["intervalo"];
			$label = $_GET["label"];
			$color = "#".$color;
			if ( isset($_GET["draw_events"]) and $_GET["draw_events"]==0 ) 
				{ $draw_events = 0; } else { $draw_events = 1; } 
			if (isset($_GET['zoom']) and is_numeric($_GET['zoom']) and $_GET['zoom']>100) {
				$zoom = $_GET['zoom'] / 100 ;
			} else { $zoom = 1; } 
			// grafico_modulo_sparse($id, $periodo, $intervalo, $label, $color, $zoom, $draw_events)
//			print "periodo: $periodo, intervalo: $intervalo<br>";

			grafico_modulo_sparse(	$label, 				// label of the graph
						$id_agente_modulo = $id,			// array with modules id to be represented
						$graph_type= 0, 				// type of graph to be represented
						$abc_o=($ahora-($periodo*60)), $abc_int=$periodo*60, 			// origin abcise of graph and abscise interval
															// $abc_f - $abc_o = $abc_int,
						$period=ceil($abc_int/$intervalo),						// resolution of abc
						$ord_o=0, $ord_int=100,				// origin ordenade and interval
						$zoom, $draw_events,
						$transparency = 0);
		}
	}
	elseif ($_GET["tipo"] =="estado_incidente") 
		grafico_incidente_estados();	
	elseif ($_GET["tipo"] =="prioridad_incidente") 
		grafico_incidente_prioridad();	
	elseif ($_GET["tipo"]=="db_agente_modulo")
		grafico_db_agentes_modulos();
	elseif ($_GET["tipo"]=="db_agente_paquetes")
		grafico_db_agentes_paquetes();
	elseif ($_GET["tipo"] =="db_agente_purge")
		grafico_db_agentes_purge(-1);
	elseif ($_GET["tipo"] =="group_events")
		grafico_eventos_grupo();
	elseif ($_GET["tipo"] =="user_events")
		grafico_eventos_usuario();
	elseif ($_GET["tipo"] =="total_events")
		grafico_eventos_total();
	elseif ($_GET["tipo"] =="group_incident")
		graphic_incident_group();
	elseif ($_GET["tipo"] =="user_incident")
                graphic_incident_user();
	elseif ($_GET["tipo"] =="source_incident")
                graphic_incident_source();
	elseif ($_GET["tipo"] =="user_activity")
                graphic_user_activity();
	elseif ($_GET["tipo"] == "agentaccess")
		graphic_agentaccess($_GET["id"], $_GET["periodo"]);
	elseif ($_GET["tipo"] == "agentmodules")
		graphic_agentmodules($_GET["id"]);
	elseif ( $_GET["tipo"] =="progress"){
		$percent= $_GET["percent"];
		$width= $_GET["width"];
		$height= $_GET["height"];
		progress_bar($percent,$width,$height);
	} 
} 



