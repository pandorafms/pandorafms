<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
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

// Functions

// ---------------------------------------------------------------
// input: var, string. 
//          mesg, mesage to show, var content. 
// --------------------------------------------------------------- 

function midebug($var, $mesg){ 
	echo "[DEBUG (".$var."]: (".$mesg.")"; 
	echo "<br>";
} 

// --------------------------------------------------------------- 
// audit_db, update audit log
// --------------------------------------------------------------- 

function audit_db($id,$ip,$accion,$descripcion){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$sql1='INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion) VALUES ("'.$id.'","'.$accion.'","'.$today.'","'.$ip.'","'.$descripcion.'")';
	$result=mysql_query($sql1);
}


// --------------------------------------------------------------- 
// logon_db, update entry in logon audit
// --------------------------------------------------------------- 

function logon_db($id,$ip){
	require("config.php");
	audit_db($id,$ip,"Logon","Logged in");
	// Update last registry of user to get last logon
	$sql2='UPDATE tusuario fecha_registro = $today WHERE id_usuario = "$id"';
	$result=mysql_query($sql2);
}

// --------------------------------------------------------------- 
// logoff_db, also adds audit log
// --------------------------------------------------------------- 

function logoff_db($id,$ip){
	require("config.php");
	audit_db($id,$ip,"Logoff","Logged out");
}

// --------------------------------------------------------------- 
// Return email of a user given ID 
// --------------------------------------------------------------- 

function dame_email($id){ 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario =".$id;
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["direccion"];
	return $nombre;
} 


// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an valid user
// --------------------------------------------------------------- 

function comprueba_login() { 
	if (isset($_SESSION["id_usuario"])){
		$id = $_SESSION["id_usuario"];
		require("config.php");
		$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
		$resq1=mysql_query($query1);
		$rowdup=mysql_fetch_array($resq1);
		$nombre=$rowdup["id_usuario"];
		if ( $id == $nombre ){
			return 0 ;	
		}
	}
	require("general/noaccess.php");
	return 1;	
}

// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an administrator
// --------------------------------------------------------------- 

function comprueba_admin() {
	if (isset($_SESSION["id_usuario"])){
		$iduser=$_SESSION['id_usuario'];
		if (dame_admin($iduser)==1){
			$id = $_SESSION["id_usuario"];
			require("config.php");
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];
			$nivel=$rowdup["nivel"];
			if (( $id == $nombre) and ($nivel ==1))
				return 0;
		}
	}
	require("../general/no_access.php");
	return 1;
}

// ---------------------------------------------------------------
// Returns Admin value (0 no admin, 1 admin)
// ---------------------------------------------------------------

function dame_admin($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario ='".$id."'";   
	$rowdup=mysql_query($query1);
	$rowdup2=mysql_fetch_array($rowdup);
	$admin=$rowdup2["nivel"];
	return $admin;
}

// ---------------------------------------------------------------
// Returns number of alerts fired by this agent
// ---------------------------------------------------------------

function check_alert_fired($id_agente){
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente ='".$id_agente."'";   
	$rowdup=mysql_query($query1);
	while ($data=mysql_fetch_array($rowdup)){
		$query2="SELECT COUNT(*) FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$data["id_agente_modulo"];
		$rowdup2=mysql_query($query2);
		$data2=mysql_fetch_array($rowdup2);
		if ($data2[0] > 0)
			return 1;
	}
	return 0;
}

// ---------------------------------------------------------------
// 0 if it doesn't exist, 1 if it does, when given email
// ---------------------------------------------------------------

function existe($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";   
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		if ($rowdup=mysql_fetch_array($resq1)){ 
			return 1; 
		}
		else {
			return 0; 
		}
	} else { return 0 ; }
}


// ---------------------------------------------------------------
// parse and clear string
// --------------------------------------------------------------- 

function salida_limpia ($string){
	$quote_style=ENT_QUOTES;
	static $trans;
	if (!isset($trans)) {
		$trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
		foreach ($trans as $key => $value)
			$trans[$key] = '&#'.ord($key).';';
		// dont translate the '&' in case it is part of &xxx;
		$trans[chr(38)] = '&';
	}
	// after the initial translation, _do_ map standalone '&' into '&#38;'
	return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr($string, $trans));
}
/*
{
	$texto_ok = htmlspecialchars($texto, ENT_QUOTES, "ISO8859-15"); // Quitamos 
	// Reemplazamos retornos de carro por "<br>"
	$texto_html = str_replace(chr(13),"<br>",$texto_ok);
	return $texto_html;
}
*/

// ---------------------------------------------------------------
// This function reads a string and returns it "clean"
// for use in DB, againts string XSS and so on
// ---------------------------------------------------------------

