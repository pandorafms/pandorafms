<?php

// Pandora - the Free monitoring system
// ===================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006 Jose Navarro <josepublico@jnavarro.net>
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

include_once ('reporting/fgraph.php');
include_once 'Image/Graph.php';



// LaTeX related functions

function esc_LaTeX ( $text ) {

	// escaping LaTeX special characters
	return str_replace ( 	array ( '#', '$', '%', '^', '&', '{', '}', '~', '\\\\' ),
			array ( '\#', '\$', '\%', '\^', '\&', '\{', '\}', '\~', '$\backslash$' ),
			$text ); 
}


// XML and report management functions //


function XML_get_content_php4 ( &$xml_node ) {

	// in http://se.php.net/domxml/ is said that $node->get_content() is deprecated
	// and, that text content is now a text node. Ok, so, let's access this new node.

	$ar = $xml_node->child_nodes();

   	foreach($ar as $i)
   	{
       		if( $i->node_type() == XML_TEXT_NODE )  { 
       			return iconv("UTF-8", "ISO-8859-1",  $i->node_value() ); 
       		}
   	}
}

function XML_get_content_php5 ( &$xml_node ) {

	// simpleXML version of XML_get_content_php4

	return iconv ( "UTF-8", "ISO-8859-1", (string) $xml_node );
}


function &XML_parseConf_php4 ( $XML_node, $tagname, &$child_type ) {
	
	if ($tagname) {
		$tt = XML_get_elements_by_tagname_php4 ( $XML_node, $tagname );
		$child = $tt[0];
	} else {
		$child = $XML_node;
	}
	
	if ($child) {
		$tt = $child->child_nodes();
		foreach ($tt as $param) {
			$ar_param[ $param->node_name() ] = XML_get_content_php4 ( $param );
		}

		$child_type = $child->get_attribute('type');
	} else {
		$child_type = "";
	}

	return $ar_param;
}


function &XML_parseConf_php5 ( $XML_node, $tagname, &$child_type ) {

	// simpleXML version of XML_parseConf_php4
	
	if ($tagname) {
		$tt = XML_get_elements_by_tagname_php5 ( $XML_node, $tagname );
		$child = $tt[0];
	} else {
		$child = $XML_node;
	}
	
	if ($child) {
		$tt = $child->children();
		foreach ($tt as $param) {
			$ar_param[ $param->getName() ] = XML_get_content_php5 ( $param );
		}

		$child_type = (string) $child['type'];
	} else {
		$child_type = "";
	}

	return $ar_param;	
}


function &open_report_xml_php4 ( $xml_file ) {

	// domxml_open_file|mem only work with utf-8
	// this change is unmade in the function XML_get_content
	$xml_file = iconv("ISO-8859-1", "UTF-8", $xml_file);

	$func = is_file( $xml_file )?'domxml_open_file':'domxml_open_mem';
	
	if (!$reportxml = $func ( $xml_file ,
		  DOMXML_LOAD_PARSING + 
		  DOMXML_LOAD_COMPLETE_ATTRS + 
		  DOMXML_LOAD_SUBSTITUTE_ENTITIES + 
		  DOMXML_LOAD_DONT_KEEP_BLANKS  )) {

	    echo "Error while parsing the document\n";
	    return;
	}

	return $reportxml;

}


function &open_report_xml_php5 ( $xml_file ) {

	// simpleXML version of open_report_xml_php4

	// simpleXML only work with utf-8
	// this change is unmade in the function XML_get_content
	$xml_file = iconv("ISO-8859-1", "UTF-8", $xml_file);
	
	$reportxml = simplexml_load_string($xml_file);
	
	return $reportxml;
}


function XML_load_report_php4 ( $xml_file ) {

	// loads $xml_file into domxml and returns the root node

	if (!$reportxml =& open_report_xml_php4 ( $xml_file )) { return; }
  	return $reportxml->document_element();
}


function XML_load_report_php5 ( $xml_file ) {

	// simpleXML version of XML_load_report_php4
	
	if (!$reportxml =& open_report_xml_php5 ( $xml_file )) { return; }
	return $reportxml;
}


