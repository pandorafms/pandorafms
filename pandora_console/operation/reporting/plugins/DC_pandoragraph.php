<?php

function &DC_pandoragraph_calculate ( &$params ) {
                 	
	// translating key words
	global $time_key_words;
		
	if ( array_key_exists($params['sts'], $time_key_words) ) { 
		$periodo = $time_key_words[$params['sts']];
		$origin = mysql_time ( $params['ets'] ) - $periodo;
	} elseif ( array_key_exists($params['ets'], $time_key_words) ) {
		$periodo = $time_key_words[$params['ets']];
		$origin = mysql_time ( $params['sts'] );
	} else {
		$origin = mysql_time ( $params['sts'] );
		$periodo = mysql_time ( $params['ets'] ) - $origin;
	}
	
	// getting agent-module id
	$id_agent_module = dame_agente_modulo_id_names(	$params['agentname'], 
							$params['modulename']);

	// tipo = sparse
	if (isset($id_agent_module) and   (isset($params["label"])) and ( $origin ) and (isset ($params["intervalo"])) ){
		                    
		$tmp_id_module = $id_agent_module;
		$tmp_graph_type = $params["graphtype"];
		$tipo = 'sparse';
		$intervalo = $params["intervalo"];
		$label = $params["label"];
		$color = ($params['color'])?"#".$color:'blue';
		if ( isset($params["draw_events"]) and $params["draw_events"]==0 ) 
			{ $draw_events = 0; } else { $draw_events = 1; } 
		if (isset($params['zoom']) and is_numeric($params['zoom']) and $params['zoom']>100) {
			$zoom = $params['zoom'] / 100 ;
		} else { $zoom = 1; } 
		
		// building parameters for grafico_modulo_sparse
		// $graph_type
		$graph_type = split (":", $tmp_graph_type);
		for ($cc=0; $cc<count($graph_type); $cc++) {
			if (!is_numeric($graph_type[$cc])) { $graph_type[$cc] = '0'; }
		}
	
		// $id_module
		$id_module = split (":", $tmp_id_module);
		// TODO: check
            
		$params_graph = grafico_modulo_sparse(	$label, 		// label of the graph
					$id_module,				// array with modules id to be represented
					$graph_type, 				// type of graph to be represented
					$abc_o=$origin, $abc_int=$periodo,	// origin abcise of graph and abscise interval
										// $abc_f - $abc_o = $abc_int,
					$period=ceil($periodo/$intervalo),										// resolution of abc
					$ord_o=0, $ord_int=100,			// origin ordenade and interval
					$zoom, $draw_events,
					$transparency = 0);
	}
	              

	return $params_graph;
}


function DC_pandoragraph_write_guihtml ( &$DC_params, $identifier, $handler ) {

	$identifier .= "DC_";
	$DC_type = 'DC_pandoragraph'; 
	
	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "type' value='". $DC_type ."'> <BR>\n" );

	// from winstat.php. This is the code for the sliding window with the graph parameters

	fwrite ( $handler, "

	<table>

	<tr><td class='left'>
        agent name :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "agentname' value='" . $DC_params['agentname'] . "'>
        </td></tr>
        
        <tr><td class='left'>
        module name :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "modulename' value='" . $DC_params['modulename'] . "'>
        </td></tr>
        
        <tr><td class='left'>
        start (YYYY-MM-DD hh:mm:ss) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "sts' value='" . $DC_params['sts'] . "'>
        </td></tr>
        
        <tr><td class='left'>
        end (YYYY-MM-DD hh:mm:ss) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "ets' value='" . $DC_params['ets'] . "'>
        </td></tr>
        
        <tr><td class='left'>
        graph type(s) : 
        </td><td class='right'>
        <input type='text' name='" . $identifier . "graphtype' value='" . $DC_params['graphtype'] . "'>
        </td></tr>
              
        <tr><td class='left'>
        draw_events :
        </td><td class='right'>
        	<input type='radio' name='" . $identifier . "draw_events' value='0' " . (($DC_params['draw_events'] == '0')?'checked="checked"':'') . " > no
				&nbsp; &nbsp; 
		<input type='radio' name='" . $identifier . "draw_events' value='1' " . (($DC_params['draw_events'] == '1')?'checked="checked"':'') . " > yes
        </td></tr>	
	
        <tr><td class='left'>
        points :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "intervalo' value='" . $DC_params['intervalo'] . "'>
        </td></tr>	 
	
        <tr><td class='left'>
        label :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "label' value='" . $DC_params['label'] . "'>
        </td></tr>	 
	
        <tr><td class='left'>
        zoom (%) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "zoom' value='" . $DC_params['zoom'] . "'> 
        </td></tr>	 
		 
        <tr><td class='left'>
        color :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "color' value='" . $DC_params['color'] . "'>
        </td></tr>	 	
		
	</table>
	" );
	                                                                                    
}                                                                                           
                                                                                            
function DC_pandoragraph_params () {

	return array (	'agentname'	,
			'modulename'	,
			'label'		,
			'sts'		,
			'ets'		,
			'color'		,
			'graphtype'	,
			'origin'	,
			'draw_events'	,
			'intervalo'	,
			'zoom'			
			);

}


?>