function entrada_limpia ($texto){
	// OJO: MagicQuotes en la configuracion de php.ini deberia estar activado y esta funcion solo deberiamos 
	// llamarla al entrar datos para escribir datos en la base MYSQL no al reves.
	//	$str = "A 'quote' is <b>bold</b>";
	// Outputs: A 'quote' is &lt;b&gt;bold&lt;/b&gt;
	// $filtro0 = utf8_decode($texto);
	$filtro1 =  htmlentities($texto, ENT_QUOTES); // Primero evitamos el problema de las dobles comillas, comillas sueltas, etc.
	$filtro2 = $filtro1;
	// Sustituimos los caracteres siguientes ( ) : &
	// $filtro2 = strtr($filtro1, array('&' => '&#38',':' => '58', '(' => '&#40;', ')' => '&#41;')); 
	return $filtro2;							
}

// ---------------------------------------------------------------
// Esta funcion lee una cadena y la da "limpia", para su uso con 
// parametros pasados a funcion de abrir fichero. Usados en sec y sec2
// ---------------------------------------------------------------

function parametro_limpio($texto){
	// Metemos comprobaciones de seguridad para los includes de paginas pasados por parametro
	// Gracias Raul (http://seclists.org/lists/incidents/2004/Jul/0034.html)
	// Consiste en purgar los http:// de las cadenas
	$pos = strpos($texto,"://");	// quitamos la parte "fea" de http:// o ftp:// o telnet:// :-)))
	if ($pos <> 0)
	$texto = substr_replace($texto,"",$pos,+3);   
	// limitamos la entrada de datos por parametros a 125 caracteres
	$texto = substr_replace($texto,"",125);
	$safe = preg_replace('/[^a-z0-9_\/]/i','',$texto);
	return $safe;
}

// ---------------------------------------------------------------
// Esta funcion se supone que cierra todos los tags HTML abiertos y no cerrados
// ---------------------------------------------------------------

// string closeOpenTags(string string [, string beginChar [, stringEndChar [, string CloseChar]]]);