function XML_load_defaults_php4 ( $report ) {
	// loads defaults values from domxml report (root node)

	$childs = XML_get_elements_by_tagname_php4 ($report, 'defaultvalues');
	
	foreach ($childs as $defaultvalues_node) {
		
		// let's fill $defaultvalues[?C_type][param]=default value
		
		$c_type_ar = $defaultvalues_node->child_nodes();
		foreach ($c_type_ar as $c_type) {
			$defaultvalues[ $c_type->node_name() ] =& XML_parseConf_php4 ( $c_type, '', $dummy );
		}
	}

	return $defaultvalues;
}


function XML_load_defaults_php5 ( $report ) {
	
	// simpleXML version of XML_load_defaults_php4
	
	$childs = XML_get_elements_by_tagname_php5 ($report, 'defaultvalues');
	foreach ($childs as $defaultvalues_node) {
		
		// let's fill $defaultvalues[?C_type][param]=default value
		
		$c_type_ar = $defaultvalues_node->children();
		foreach ($c_type_ar as $c_type) {
			
			$defaultvalues[ $c_type->getName() ] =& XML_parseConf_php5 ( $c_type, '', $dummy );
		}
	}

	return $defaultvalues;	
}


function XML_get_elements_by_tagname_php4 ( $node, $tagname ) {
	// get elements, inside a domxml node, named $tagname

	return $node->get_elements_by_tagname( $tagname );
}

function XML_get_elements_by_tagname_php5 ( $node, $tagname ) {

	// simpleXML version of XML_get_elements_by_tagname_php4
	
	foreach ( $node->children() as $child ) {
		if ( $child->getName() == $tagname ) { $matched_elements[] = $child; }
	}
	
	return $matched_elements;
}


// end of XML functions // 


function complete_with_defaults ( $C_type, &$C_params ) {

	global $defaultvalues;

	foreach ( ($defaultvalues[$C_type]) as $param => $value ) {
		if (!$C_params[$param]) { $C_params[$param] = $value; }		
	}
	
}


