<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to code for Pandora FMS 1.2 graph code and new XML reporting template managemement
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//

require "/var/www/pandora_console/include/config.php";

// retorna el array con los agentes existente en la base de datos
function dameAgentes(){
	$query1="SELECT * FROM tagente;"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// retorna el array con los modulos asignados a un agente que es identificado por su id_agente como parametro
function dameModulos($id_agente){
	$query1="SELECT * FROM tagente_modulo where id_agente=".$id_agente.";"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// retorna el array con los grupos de agentes existente en la base de datos
function dameGruposAgentes(){
	$query1="SELECT * FROM tgrupo;"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// retorna el array con los grupos de agentes que contengan algún agente, 
// devuelve -1 si no hay grupo de agentes con agentes
function dameGruposAgentesConAgentes(){
	$query1="SELECT * FROM tgrupo where id_grupo in (select id_grupo from tagente group by id_grupo);"; 
	$resq1=mysql_query($query1);  
	if ($resq1)
		return $resq1;
	else
		return -1;
}


// retorna el array con los agentes pertenecientes al grupo de agentes identificado por su id
function dameAgentesDelGrupAogentes($id_grupo){
	$query1="SELECT * FROM tagente where id_grupo='".$id_grupo."'"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}


// retorna el array con los grupos existentes en la bd con nombre que se le pasa como parametro
function dameGrupoAgente($id_grupo){
	$query1="SELECT * FROM tgrupo where id_grupo=".$id_grupo.";"; 
	$resq1=mysql_query($query1);  
	$row_grupo=mysql_fetch_array($resq1);
	return $row_grupo;
}

// retorna el id_agent pasandole como argumento el nombre del agente
function dameIdAgente($nombre){
	$query_agent="SELECT id_agente FROM tagente where nombre='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);
	return $row_agent["id_agente"];
}

// retorna el id_agent_modulo pasandole como argumento el id del agente y el nombre del modulo
function dameIdModulo($id_agente,$nombre){
	$query_agent="SELECT id_agente_modulo FROM tagente_modulo where nombre='".$nombre."' and id_agente='".$id_agente."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);		
	return $row_agent["id_agente_modulo"];
}

// retorna el id_grupo pasandole como argumento el nombre del grupo
function dameIdGrupoAgente($nombre){
	$query_agent="SELECT id_grupo FROM tgrupo where nombre='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);
	return $row_agent["id_grupo"];
}

// retorna el id_grupo_modulo pasandole como argumento el nombre del grupo_modulo
function dameIdGrupoModulo($nombre){
	$query_agent="SELECT id_mg FROM tmodule_group where name='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);
	return $row_agent["id_mg"];
}

// devuelve los grupos de modulos existentes para el agente identificado con su id y que se pasa como parametro
function dameGruposModuloDelAgente($id_agente){
	$query1="SELECT TMG.name, TMG.id_mg FROM tmodule_group TMG, tagente_modulo TAM WHERE TMG.id_mg = TAM.id_module_group AND TAM.id_module_group >0 AND TAM.id_agente=".$id_agente." group by TMG.id_mg"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}


// devuelve los modulos del agente identificado por su que no pertenecen a ningun grupo de modulos
function dameModulosSinGrupo($id_agente){
	$query1="SELECT * from tagente_modulo where id_agente='".$id_agente."' and id_module_group=0"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// Funcion que devuelve los modulos de un determinado grupo de modulos y de un agente
function dameModulosDelGrupoModulosAgente($id_agente,$id_mg){
	$query1="Select * from tagente_modulo where id_agente=".$id_agente." and id_module_group=".$id_mg.";";
	$resq1=mysql_query($query1);  
	return $resq1;
}


// devuleve los modulos de un agente determinado y de un grupo de modulos determinado (recive el id_agente y el id del grupo de modulos)
function dameModulosGrupo($id_mg,$id_agente){
	$query1="Select * from tagente_modulo where id_agente=".$id_agente." and id_module_group=".$id_mg.""; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// inserta un objeto pasandole: nombre de la imagen, tipo del objeto, posicion left, posicion top, id tipo del objeto 
function addObject($nom_img, $tipo, $left, $top, $id_tipo){
	if ($tipo=="GA") //como el grupo ya tiene una imagen asignada, la aprovechamos
	{
		$grupoAgente=dameGrupoAgente($id_tipo);
		$nom_img=$grupoAgente["icon"];
	}
	$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`) VALUES ('".$nom_img."', '".$tipo."', $left, $top, $id_tipo);"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	$query2="Select id_objeto from objeto_consola where id_tipo=".$id_tipo.";"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query2. mysql_error());
	$rowidObjeto=mysql_fetch_array($resq2);

	return $rowidObjeto;
}

// retorna el array con los objetos existentes en la base de datos
function dameObjetos(){
	$query1="SELECT * FROM objeto_consola;"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// guardamos una vista nueva pasandole su nombre y una descripcion. Devuelve el id adjudicado a la nueva vista.
function guardarNuevaVista($nombre, $descripcion,$idPerfil){

	$query1="INSERT INTO vistas_consola (`nombre`,`descripcion`) VALUES ('".$nombre."', '".$descripcion."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	$idVista=mysql_insert_id();

	// Insertamos la nueva vista al perfil activo (id=2)
	$query2="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('".$idPerfil."', '".$idVista."');"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query2. mysql_error());   
	return $idVista;
}

// Se crea un nuevo objeto, $nom_img : Nombre de la imagen que lo representa
// 			    $tipo: Tipo de objeto
// 			    $left: posicion respecto a la izquierda
// 			    $top: posicion respecto arriba
// 			    $id_tipo: id del tipo de objeto
// 		 	    $idVista: id de la vista a la que pertenece
// 	Devuelve -1 si la vista que será representada por el objeto no tiene ya un objeto vista de la vista donde insertaremos el objeto.

function nuevoObjEnVista($nom_img,$tipo,$left,$top,$id_tipo,$idVista){
	$objVista=true;	
	if ($tipo == "V"){ // Comprobamos que la vista que será representada por el objeto no tiene ya un objeto vista de la vista donde insertaremos el objeto. :-s
		$objs = dameObjetosVista($id_tipo);
		while ($objeto=mysql_fetch_array($objs)){
			if ($objeto["id_tipo"]==$idVista){
				$objVista=false;
			}
		}
	}
	if ($objVista){
		$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`,`idVista`) VALUES ('".$nom_img."', '".$tipo."', '".$left."', '".$top."', '".$id_tipo."', '".$idVista."');"; 
		$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  
	} else 	
		return -1;
	
}

// Crea un nuevo objeto a partir de uno ya existente (util para copiar objeto de una vista a otra)
function copiaObjEnNuevaVista($idVista,$idObjeto)
{
	$queryObj="SELECT * FROM objeto_consola where id_objeto=".$idObjeto.";"; 
	$resqObj=mysql_query($queryObj); 
	$obj=mysql_fetch_array($resqObj);
 
	$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`,`idVista`) VALUES ('".$obj["nom_img"]."', '".$obj["tipo"]."', '".$obj["left"]."', '".$obj["top"]."', '".$obj["id_tipo"]."', '".$idVista."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  
}


// retorna el array con las vistas existentes en la base de datos
function dameVistas(){
	$queryVista="SELECT * FROM vistas_consola;"; 
	$resqVista=mysql_query($queryVista);  
	return $resqVista;
}

// retorna el array con los ids de los objetos que pertenecen a la vista pasada como parametro
function dameObjetosVista($id_vista){
	$queryObj="SELECT * FROM objeto_consola where idVista=".$id_vista." order by idVista, id_objeto;"; 
	$resqObj=mysql_query($queryObj);  
	return $resqObj;
}

// se devuelve la vista que es referencia por su id que se pasa como parametro
function dameVista($idVista)
{
	$query_vista="SELECT * FROM vistas_consola where idVista='".$idVista."';"; 
	$resq1_vista=mysql_query($query_vista);
	$vista=mysql_fetch_array($resq1_vista);
	return $vista;
}

// se retorna el objeto que es referenciado por su idObjeto
function dameObjeto($idObjeto)
{
	$query_objeto="SELECT * FROM objeto_consola where id_objeto='".$idObjeto."';"; 
	$resq1_objeto=mysql_query($query_objeto);
	$objeto=mysql_fetch_array($resq1_objeto);
	return $objeto;
}

// Funcion que edita un objeto (de momento solo la imagen)
function editarObjeto($idObjeto,$nom_img)
{
	$query_objeto="UPDATE objeto_consola set nom_img='".$nom_img."' where id_objeto='".$idObjeto."';"; 
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;
}

// Funcion que elimina un objeto
function eliminarObjeto($idObjeto)
{
	$query_objeto="DELETE from objeto_consola where id_objeto='".$idObjeto."';"; 
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);
}

// retorna el nombre del objeto referenciado por su tipo y por su y por su id de tipo
function dameNombreObjeto($idTipo,$tipo)
{
	$nombre="";
	switch ($tipo) {
		case "A": //agente
			$query_objeto="SELECT * FROM tagente where id_agente='".$idTipo."';"; 
			$resq1_objeto=mysql_query($query_objeto);
			$objeto=mysql_fetch_array($resq1_objeto);
   			$nombre=$objeto["nombre"];
   			break;
		case "GA": //Grupo Agentes
  			$query_objeto="SELECT * FROM tgrupo where id_grupo='".$idTipo."';"; 
			$resq1_objeto=mysql_query($query_objeto);
			$objeto=mysql_fetch_array($resq1_objeto);
   			$nombre=$objeto["nombre"];
  			break;
		case "M": //Modulo
  			$query_objeto="SELECT * FROM tagente_modulo where id_agente_modulo='".$idTipo."';"; 
			$resq1_objeto=mysql_query($query_objeto);
			$objeto=mysql_fetch_array($resq1_objeto);
   			$nombre=$objeto["nombre"];
  			break;
		case "GM": //Grupo Modulos
  			$query_objeto="SELECT * FROM tmodule_group where id_mg='".$idTipo."';"; 
			$resq1_objeto=mysql_query($query_objeto);
			$objeto=mysql_fetch_array($resq1_objeto);
   			$nombre=$objeto["name"];
  			break;
		case "V": //Vista
  			$query_objeto="SELECT * FROM vistas_consola where idVista='".$idTipo."';"; 
			$resq1_objeto=mysql_query($query_objeto);
			$objeto=mysql_fetch_array($resq1_objeto);
   			$nombre=$objeto["nombre"];
  			break;
	}
	return $nombre;
}

//Inserta un nuevo perfil en la base de datos y retorna su id
function guardarNuevoPefil ($nombre,$descripcion)
{

	$query1="INSERT INTO perfil (`nombre`,`descripcion`) VALUES ('".$nombre."', '".$descripcion."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error()); 
	$idPerfil=mysql_insert_id();
	// Creamos una vista para el perfil
	$query3="INSERT INTO vistas_consola (`nombre`,`descripcion`) VALUES ('default', 'default');"; 
	$resq3=mysql_query($query3) or die('Error, insert query failed'.$query3. mysql_error());  
	$idVista=mysql_insert_id();
	$query2="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('".$idPerfil."', '".$idVista."');"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query1. mysql_error()); 
	return $idPerfil;
}

// retorna el array con las vistas existentes para el perfil especificado mediante parametro
function dameVistasPerfil($idPerfil){
	$queryVista="SELECT * FROM perfil_vista where idPerfil=".$idPerfil.";"; 
	$resqVista=mysql_query($queryVista);  
	return $resqVista;
}

// retorna el array con las vistas existentes para el perfil especificado mediante parametro y tienen su campo activa = 1
function dameVistasPerfilActivas($idPerfil){
	$queryVista="SELECT * FROM perfil_vista where idPerfil=".$idPerfil." and activa=1;"; 
	$resqVista=mysql_query($queryVista);  
	return $resqVista;
}


// Retorna un array con todos los Perfiles excepto los especiales Default(id=1) y PerfilActivo(id=2)
function damePerfiles(){
	$query1="SELECT * FROM perfil;"; 
	$resq1=mysql_query($query1);  
	return $resq1;
}

// se devuelve el perfil referenciado por su id que se pasa como parametro
function damePerfil($idPerfil){
	$query_perfil="SELECT * FROM perfil where idPerfil=".$idPerfil.";"; 
	$resq1_perfil=mysql_query($query_perfil);
	$perfil=mysql_fetch_array($resq1_perfil);
	return $perfil;
}

// Devuelve un perfil cualquiera (Utilizado a la hora de borrar el perfil que esta en curso, con esto se carga otro perfil existente)
function damePerfilCualquiera(){
	$query1="SELECT * FROM perfil;"; 
	$resq1=mysql_query($query1);  
	$perfil=mysql_fetch_array($resq1);
	return $perfil;
}

// Devuelve una vista cualquiera de las que estan visibles (Utilizado a la hora de cerrar una Vista que esta en curso, con esto se carga otra Vista existente)
function dameVistaCualquiera($idPerfil){
	$query1="SELECT * FROM perfil_vista where idPerfil=".$idPerfil." and activa='1';"; 
	$resq1=mysql_query($query1);  
	$vista=mysql_fetch_array($resq1);
	return $vista;
}


// Funcion que elimina una vista y todos sus objetos. Devuelve el codigo de error 1 si es la ultima vista, no borrandola.
function eliminarVista($idVista){
	// comprobamos que no es la ultima vista existente en el perfil
// 	$queryCheck="SELECT * FROM perfil_vista where idPerfil=(Select idPerfil from perfil_vista where idVista=".$idVista.") ;"; 
	$queryCheck="SELECT pv.* FROM perfil_vista pv , perfil_vista pv2 where pv.idPerfil=pv2.idPerfil and pv2.idVista='".$idVista."'";
	$numVistasexe=mysql_query($queryCheck); 
	$numVistas = mysql_num_rows($numVistasexe);

	if ($numVistas > 1)
	{

// 		Obtenemos los objetos de la vista
		$queryObj="SELECT * FROM objeto_consola where idVista=".$idVista.";"; 
		$resqObj=mysql_query($queryObj);  
		while ($objeto=mysql_fetch_array($resqObj)){
	
			$query_objeto="DELETE from objeto_consola where id_objeto='".$objeto['id_objeto']."';"; 
			mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;
	
		}
		
// 		Borramos la vista de la tabla que la relaciona con los perfiles
		$query_vista="DELETE from perfil_vista where idVista='".$idVista."';"; 
		mysql_query($query_vista) or die("Failed Query of " . $query_vista);
	
// 		Borramos la vista 
		$query_vista="DELETE from vistas_consola where idVista='".$idVista."';"; 
		mysql_query($query_vista) or die("Failed Query of " . $query_vista);
		
	
	} else 
		return 1; // ERROR: solo queda una vista y no se puede borrar
}

// Retorna los valores de la consola para el usuario pasado como parametro
function dameEstadoConsola($usuario){

	$query1="SELECT * FROM estado_consola where id_usuario='".$usuario."';"; 
	$resq1=mysql_query($query1);  
	return mysql_fetch_array($resq1);
}

// Funcion que edita una Vista (id de la vista , nombre , descripcion)
function editarVista($idVista,$nombre,$descripcion){

	$query_vista="UPDATE vistas_consola set nombre='".$nombre."' , descripcion='".$descripcion."' where idVista='".$idVista."';"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;
}

// Funcion que edita un Perfil (id del Perfil , nombre , descripcion)
function editarPerfil($idPerfil,$nombre,$descripcion)
{


	$query_perfil="UPDATE perfil set Nombre='".$nombre."' , Descripcion='".$descripcion."' where idPerfil='".$idPerfil."';"; 
	mysql_query($query_perfil) or die("Failed Query of " . $query_perfil);;


	

}


// Elimina el perfil y las vistas asociadas al perfil referenciado por su id, y devuelve -1 como codigo de error si es el ultimo perfil, para indicar de que no se ha borrado por ser el ultimo.Si se ha borrado con exito, devuelve el id del perfil borrado
function eliminarPerfil($idPerfil)
{



	$queryCheck="SELECT * FROM perfil ;"; 
	$numPerfilesexe=mysql_query($queryCheck); 
	$numPerfiles = mysql_num_rows($numPerfilesexe);

	if ($numPerfiles > 1)
	{
// 		Obtenemos las vistas del perfil para borrarlas
		$queryVistas="SELECT * FROM perfil_vista where idPerfil=".$idPerfil.";"; 
		$resqVistas=mysql_query($queryVistas);  
		while ($vista=mysql_fetch_array($resqVistas)){
			$idVista=$vista["idVista"];
			
	// 		Obtenemos los objetos de la vista
			$queryObj="SELECT * FROM objeto_consola where idVista=".$idVista.";"; 
			$resqObj=mysql_query($queryObj);  
			while ($objeto=mysql_fetch_array($resqObj)){
		
				$query_objeto="DELETE from objeto_consola where id_objeto='".$objeto['id_objeto']."';"; 
				mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;
		
			}
			
	// 		Borramos la vista de la tabla que la relaciona con los perfiles
			$query_vista="DELETE from perfil_vista where idVista='".$idVista."';"; 
			mysql_query($query_vista) or die("Failed Query of " . $query_vista);
		
	// 		Borramos la vista 
			$query_vista="DELETE from vistas_consola where idVista='".$idVista."';"; 
			mysql_query($query_vista) or die("Failed Query of " . $query_vista);
			
  		}
		
// 		Borramos el perfil de la tabla de relaciones con la vista
		$query_perfil="DELETE from perfil_vista where idPerfil='".$idPerfil."';"; 
		mysql_query($query_perfil) or die("Failed Query of " . $query_perfil);
		
// 		Borramos el perfil 
		$query_perfil="DELETE from perfil where idPerfil='".$idPerfil."';"; 
		mysql_query($query_perfil) or die("Failed Query of " . $query_perfil);
	
		return $idPerfil;
	}else return -1;

	

}

// Funcion que pone a 1 el campo "activa" de la tabla perfil_vista (esto hace que la vista se visualice en la consola)

function activarVista($idVista)
{


	$query_vista="UPDATE perfil_vista set activa=1 where idVista=".$idVista.";"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;


	

}

// Funcion que pone a 0 el campo "activa" de la tabla perfil_vista (esto hace que la vista NO se visualice en la consola)

function desactivarVista($idVista)
{


	$query_vista="UPDATE perfil_vista set activa = 0 where idVista='".$idVista."';"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;


	
}

// Funcion que devuelve 1 si es la ultima vista activa del perfil, o devuelve 0 si no lo es
function es_ultimaVistaActiva($idPerfil)
{



	$queryCheck="SELECT * FROM perfil_vista where idPerfil=".$idPerfil." and activa=1 ;"; 
	$numVistasexe=mysql_query($queryCheck); 
	$numVistas = mysql_num_rows($numVistasexe);
	if ($numVistas > 1)
		return 0;
	else return 1;

}

// Funcion que devuelve 1 si la vista del perfil es activa, o devuelve 0 si no lo esta
function esVistaActiva($idVista,$idPerfil)
{



	$queryCheck="SELECT * FROM perfil_vista where idVista='".$idVista."' and idPerfil=".$idPerfil.";"; 
	$vista_exe=mysql_query($queryCheck); 
	$vista=mysql_fetch_array($vista_exe);
	return $vista["activa"];

}


// Crea una relacion (linea) entre dos objetos
function crearRelacionObjetos($idObjeto1, $idObjeto2)
{



	$query1="INSERT INTO relacion_objetos (`idObjeto1`,`idObjeto2`) VALUES ('".$idObjeto1."', '".$idObjeto2."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	

}

//Devuelve todas las relaciones del objeto pasado como parametro y que sea el Objeto 1 de la relacion, si no tiene ninguna relación, devuelve -1
function dameRelacionesObjeto($idObjeto)
{


	$query1="SELECT * FROM relacion_objetos where idObjeto1='".$idObjeto."' or idObjeto2='".$idObjeto."';"; 
	$resq1=mysql_query($query1);  

	

	$numRelaciones = mysql_num_rows($resq1);
	if ($numRelaciones > 0)
		return $resq1;
	else return -1;
	

}

//Funcion que devuelve 1 si el objeto pertence a la vista y 0 si no
function esObjetoDeVista($idObjeto,$idVista)
{

	$query1="SELECT * FROM objeto_consola where id_objeto='".$idObjeto."' and idVista='".$idVista."';"; 
	$resq1=mysql_query($query1);  

	

	$objVista = mysql_num_rows($resq1);
	if ($objVista > 0)
		return 1;
	else return 0;

}

// Funcion que devuelve las relaciones entre objetos de la vista que se le pasa como parametro.
function dameRelacionesVista($idVista)
{


	$query1="Select idObjeto1, idObjeto2 from relacion_objetos, objeto_consola where idVista='".$idVista."' and idObjeto1 = id_objeto ;"; 
	$resq1=mysql_query($query1);

	

	return $resq1;
	
}

// Funcion que elimina una relacion entre dos objetos
function eliminarRelacion($obj1, $obj2)
{

	$query_relacion="DELETE from relacion_objetos where idObjeto1='".$obj1."' and idObjeto2='".$obj2."';"; 
	mysql_query($query_relacion) or die("Failed Query of " . $query_relacion);

	
	
}

// Devuelve el estado de un Objeto 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoObjeto($idObjeto)
{

	$estado=-1000;



	$query="Select * from objeto_consola where id_objeto='".$idObjeto."';"; 
	$objetoexe=mysql_query($query) or die("Failed Query of " . $query);
	$objeto=mysql_fetch_array($objetoexe);

	// Comprobamos que esta conectado el objeto
	$query_objeto="SELECT * FROM tagente_estado ts, tagente_modulo tm where ts.id_agente_modulo='".$objeto["id_tipo"]."' and tm.id_agente_modulo='".$objeto["id_tipo"]."';"; 
	$resq1_objeto=mysql_query($query_objeto);
	$objeto_estado=mysql_fetch_array($resq1_objeto);

	$agent_down=esConectadoObjeto($objeto["id_tipo"],$objeto["tipo"]);
/*
	$est_interval = $objeto_estado["module_interval"];
	if (($est_interval != $intervalo) && ($est_interval > 0)) {
		$temp_interval = $est_interval;
	} else {
		$temp_interval = $intervalo;
		}

	

	$ahora=date("Y/m/d H:i:s");
	$seconds = strtotime($ahora) - strtotime($objeto_estado["timestamp"]);
	if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
		$agent_down = 1;
	else
		$agent_down = 0;*/

	

	if ($agent_down==1) // Desconectado
	{
		$estado=-1;
	}else
	{
		// Comprobamos si existe una regla de estado para el objeto, sino se comporta segun el estandar
		$query_relacion="Select * from relacion_estado where id_objeto='".$idObjeto."';"; 
		$result_relacion=mysql_query($query_relacion) or die("Failed Query of " . $query_relacion);
		$hay_relacion = mysql_num_rows($result_relacion);
		
		if ($hay_relacion > 0)
			$relacion=mysql_fetch_array($result_relacion);
		else $relacion=-1;		

		if ($relacion==-1) // Estado estandar
		{
			$tipo=$objeto["tipo"];
			switch ($tipo) {
				case "A": //agente
					$estado=dameEstadoAgente($objeto["id_tipo"]);
					break;
				case "GA": //Grupo Agentes
					$estado=dameEstadoGrupoAgentes($objeto["id_tipo"]);
					break;
				case "M": //Modulo
					$estado=dameEstadoModulo($objeto["id_tipo"]);
					break;
				case "GM": //Grupo Modulos
					$estado=dameEstadoGrupoModulos($objeto["id_tipo"]);
					break;
				case "V": //Vista
					$estado=dameEstadoVista($objeto["id_tipo"]);
					break;
	
			}
		}else // Calculamos su estado dependiendo de la expresion que se le ha asignado
		{
		
			$estado = dameEstadoEditadoObjeto($idObjeto,$relacion["relacion"]);

		}

	}
	
	
	
// 	mensaje($estado);

	if ($estado==-1000) 
		return -1;
	else
		return $estado;
}


//Funcion que devuelve el estado de un modulo 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoModulo($idModulo)
{
	$estado=-1000;

	$query_objeto="SELECT * FROM tagente_estado where id_agente_modulo='".$idModulo."';"; 
	$resq1_objeto=mysql_query($query_objeto);
	$objeto_estado=mysql_fetch_array($resq1_objeto);
	if ($objeto_estado["estado"]==1)
	{
		if ($objeto_estado["cambio"]==1)
		{
			$estado=2;		
		}else 
		{
			$estado=0;
		}		
	}else
	{
		$estado=1;		
	}

	if ($estado==-1000) 
		return -1;
	else
		return $estado;
}

//Funcion que devuelve el estado de un grupo de modulos 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoGrupoModulos($idGrupo)
{
	$estado=1;
	$query="Select * from tagente_modulo where id_module_group='".$idGrupo."';";
	$resq1=mysql_query($query);
	while ($modulo=mysql_fetch_array($resq1))
	{
		$sub_estado=dameEstadoModulo($modulo["id_agente_modulo"]);
		if ($estado == 1) // Si esta en estado BIEN y alguno de los modulos pasa a otro estado -> cambiar el estado
		{
			if (($sub_estado==2) or ($usb_estado==0))
			{
				$estado=$sub_estado;		
			}
		}
		if (($estado == 2) and ($sub_estado=0)) // Asegura que se toma el estado mas grave de los elementos de los que esta compuesto el grupo
		{
			$estado=$sub_estado;
		}

	}
	return $estado;
}

//Funcion que devuelve el estado de agente 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoAgente($idAgente)
{
	$estado=1;
	$query="Select * from tagente_modulo where id_agente='".$idAgente."';";
	$resq1=mysql_query($query);
	while ($modulo=mysql_fetch_array($resq1))
	{
		$sub_estado=dameEstadoModulo($modulo["id_agente_modulo"]);
		if ($estado == 1) // Si esta en estado BIEN y alguno de los modulos pasa a otro estado -> cambiar el estado
		{
			if (($sub_estado==2) or ($usb_estado==0))
			{
				$estado=$sub_estado;		
			}
		}
		if (($estado == 2) and ($sub_estado=0)) // Asegura que se toma el estado mas grave de los elementos de los que esta compuesto el grupo
		{
			$estado=$sub_estado;
		}

	}
	return $estado;
}

//Funcion que devuelve el estado de un grupo de agentes 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoGrupoAgentes($idGrupoAgente)
{
	$estado=1;
	$query="Select * from tagente where id_grupo='".$idGrupoAgente."';";
	$resq1=mysql_query($query);
	while ($agente=mysql_fetch_array($resq1))
	{
		$sub_estado=dameEstadoAgente($agente["id_agente"]);
		if ($estado == 1) // Si esta en estado BIEN y alguno de los modulos pasa a otro estado -> cambiar el estado
		{
			if (($sub_estado==2) or ($usb_estado==0))
			{
				$estado=$sub_estado;		
			}
		}
		if (($estado == 2) and ($sub_estado=0)) // Asegura que se toma el estado mas grave de los elementos de los que esta compuesto el grupo
		{
			$estado=$sub_estado;
		}

	}
	return $estado;
}


//Funcion que devuelve el estado de una vista 0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO
function dameEstadoVista($idVista)
{

	$estado=1;
	$query="Select * from objeto_consola where idVista='".$idVista."';";
	$resq1=mysql_query($query);
	while ($objeto_vista=mysql_fetch_array($resq1))
	{
		switch ($objeto_vista["tipo"]) {
			case "A": //agente
				$sub_estado=dameEstadoAgente($objeto_vista["id_tipo"]);
				break;
			case "GA": //Grupo Agentes
				$sub_estado=dameEstadoGrupoAgentes($objeto_vista["id_tipo"]);
				break;
			case "M": //Modulo
				$sub_estado=dameEstadoModulo($objeto_vista["id_tipo"]);
				break;
			case "GM": //Grupo Modulos
				$sub_estado=dameEstadoGrupoModulos($objeto_vista["id_tipo"]);
				break;
			case "V": //Vista
				$sub_estado=dameEstadoVista($objeto_vista["id_tipo"]);
				break;
		}	

		if ($estado == 1) // Si esta en estado BIEN y alguno de los modulos pasa a otro estado -> cambiar el estado
		{
			if (($sub_estado==2) or ($usb_estado==0))
			{
				$estado=$sub_estado;		
			}
		}
		if (($estado == 2) and ($sub_estado=0)) // Asegura que se toma el estado mas grave de los elementos de los que esta compuesto el grupo
		{
			$estado=$sub_estado;
		}

	}


	return $estado;

	
	
}

function comprobarAlertaObjeto($idObjeto)
{

	$alerta=0;



	$query="Select * from objeto_consola where id_objeto='".$idObjeto."';"; 
	$objetoexe=mysql_query($query) or die("Failed Query of " . $query);
	$objeto=mysql_fetch_array($objetoexe);

	$tipo=$objeto["tipo"];
	switch ($tipo) {
		case "A": //agente
			$alerta=comprobarAlertaAgente($objeto["id_tipo"]);
			break;
		case "GA": //Grupo Agentes
			$alerta=comprobarAlertaGrupoAgentes($objeto["id_tipo"]);
			break;
		case "M": //Modulo
			$alerta=comprobarAlertaModulo($objeto["id_tipo"]);
			break;
		case "GM": //Grupo Modulos
			$alerta=comprobarAlertaGrupoModulos($objeto["id_tipo"]);
			break;
		case "V": //Vista
			$alerta=comprobarAlertaVista($objeto["id_tipo"]);
			break;

	}

	
	return $alerta;
	
	
}


function comprobarAlertaModulo($idModulo)
{

	$query2="SELECT * FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$idModulo;
	$rowdup2=mysql_query($query2);
	if (mysql_num_rows($rowdup2) > 0)
	{	
	
		return 1;
	}
		
	
	return 0;

}

function comprobarAlertaAgente($idAgente)
{
	$query2="SELECT * FROM tagente_modulo WHERE id_agente =".$idAgente;
	$modulos=mysql_query($query2);
	while ($modulo=mysql_fetch_array($modulos))
	{
		$query2="SELECT * FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$modulo["id_agente_modulo"];
		$alertas=mysql_query($query2);
		if (mysql_num_rows($alertas) > 0)
		{	
		
			return 1;
		}
		
	}
	return 0;

}

function comprobarAlertaGrupoAgentes($id_grupo)
{

	$query2="SELECT * FROM tagente_modulo WHERE id_agente in (select id_agente from tagente where id_grupo='".$id_grupo."') ";
	$modulos=mysql_query($query2);
	while ($modulo=mysql_fetch_array($modulos))
	{
		$query2="SELECT * FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$modulo["id_agente_modulo"];
		$alertas=mysql_query($query2);
		if (mysql_num_rows($alertas) > 0)
		{	
		
			return 1;
		}
		
	}
	return 0;

}

function comprobarAlertaGrupoModulos($id_mg)
{
	$query2="SELECT * FROM tagente_modulo WHERE id_module_group in (select id_mg from tmodule_group where id_mg='".$id_mg."') ";
	$modulos=mysql_query($query2);
	while ($modulo=mysql_fetch_array($modulos))
	{
		$query2="SELECT * FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$modulo["id_agente_modulo"];
		$alertas=mysql_query($query2);
		if (mysql_num_rows($alertas) > 0)
		{	
		
			return 1;
		}
		
	}
	return 0;

}

function comprobarAlertaVista($idVista)
{
	$query="Select * from objeto_consola where idVista='".$idVista."';";
	$resq1=mysql_query($query);
	while ($objeto_vista=mysql_fetch_array($resq1))
	{
		switch ($objeto_vista["tipo"]) {
			case "A": //agente
				if (comprobarAlertaAgente($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "GA": //Grupo Agentes
				if ( $sub_estado=comprobarAlertaGrupoAgentes($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "M": //Modulo
				if ( $sub_estado=comprobarAlertaModulo($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "GM": //Grupo Modulos
				if ($sub_estado=comprobarAlertaGrupoModulos($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "V": //Vista
				if ($sub_estado=comprobarAlertaVista($objeto_vista["id_tipo"]) == 1) return 1;
				break;
		}	
	}
}

// Guarda en la base de datos el estado left y top del objeto
function guardarPosicion($idObjeto,$left,$top)
{



	$query_objeto="UPDATE `objeto_consola` SET `left` = '".$left."', `top` = '".$top."' WHERE `id_objeto` = ".$idObjeto." LIMIT 1;";
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;


	


}

// Crea una relacion de estados (el estado de uno dependera del estado del otro ) entre dos objetos
// Devuelve -1 si se intenta inserta una relacion para un objeto que ya la tiene (duplicate key)
function crearRelacionEstado($idObjeto, $expresion)
{



	$query1="INSERT INTO relacion_estado (`id_objeto`,`relacion`) VALUES ('".$idObjeto."', '".$expresion."');"; 
	$resq1=mysql_query($query1);
	if (mysql_errno()==1062) // duplicate key
	{
		return -1;
	}  

	

}


// Devuelve el estado de un Objeto para el que existe una relacion de estado 0=MAL, 1=BIEN, 
function dameEstadoEditadoObjeto ($idObjeto, $expresion)
{	

	$estado=-1;
	$subObjetos=array();

	// Obtenemos los ids de los objetos de los que depende su estado 

	$params = preg_split("/(\*|\+|\!|\(|\))/" ,$expresion,-1,PREG_SPLIT_NO_EMPTY); 
	for ($i=0; $i<sizeof($params);$i++)
	{
		$sub_objetos= explode("#",$params[$i]);
		$subObjetos[$sub_objetos[1]] = 0;
	}

	// Por cada objeto obtenemos su estado actual	

	foreach($subObjetos as $idSubObjeto => $subEstado) 
	{
		$estadoSubObjeto = dameEstadoObjeto($idSubObjeto);
		$subObjetos[$idSubObjeto]=$estadoSubObjeto;
	}

	// Traducimos la expresion en codigo php para poder ejecutarlo
	$condicion = $expresion;
	$condicion = str_replace("*"," && ",$condicion);
	$condicion = str_replace("+"," || ",$condicion);
	$condicion = str_replace("#"," ",$condicion);
	
	// Cambiamos el id del objeto por su estado
	foreach($subObjetos as $idSubObjeto => $subEstado) 
	{
		$condicion = str_replace($idSubObjeto,$subEstado,$condicion);
	}
// 	$condicion = str_replace("1","TRUE",$condicion);
// 	$condicion = str_replace("0","FALSE",$condicion);
// 	if ("1" && "1"){$estado=1;}else{$estado=0;}
	eval(" if ($condicion == 1) {\$estado=1;}else{\$estado=0;}");
// 	echo "$condicion = ".$estado;
	return $estado;
	
}

// Funcion que devuelve las relaciones de estado existentes en una vista
function dameRelacionesEstadoVista($idVista)
{


	$query1="Select rs.id_objeto from relacion_estado rs, objeto_consola oc where idVista='".$idVista."' and rs.id_objeto = oc.id_objeto ;"; 
	$resq1=mysql_query($query1);

	

	return $resq1;

}

// Funcion que elimina la relacion de estado pasada como argumento
function eliminarRelacionEstado($idRelacion)
{

	$query_relacion="DELETE from relacion_estado where id_objeto='".$idRelacion."';"; 
	mysql_query($query_relacion) or die("Failed Query of " . $query_relacion);

}

// Funcion que devuelve un 1 si el objeto no esta conectado, y un 0 si si lo está
function esConectadoObjeto($idTipo,$tipo)
{
	$conectado = 1;

	switch ($tipo) {
		case "A": //agente
			$conectado = comprobarConexionAgente($idTipo);
			break;
		case "GA": //Grupo Agentes
			$conectado=comprobarConexionGrupoAgentes($idTipo);
			break;
		case "M": //Modulo
			$conectado=comprobarConexionModulo($idTipo);
			break;
		case "GM": //Grupo Modulos
			$conectado=comprobarConexionGrupoModulos($idTipo);
			break;
		case "V": //Vista
			$conectado=comprobarConexionVista($idTipo);
			break;
	}	
	

	return $conectado;
}

// Funcion que devuelve un 1 si el modulo no esta conectado, y un 0 si si lo está
function comprobarConexionModulo($idTipo)
{
	$conectado = 1;

	$ahora=date("Y/m/d H:i:s"); 

	$sql="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$idTipo;
	$result=mysql_query($sql);
	if ($modulo = mysql_fetch_array($result)){
		$module_interval = $modulo["module_interval"];
		if ($module_interval > 0)
			$intervalo_comp = $module_interval;
		else {
			$sql_agent="SELECT * FROM tagente WHERE id_agente = ".$modulo["id_agente"];
			$result_agent=mysql_query($sql_agent);
			if ($agente = mysql_fetch_array($result_agent)){
				$intervalo = $agente["intervalo"];
			}
			$intervalo_comp = $intervalo;
		}
	}
	$sql_estado="SELECT * FROM tagente_estado WHERE id_agente = ".$modulo["id_agente"];
	$result_estado=mysql_query($sql_estado);
	if ($r_estado = mysql_fetch_array($result_estado))
		$ultimo_contacto_modulo = $r_estado["timestamp"];

	# Defines if module is down (interval x 2 > time last contact)
	if ($ultimo_contacto_modulo != "2000-00-00 00:00:00"){
		$seconds = strtotime($ahora) - strtotime($ultimo_contacto_modulo);
		if ($seconds >= ($intervalo_comp*2)){
			$conectado = 1;
		}else $conectado = 0;
	}

	return $conectado;

}


function comprobarConexionGrupoModulos($id_mg)
{
	

	$query2="SELECT * FROM tagente_modulo WHERE id_module_group in (select id_mg from tmodule_group where id_mg='".$id_mg."') ";
	$modulos=mysql_query($query2);
	while ($modulo=mysql_fetch_array($modulos))
	{
		$modCon = comprobarConexionModulo($modulo["id_agente_modulo"]);

		
		if ($modCon == 1)
		{	
			return 1;
			
		}
		
	}
	return 0;

}


// Funcion que devuelve un 1 si el modulo no esta conectado, y un 0 si si lo está
function comprobarConexionAgente($idTipo)
{
	$conectado = 0;

	$modulos=dameModulos($idTipo);
		
	while ($modulo=mysql_fetch_array($modulos))
	{
		if (comprobarConexionModulo($modulo["id_agente_modulo"]) == 1)	
		{
			$conectado = 1;	
		}
	}

	return $conectado;
}

function comprobarConexionGrupoAgentes($id_grupo){
	$query2="SELECT * FROM tagente_modulo WHERE id_agente in (select id_agente from tagente where id_grupo='".$id_grupo."') ";
	$modulos=mysql_query($query2);
	while ($modulo=mysql_fetch_array($modulos))
	{
		$modCon = comprobarConexionAgente($idTipo);
		if ($modCon == 1)
		{	
		
			return 1;
		}
		
	}
	return 0;
}


function comprobarConexionVista($idVista){
	$query="Select * from objeto_consola where idVista='".$idVista."';";
	$resq1=mysql_query($query);
	while ($objeto_vista=mysql_fetch_array($resq1))
	{
		switch ($objeto_vista["tipo"]) {
			case "A": //agente
				if (comprobarConexionAgente($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "GA": //Grupo Agentes
				if ( $sub_estado=comprobarConexionGrupoAgentes($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "M": //Modulo
				if ( $sub_estado=comprobarConexionModulo($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "GM": //Grupo Modulos
				if ($sub_estado=comprobarConexionGrupoModulos($objeto_vista["id_tipo"]) == 1) return 1;
				break;
			case "V": //Vista
				if ($sub_estado=comprobarConexionVista($objeto_vista["id_tipo"]) == 1) return 1;
				break;
		}	
	}
	return 0;
}

// Funcion que devuelve el ultimo valor recogido por el modulo
function ultimoValorModulo($idModulo){
	$query="Select * from tagente_datos where id_agente_modulo='".$idModulo."' order by timestamp desc limit 1;";
	$resq1=mysql_query($query);
	$modulo=mysql_fetch_array($resq1);
	return $modulo["datos"];
}



// Funcion que sustituye el actual icono de un objeto Modulo por su grafica
function setImagenGrafica($idModulo){
	$query_objeto="UPDATE objeto_consola SET nom_img='grafica' WHERE id_objeto='".$idModulo."';";
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;
}

?>