<?php


function &DC_listmoduledata_calculate ( &$params ) {

	// makes a query againts Pandora DB
	// and return a list in RC_list format

	$data;		// array to return

	// translating key words
	global $time_key_words;
		
	if ( array_key_exists($params['sts'], $time_key_words) ) { 
		$tfinal = mysql_time ( $params['ets'] );
		$torigin = $tfinal - $time_key_words[$params['sts']];
	} elseif ( array_key_exists($params['ets'], $time_key_words) ) {
		$torigin = mysql_time ( $params['sts'] );
		$tfinal = $torigin + $time_key_words[$params['ets']];
	} else {
		$torigin = mysql_time ( $params['sts'] );
		$tfinal = mysql_time ( $params['ets'] );
	}

	$data[0]['torigin'] = $torigin;
	$data[0]['tfinal']  = $tfinal;
	
	// translating to mysql text timestamp
	$torigin = mysql_date ($torigin);
	$tfinal  = mysql_date ($tfinal);

	// checking fields and getting agent-module id
	$id_agent_module = dame_agente_modulo_id_names(	$params['agentname'], 
							$params['modulename']);
	if (!$id_agent_module) { return; }


	// building list titles
	
	if ($params['btimestamp']) { $data[0]['titles'][] = 'Timestamp'; }
	$data[0]['titles'][] = 'Data';
	if ($params['bcount']) { $data[0]['titles'][] = 'Count'; }


	// querying

	$data_tables = array (	'tagente_datos', 
				'tagente_datos_inc',
				'tagente_datos_string' );

	$columns = ($params['btimestamp'])?'timestamp, ':'';
	$columns .= "datos";
	$columns .= ($params['bcount'])?', count(*) cc':'';

	$tail .= ($params['sts'])?' and timestamp >"' . $torigin . '" ':'';
	$tail .= ($params['ets'])?' and timestamp <"' . $tfinal . '" ':'';
	
	$tail .= ($params['bcount'])?'group by datos order by cc desc':'';
	
	for ($cc=0; $cc<3; $cc++) {
		$sql = 'SELECT ' . $columns . 
			' FROM ' . $data_tables[$cc] . 
			' where id_agente_modulo =' . $id_agent_module . 
			' ' . $tail ;
			
		if ($result=mysql_query($sql)){
			while ( $row=mysql_fetch_row($result) ) {		
				for ($rr=0; $rr<count($row); $rr++) { $data[$rr+1][] = $row[$rr]; }
			}
		}
	}

	// columns defined with regular expressions?
	// note that if one r.e. for a new column is specified, the original
	// data is not included in the final list.
	// note also that only the first match is considered.
	// note also that, if parenthesis are used, the inner match is considered.
	
	// ugly
	if ($params['re1'] or $params['re2'] or $params['re3'] or $params['re4'] or $params['re5']) {
		// we have to reorganize colums
		// note that counter, if present, disappears
		if ($params['btimestamp']) { 
			$ar_ts = $data[1]; $ar_moduledata = $data[2]; 
		} else {
			$ar_moduledata = $data[1];
		}
		unset ($data);
		
		// and create new data.
		// timestamp title first
		if ($params['btimestamp']) { 
			$data[0]['titles'][] = 'Timestamp'; 
			$data[1] = $ar_ts;
		}
		
		for ($cc=1; $cc<6; $cc++) {
			if ($params['re' . $cc]) {
				// title
				if ($params['re' . $cc . 't']) {
					$data[0]['titles'][] = $params['re' . $cc . 't'];
				} else {
					$data[0]['titles'][] = 'Data ' . $cc;
				}
				
				// and the data
			
				$data[] = array_map (
					create_function('$a', 
						'preg_match (\'' . $params['re'.$cc] . '\', $a, $ar_matches );
						return (($ar_matches)?$ar_matches[count($ar_matches)-1]:"");')
						, $ar_moduledata ) ; 
			}
		}
		
	}
	
	return $data;
}

function DC_listmoduledata_write_guihtml ( &$DC_params, $identifier, $handler ) {

	$identifier .= "DC_";
	$DC_type = 'DC_listmoduledata';

	fwrite ( $handler, "<input type='hidden' name='". $identifier . "type' value='". $DC_type ."'> <BR>\n" );
	
	fwrite ( $handler, "
	<table>
	
	<tr><td class='left'>
	agent name :
	</td><td class='right'>
	<input type='text' name='" . $identifier . "agentname' value='" . $DC_params['agentname'] . "'></td></tr>

	<tr><td class='left'>
	module name :
	</td><td class='right'>
	<input type='text' name='" . $identifier . "modulename' value='" . $DC_params['modulename'] . "'></td></tr>

 	<tr><td class='left'>
 	include timestamp:
        </td><td class='right'>
        <input type='radio' name='" . $identifier . "btimestamp' value='0' " . (($DC_params['btimestamp'] == '0')?'checked="checked"':'') . " > no
			&nbsp; &nbsp; 
	<input type='radio' name='" . $identifier . "btimestamp' value='1' " . (($DC_params['btimestamp'] == '1')?'checked="checked"':'') . " > yes</td></tr>

	<tr><td class='left'>
        include counter: 
        </td><td class='right'>
        <input type='radio' name='" . $identifier . "bcount' value='0' " . (($DC_params['bcount'] == '0')?'checked="checked"':'') . " > no
			&nbsp; &nbsp; 
	<input type='radio' name='" . $identifier . "bcount' value='1' " . (($DC_params['bcount'] == '1')?'checked="checked"':'') . " > yes</td></tr>
	 
        <tr><td class='left'>
        start (YYYY-MM-DD hh:mm:ss) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "sts' value='" . $DC_params['sts'] . "'></td></tr>	
	
	<tr><td class='left'>
        end (YYYY-MM-DD hh:mm:ss) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "ets' value='" . $DC_params['ets'] . "'></td></tr>
        </table>
        ");
        
	
	// this should be dynamic and/or and array 
	fwrite ( $handler, "<br>regular expression defined columns: <br><br>");
	fwrite ( $handler, "reg expr field 1 : <input type='text' name='" . $identifier . "re1' value='" . $DC_params['re1'] . "'> &nbsp;&nbsp; " ); 
	
	fwrite ( $handler, "title : <input type='text' name='" . $identifier . "re1t' value='" . $DC_params['re1t'] . "'><br>\n" ); 
	
	fwrite ( $handler, "reg expr field 2 : <input type='text' name='" . $identifier . "re2' value='" . $DC_params['re2'] . "'> &nbsp;&nbsp; " ); 
	
	fwrite ( $handler, "title : <input type='text' name='" . $identifier . "re2t' value='" . $DC_params['re2t'] . "'><br>\n" ); 
	
	fwrite ( $handler, "reg expr field 3 : <input type='text' name='" . $identifier . "re3' value='" . $DC_params['re3'] . "'> &nbsp;&nbsp; " ); 
	
	fwrite ( $handler, "title : <input type='text' name='" . $identifier . "re3t' value='" . $DC_params['re3t'] . "'><br>\n" ); 
	
	fwrite ( $handler, "reg expr field 4 : <input type='text' name='" . $identifier . "re4' value='" . $DC_params['re4'] . "'> &nbsp;&nbsp; " ); 
	
	fwrite ( $handler, "title : <input type='text' name='" . $identifier . "re4t' value='" . $DC_params['re4t'] . "'><br>\n" ); 
	
	fwrite ( $handler, "reg expr field 5 : <input type='text' name='" . $identifier . "re5' value='" . $DC_params['re5'] . "'> &nbsp;&nbsp; " ); 
	
	fwrite ( $handler, "title : <input type='text' name='" . $identifier . "re5t' value='" . $DC_params['re5t'] . "'><br>\n" ); 
}                                                                                           
                                                                                            
function DC_listmoduledata_params () {

	return array (
			'agentname'	,	// agent name 
			'modulename'	,	// module name
			'btimestamp'	,	// if 1, the first column is the timestamp
			'bcount'	,	// if btimestamp = 0 and bcount = 1, add a count column
			'sts'		, 	// starting timestamp
			'ets'		,	// ending timestamp
			're1'		,	// regular expressions that define new columns.
			're2'		,	//   If empty, they are ignored
			're3'		,	
			're4'		,	
			're5'		
			);

}


?>