function open_and_write_report ( $xml_file, &$present_plugins, &$present_plugin_functions, $file='php://output', $override='' ) {

	global $reports_ext, $reports_dir, $defaultvalues;
	global $XML_parseConf, $XML_load_report, $XML_load_defaults, $XML_get_elements_by_tagname;

	// loading root (report)
	$report = $XML_load_report ($xml_file);	

	// loading default values
	$defaultvalues = $XML_load_defaults ( $report );
		
	// opening handler
	
	if (!$reports_ext[ $defaultvalues['RC_report']['output_format'] ] and !$override) { return; }
	if ( $file != 'php://output' ) { 
		// creating a temporal folder
		$tmpfolder = tempnam($reports_dir, $file); 
		unlink($tmpfolder);
		mkdir($tmpfolder, 0700);
		// and the complete name file
		$file .= '.' . $reports_ext[ $defaultvalues['RC_report']['output_format'] ]; 
		//$file = $tmpfolder . '/' . $file;
		$handler = fopen ( $tmpfolder . '/' . $file, "w" );
	} else {
		$handler = fopen ( 'php://output', "w" );
	}
	if (!$handler) { print "no puedo abrir " . $tmpfolder . '/' . $file; return; }		
		
		
	// displaying default values (if in gui mode)
	// chapuza follows!  what will happen when I had more gui modes??
	//    maybe a regular expression $override ~ /^gui.*/
	if ($override=='guihtml') {	
		
		$counter = 0;   // counter for doing a unique identifier for defaultvalues	
		foreach ( $defaultvalues as $c_type => $params ) {
			// is DC or RC ?
			$type = '';
			if ( strstr($c_type, 'DC_') == $c_type ) { $type = 'DC'; }
			if ( strstr($c_type, 'RC_') == $c_type ) { $type = 'RC'; }	
			
			if ($type) {
				$counter++;
				$data[ 'identifier' ] = 'dvid' . $counter . '_';
				$data[ 'C_type' ] = $c_type;
				$RC_override = 'RC_' . $override;
				
				if ( $RC_override and in_array($RC_override, $present_plugins) ) {
					$func = $RC_override . '_write_defaultvalues';
					$func ( $params, $data, $handler );
				}
			}
		}
	}


	// loading RC's

	$counter = 0;	// counter for doing a unique identifier for RC's
	$childs = $XML_get_elements_by_tagname ( $report, 'RC');
	foreach ($childs as $RC_node) {

		// for each RC:
		// - DC is loaded and executed
		// - TC and output_format are loaded
		// - and, finally, RC is executed
		
		$counter++;
		$RC_identifier = 'id' . $counter . '_';
		
		// processing DC
		
		$ar_param =& $XML_parseConf ( $RC_node, 'DC', $child_DC_type );

		if ($override) { 
			$ar_param['DC_type'] = $child_DC_type;
			$child_DC_type = 'DC_' . $override;
		} else {
			complete_with_defaults ( $child_DC_type, $ar_param );
		}
		
		if ( $child_DC_type and in_array($child_DC_type, $present_plugins) ) {
			$func = $child_DC_type . '_calculate';
			$data =& $func ( $ar_param );
		}
		
		// processing RC parameters
		
		$ar_param =& $XML_parseConf ( $RC_node, '', $RC_node_type );

		if ($override) { 
			$ar_param['RC_type'] = $RC_node_type;
			$RC_node_type = 'RC_' . $override; 
		} else {
			complete_with_defaults ( $RC_node_type, $ar_param );
		}
		$ar_param['RC_report_id'] = $RC_identifier;
		$ar_param['RC_tmpfolder'] = $tmpfolder;		// needed if RC needs to create files
		
		// processing RC
		
		if ( in_array($RC_node_type . '_write_' . $defaultvalues['RC_report']['output_format'], $present_plugin_functions ) or $override ) {
			$func = $override?$RC_node_type . '_write_all':$RC_node_type . '_write_' . $defaultvalues['RC_report']['output_format'];
			$func ( $ar_param, $data, $handler );
		}
	}
	
	fclose ($handler);
	
	// and, finally, postprocessing the report (if applies)
	
	$RC_report_params['tmpfolder'] = $tmpfolder;
	$RC_report_params['filename'] = $file;		// path = $tmpfolder . '/' . $file
	$func = "RC_report_write_" . ( ($override)?$override:$defaultvalues['RC_report']['output_format'] ) ;
	if ( function_exists($func) ) { $func ( $RC_report_params ); } 
}


// GUI functions //

function html_xmp ($foo)  {  
	$foo = str_replace ('<', '&lt;', $foo );
	$foo = str_replace ('>', '&gt;', $foo );
	//$foo = str_replace ('\n', '<br>', $foo );
	return $foo;
}