function closeOpenTags($str, $open = "<", $close = ">", $end = "/", $tokens = "_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
{ $chars = array();
	for ($i = 0; $i < strlen($tokens); $i++)
	{ $chars[] = substr($tokens, $i, 1); }

	$openedTags = array();
	$closedTags = array();
	$tag = FALSE;
	$closeTag = FALSE;
	$tagName = "";

	for ($i = 0; $i < strlen($str); $i++)
	{ $char = substr($str, $i, 1);
	if ($char == $open)
	{ $tag = TRUE; continue; }
	if ($char == $end)
	{ $closeTag = TRUE; continue; }
	if ($tag && in_array($char, $chars))
	{ $tagName .= $char; }
	else
	{if ($closeTag)
		{if (isset($closedTags[$tagName]))
			{ $closedTags[$tagName]++; }
		else
			{ $closedTags[$tagName] = 1; } }
		elseif ($tag)
			{if (isset($openedTags[$tagName]))
			{ $openedTags[$tagName]++; }
			else
			{ $openedTags[$tagName] = 1; } }
		$tag = FALSE; $closeTag = FALSE; $tagName = ""; } 
	 }

	while(list($tag, $count) = each($openedTags))
	{
	$closedTags[$tag] = isset($closedTags[$tag]) ? $closedTags[$tag] : 0;
	$count -= $closedTags[$tag];
	if ($count < 1) continue;
	$str .= str_repeat($open.$end.$tag.$close, $count);
	}
	return $str;

	}

// ---------------------------------------------------------------
// Return string with time-threshold in secs, mins, days or weeks
// ---------------------------------------------------------------

function give_human_time ($int_seconds){
   $key_suffix = 's';
   $periods = array(
                   'year'        => 31556926,
                   'month'        => 2629743,
                   'day'        => 86400,
                   'hour'        => 3600,
                   'minute'    => 60,
                   'second'    => 1
                   );

   // used to hide 0's in higher periods
   $flag_hide_zero = true;

   // do the loop thang
   foreach( $periods as $key => $length )
   {
       // calculate
       $temp = floor( $int_seconds / $length );

       // determine if temp qualifies to be passed to output
       if( !$flag_hide_zero || $temp > 0 )
       {
           // store in an array
           $build[] = $temp.' '.$key.($temp!=1?'s':null);

           // set flag to false, to allow 0's in lower periods
           $flag_hide_zero = false;
       }

       // get the remainder of seconds
       $int_seconds = fmod($int_seconds, $length);
   }

   // return output, if !empty, implode into string, else output $if_reached
   return ( !empty($build)?implode(', ', $build):$if_reached );
}

// ---------------------------------------------------------------
// This function show a popup window using a help_id (unused)
// ---------------------------------------------------------------

function popup_help ($help_id){
	echo "<a href='javascript:help_popup(".$help_id.")'>[H]</a>";
}

// ---------------------------------------------------------------
// no_permission () - Display no perm. access
// ---------------------------------------------------------------

function no_permission () {
	require("config.php");
	require ("include/languages/language_".$language_code.".php");
	echo "<h1>".$lang_label["no_permission_title"]."</h1>";
	echo "<img src='img/noaccess.gif' width='120'><br><br>";
	echo "<table width=550>";
	echo "<tr><td class=datos>";
	echo $lang_label["no_permission_text"];
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

function list_files($directory, $stringSearch, $searchHandler, $outputHandler) {
 	$errorHandler = false;
 	$result = array();
 	if (! $directoryHandler = @opendir ($directory)) {
  		echo ("<pre>\nerror: directory \"$directory\" doesn't exist!\n</pre>\n");
 		return $errorHandler = true;
 	}
 	if ($searchHandler == 0) {
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			@array_push ($result, $fileName);
		}
 	}
 	if ($searchHandler == 1) {
  		while(false !== ($fileName = @readdir ($directoryHandler))) {
   			if(@substr_count ($fileName, $stringSearch) > 0) {
   				@array_push ($result, $fileName);
   			}
  		}
 	}
 	if (($errorHandler == true) &&  (@count ($result) === 0)) {
  		echo ("<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n");
 	}
 	else {
  		sort ($result);
  		if ($outputHandler == 0) {
   			return $result;
  		}
  		if ($outputHandler == 1) {
  	 		echo ("<pre>\n");
   			print_r ($result);
   			echo ("</pre>\n");
  		}
 	}
}


function pagination ($count, $url, $offset ) {
	require ("config.php");
	require ("include/languages/language_".$language_code.".php");
	
	/* 	URL passed render links with some parameter
			&offset - Offset records passed to next page
	  		&counter - Number of items to be blocked 
	   	Pagination needs $url to build the base URL to render links, its a base url, like 
	   " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	   
	*/
	$block_limit = 10; // Visualize only $block_limit blocks 
	if ($count > $block_size){
		// If exists more registers than I can put in a page, calculate index markers
		$index_counter = ceil($count/$block_size); // Number of blocks of block_size with data
		$index_page = ceil($offset/$block_size); // block to begin to show data

		// This calculate index_limit, block limit for this search.
		if (($index_page + $block_limit) > $index_counter)
			$index_limit = $index_counter - 1;
		else
			$index_limit = $index_page + $block_limit;

		// This calculate if there are more blocks than visible (more than $block_limit blocks)
		if ($index_counter > $block_limit )
			$paginacion_maxima = 1; // If maximum blocks ($block_limit), show only 10 and "...."
		else
			$paginacion_maxima = 0;

		// This setup first block of query
		if ( $paginacion_maxima == 1)
			if ($index_page == 0)
				$inicio_pag = 0;
			else
				$inicio_pag = $index_page;
		else
			$inicio_pag = 0;

		// This shows first "<" in query, only if there
		if (($index_page > 0) and ($paginacion_maxima ==1)){
			$index_page_prev= ($index_page-1)*$block_size;
			echo '<a href="'.$url.'&offset='.$index_page_prev.'">&lt;</a> ';
		}

		// Draw blocks markers
		echo "<div>";
		for ($i = $inicio_pag; $i <= $index_limit; $i++) {
			$inicio_bloque = ($i * $block_size);
			$final_bloque = $inicio_bloque + $block_size;
			if ($final_bloque > $count){ // if upper limit is beyond max, this shouldnt be possible !
				$final_bloque = ($i-1)*$block_size + $count-(($i-1) * $block_size);
			}
			if (isset($filter_item))
				echo '<a href="'.$url.'&offset='.$inicio_bloque.'">';
			else
				echo '<a href="'.$url.'&offset='.$inicio_bloque.'">';
			$inicio_bloque_fake = $inicio_bloque + 1;
			// Show ">" marker if paginacion maxima limit reached and last block is shown.
			if (($i==$inicio_pag + $block_limit) AND ($paginacion_maxima ==1)){
				echo "&gt;</a> ";
				$i = $index_counter;
			}
			else {	// Calculate last block (doesnt end with round data, it must be shown if not round to block limit)
				if ($inicio_bloque == $offset)
					echo '<b>[ '.$inicio_bloque_fake.'-'.$final_bloque.' ]</b>';
				else
					echo '[ '.$inicio_bloque_fake.'-'.$final_bloque.' ]';
				echo '</a> ';
			}
		}
		echo "</div>";
		// if exists more registers than i can put in a page (defined by $block_size config parameter)
		// get offset for index calculation

	}
        // End of subrouting to navigate throught blocks

		/*  Now you have a header with blocks rendered, and only need to jump offset records. 
			Tasks you need to do now:
			
			Skip offset records
        	
			$query1="SELECT * FROM $table $filter $order";
            $result=mysql_query($query1);
            mysql_data_seek($result, $offset);
		    $offset_counter = 0;

        	Start viewing data
			
			while ($row=mysql_fetch_array($result) and ($offset_counter < $block_size) ){
            $data=$row["ID_xxx"];
			.
			.
			.
		*/
}
?>