function parse_POST_to_XML ( $post, $flag_xmp=0 ) {

	global $report_plugin_functions;

	function lid ($foo) { return preg_replace('/^(dv)?id\d+_(DC_)?/', '', $foo); }
 

	$post_keys 	= array_keys ( $post );
	$post_defaultvalues = preg_grep ( '/^dvid\d+_[DR]C_type/', $post_keys );
	$post_RC_types 	= preg_grep ( '/^id\d+_RC_type$/', $post_keys );
	
	// if there is nothing to do, why go further?
	if (!$post_RC_types && !$post_defaultvalues 
		&& !$post['adddeldefaultvalue'] && !$post['addRCcomponentplugin']) { return; }

	$xml_doc = "<?xml version='1.0' encoding='UTF-8'?>\n\n<report>\n\n";
	
	$b_adddeldefaultvalue_found = FALSE;  // flag that goes TRUE if $post['adddeldefaultvalue'] component
					// is defined in the actual xml file => the user wants to 
					// delete it.
	$xml_doc .= "<defaultvalues>\n"; 
	foreach ( $post_defaultvalues as $C_type ) { 
	
		// if this default value needs to be deleted, let's jump to the next
		if ($post['adddeldefaultvalue'] == $post[ $C_type ]) { 
			$b_adddeldefaultvalue_found = TRUE;
			continue; 
		}
	
		preg_match ('/^dvid\d+_/', $C_type, $tt);
		$identifier = $tt?$tt[0]:NULL;
		
		$C_params_keys = preg_grep ( "/^".$identifier."(DC_)?/", $post_keys);
		$xml_doc .= "<" .  $post[ $C_type ] . ">\n";
		
		foreach ($C_params_keys as $C_param) {
			
			$func = $post[ $C_type ] . "_params";
			if ( in_array ( $func, $report_plugin_functions ) and in_array( lid( $C_param ), $func() )) {
				$xml_doc .= "<".lid( $C_param ).">". $post[$C_param] ."</".lid( $C_param ).">\n";
			}
		}
		
		$xml_doc .= "</" .  $post[ $C_type ] . ">\n";
	}
	// let's add a default value if the user wants so
	if (!$b_adddeldefaultvalue_found and $post['adddeldefaultvalue'] ) {
		$xml_doc .= "<" . $post['adddeldefaultvalue'] . ">\n";
		$xml_doc .= "</" . $post['adddeldefaultvalue'] . ">\n";
	}
	$xml_doc .= "</defaultvalues>\n\n";
	
	// maybe the user wants to add a component
	$b_new_component_updated = TRUE;	// flag
	$xml_doc_new = '';
	if ( $post['addRCcomponentplugin'] ) {		// if there is something to insert
			
		$xml_doc_new = "<RC type='". $post['addRCcomponentplugin'] ."' >\n";
		if ($post['addDCcomponentplugin']) {
			$xml_doc_new .= "<DC type='". $post['addDCcomponentplugin'] ."' >\n";
			$xml_doc_new .= "</DC>\n";
		}
		$xml_doc_new .= "</RC>\n\n";
			
		$b_new_component_updated = FALSE;
	}
	
	
	foreach ( $post_RC_types as $RC_type ) {
		
		preg_match ('/^id(\d+)_/', $RC_type, $tt);
		$identifier = $tt?$tt[0]:NULL;
		$RC_position = $tt?$tt[1]:NULL;
		
		// if this component is marked for delete, jump to the next
		if ($post['delcomponentposition'] == $RC_position) { continue; }
		
		$RC_params_keys = preg_grep ( "/^".$identifier."(?!DC_)/", $post_keys);
		$DC_params_keys = preg_grep ( "/^".$identifier."DC_/", $post_keys );
		
		$xml_doc_tmp = "<RC type='". $post[ $RC_type ] ."' >\n";
		
		foreach ($RC_params_keys as $RC_param) {
		
			$func = $post[ $RC_type ] . "_params";
			if ( in_array ( $func, $report_plugin_functions ) and in_array( lid( $RC_param ), $func() )) {
				$xml_doc_tmp .= "<".lid( $RC_param ).">". $post[$RC_param] ."</".lid( $RC_param ).">\n";
			}
		}

		if ($DC_params_keys and $post [$identifier . 'DC_type'] ) { 
			$DC_type = $post[ $identifier . 'DC_type' ];
			$xml_doc_tmp .= "<DC type='" . $DC_type . "'>\n";
			foreach ( $DC_params_keys as $DC_param ) {
				$func = $DC_type . "_params";
				if ( in_array ( $func, $report_plugin_functions ) and in_array( lid( $DC_param ), array_keys( $func() ) )) {
					$xml_doc_tmp .= "<".lid( $DC_param ).">". $post[$DC_param] ."</".lid( $DC_param ).">\n";
				}
			}
			$xml_doc_tmp .= "</DC>\n";
		}
		
		$xml_doc_tmp .= "</RC>\n\n";
		
		// has the new component to be added now?
		if ( $post['addcomponentposition'] == $RC_position && $xml_doc_new) {
		
			// before or after this position?
			if ($post['addcomponentafter']) {
				$xml_doc .= $xml_doc_tmp . $xml_doc_new;
			} else {
				$xml_doc .= $xml_doc_new . $xml_doc_tmp;
			}
			
			// updating the flag
			$b_new_component_updated = TRUE;
		} else {
			$xml_doc .= $xml_doc_tmp;
		}
	}
	
	// is there a component to add that has not been added yet?
	if (!$b_new_component_updated) { $xml_doc .= $xml_doc_new; }

	$xml_doc .= "</report>\n";

	return $flag_xmp?html_xmp ($xml_doc):$xml_doc;
}



// MAIN //



// loading plugins

// - load files from ./plugins
// - for each file, check:
//	- name (unic)
//	- contains the necessary functions ?
//	- which methods implements ?
// 	- includes


// some global variables

$mandatory_plugin_functions = array (

	'RC' 	=>	array (	'write_.+',
				'write_guihtml',
				'params'
			), 
	'DC'	=>	array ( 'calculate',
				'write_guihtml',
				'params'
			)
	);

$report_plugin_functions = array ();

$report_plugins;  	// list of plugins

$plugins_dir = './operation/reporting/plugins';
$reports_dir = './operation/reporting/reports';
$reports_ext = array (	'latex'	=>	'tex',
			'html'	=>	'html'
			);
$defaultvalues ;	// global containing defaults for every RC type
			// $defaultvalues['RC_type']['param'] = value;

$time_key_words = array (	
			'HOUR'	=>	3600,
			'DAY'	=>	86400,
			'WEEK'	=>	604800,
			'MONTH'	=>	2592000	
		);

// XML functions
// ok, now a bit of history. I began using domxml with php4 and completely
// forgot that domxml was not continued in php5. Instead of rewriting all
// for simpleXML (the simplest XML support in PHP5), i want this script to
// work also with domxml (debian sarge users with only php4... I dont like
// backports). I have decided (maybe wrong) to maintain two functions (one
// for domxml-php4 and one for simplexml-php5) for every XML function in 
// Pandora Reporting. 
// Every XML function should be named:  function_name_phpX , where X is the
// major php version.
// These functions must be called   $function_name ( ... ), and I define 
// these general function names here. I prefer this than to use call_user_func
// Remember to include "global $XML_function_name" if you are out of scope
// So far, so good. If you have a better method, please, let me know

$majorPHPversion = array_shift ( explode ('.', phpversion()) );

$XML_functions = array ( 'XML_get_content', 'XML_parseConf', 'open_report_xml',
		'XML_load_report', 'XML_load_defaults', 'XML_get_elements_by_tagname' );
		
foreach ($XML_functions as $XML_function) {

	$$XML_function = $XML_function . '_php' . $majorPHPversion;
}


// let's begin loading the plugins, checking them
// and populating global variables

if ($dh = opendir( $plugins_dir )) {
	while (($file = readdir($dh)) !== false) {
	
		if ( preg_match( '/^(([RDT]C)_.+)\.php$/', $file, $match ) ) {
			
			$plugin_name = $match[1];   	// f.ex  RC_test
			$plugin_type = $match[2];	// TC, RC or DC

			$plugin_content = file_get_contents ( $plugins_dir . '/' . $file );
			preg_match_all( '/function\s+&?(\S+)\s/', $plugin_content, $match, PREG_PATTERN_ORDER );
			$report_plugin_functions = array_merge ( $report_plugin_functions, $match[1] );
			
			$flag_plugin_OK = 1;
			
			foreach ( $mandatory_plugin_functions[ $plugin_type ] as $mandatory_function ) {
				if ( !preg_grep( '/^' . $plugin_name . "_" . $mandatory_function . '$/', $report_plugin_functions ) ) 
					{ $flag_plugin_OK = 0; }
			}

			if ( $flag_plugin_OK ) { 
				// I don't know if the includes should be put anywhere else ...
				$report_plugins[] = $plugin_name; 
				include ( $plugins_dir . '/' . $file );
			}
		}

        }
       	closedir($dh);
} else { print 'Fatal! :   could not open directory "plugins"'; exit; }





