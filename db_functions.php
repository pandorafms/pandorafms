<?php

//abrir y seleccionar la base de datos de pandora

function opendb()
{
	$link = mysql_connect(localhost, 'pandora', 'pandora');
	if (!$link) {
   		die('Could not connect: ' . mysql_error());
	}
	if (!mysql_select_db('pandora_console', $link)) {
   		echo 'Could not select database';
   		exit;
	}
}

//cerrar la base de datos
function closedb()
{
	mysql_close();

}

// retorna el array con los agentes existente en la base de datos
function dameAgentes(){
	

	opendb();

	$query1="SELECT * FROM tagente;"; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// retorna el array con los modulos asignados a un agente que es identificado por su id_agente como parametro
function dameModulos($id_agente){
	

	opendb();

	$query1="SELECT * FROM tagente_modulo where id_agente=".$id_agente.";"; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// retorna el array con los agentes existente en la base de datos
function dameGruposAgentes(){
	

	opendb();

	$query1="SELECT * FROM tgrupo;"; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// retorna el array con los grupos existentes en la bd con nombre que se le pasa como parametro
function dameGrupoAgente($id_grupo){
	

	opendb();

	$query1="SELECT * FROM tgrupo where id_grupo=".$id_grupo.";"; 
	$resq1=mysql_query($query1);  
	$row_grupo=mysql_fetch_array($resq1);

	closedb();

	return $row_grupo;

}

// retorna el id_agent pasandole como argumento el nombre del agente
function dameIdAgente($nombre)
{
	opendb();

	$query_agent="SELECT id_agente FROM tagente where nombre='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);

	closedb();
		
	return $row_agent["id_agente"];
}

// retorna el id_agent_modulo pasandole como argumento el id del agente y el nombre del modulo
function dameIdModulo($id_agente,$nombre)
{
	opendb();

	$query_agent="SELECT id_agente_modulo FROM tagente_modulo where nombre='".$nombre."' and id_agente='".$id_agente."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);

	closedb();
		
	return $row_agent["id_agente_modulo"];
}

// retorna el id_grupo pasandole como argumento el nombre del grupo
function dameIdGrupoAgente($nombre)
{
	opendb();

	$query_agent="SELECT id_grupo FROM tgrupo where nombre='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);

	closedb();
		
	return $row_agent["id_grupo"];
}

// retorna el id_grupo_modulo pasandole como argumento el nombre del grupo_modulo
function dameIdGrupoModulo($nombre)
{
	opendb();

	$query_agent="SELECT id_mg FROM tmodule_group where name='".$nombre."';"; 
	$resq1_agent=mysql_query($query_agent);
	$row_agent=mysql_fetch_array($resq1_agent);

	closedb();
		
	return $row_agent["id_mg"];
}

// devuelve los grupos de modulos existentes para el agente identificado con su id y que se pasa como parametro
function dameGruposModuloDelAgente($id_agente)
{
	opendb();

	$query1="SELECT TMG.name, TMG.id_mg FROM tmodule_group TMG, tagente_modulo TAM WHERE TMG.id_mg = TAM.id_module_group AND TAM.id_module_group >0 AND TAM.id_agente=".$id_agente." group by TMG.id_mg"; 

	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;
}

// Funcion que devuelve los modulos de un determinado grupo de modulos y de un agente
function dameModulosDelGrupoModulosAgente($id_agente,$id_mg)
{
	opendb();

	$query1="Select nombre from tagente_modulo where id_agente=".$id_agente." and id_module_group=".$id_mg.";";

	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;


}


// devuleve los modulos de un agente determinado y de un grupo de modulos determinado (recive el id_agente y el id del grupo de modulos)
function dameModulosGrupo($id_mg,$id_agente)
{
	opendb();

	$query1="Select * from tagente_modulo where id_agente=".$id_agente." and id_module_group=".$id_mg.""; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// inserta un objeto pasandole: nombre de la imagen, tipo del objeto, posicion left, posicion top, id tipo del objeto
function addObject($nom_img, $tipo, $left, $top, $id_tipo)
{
	
	if ($tipo=="GA") //como el grupo ya tiene una imagen asignada, la aprovechamos
	{
		$grupoAgente=dameGrupoAgente($id_tipo);
		$nom_img=$grupoAgente["icon"];
	}

	opendb();
	$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`) VALUES ('".$nom_img."', '".$tipo."', $left, $top, $id_tipo);"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	$query2="Select id_objeto from objeto_consola where id_tipo=".$id_tipo.";"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query2. mysql_error());
	$rowidObjeto=mysql_fetch_array($resq2);

	closedb();
	return $rowidObjeto;
}

// retorna el array con los objetos existentes en la base de datos
function dameObjetos(){
	

	opendb();

	$query1="SELECT * FROM objeto_consola;"; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// guardamos una vista nueva pasandole su nombre y una descripcion. Devuelve el id adjudicado a la nueva vista.
function guardarNuevaVista($nombre, $descripcion,$idPerfil)
{
	

	opendb();
	$query1="INSERT INTO vistas_consola (`nombre`,`descripcion`) VALUES ('".$nombre."', '".$descripcion."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	$idVista=mysql_insert_id();

	// Insertamos la nueva vista al perfil activo (id=2)
	$query2="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('".$idPerfil."', '".$idVista."');"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query2. mysql_error());   

	closedb();

	return $idVista;
}

// Se crea un nuevo objeto, $nom_img : Nombre de la imagen que lo representa
// 			    $tipo: Tipo de objeto
// 			    $left: posicion respecto a la izquierda
// 			    $top: posicion respecto arriba
// 			    $id_tipo: id del tipo de objeto
// 		 	    $idVista: id de la vista a la que pertenece
function nuevoObjEnVista($nom_img,$tipo,$left,$top,$id_tipo,$idVista)
{
	opendb();

	$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`,`idVista`) VALUES ('".$nom_img."', '".$tipo."', '".$left."', '".$top."', '".$id_tipo."', '".$idVista."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	closedb();

}

// Crea un nuevo objeto a partir de uno ya existente (util para copiar objeto de una vista a otra)
function copiaObjEnNuevaVista($idVista,$idObjeto)
{
	opendb();

	$queryObj="SELECT * FROM objeto_consola where id_objeto=".$idObjeto.";"; 
	$resqObj=mysql_query($queryObj); 
	$obj=mysql_fetch_array($resqObj);
 
	$query1="INSERT INTO objeto_consola (`nom_img`,`tipo`,`left`,`top`,`id_tipo`,`idVista`) VALUES ('".$obj["nom_img"]."', '".$obj["tipo"]."', '".$obj["left"]."', '".$obj["top"]."', '".$obj["id_tipo"]."', '".$idVista."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	closedb();

}


// retorna el array con las vistas existentes en la base de datos
function dameVistas(){
	

	opendb();

	$queryVista="SELECT * FROM vistas_consola;"; 
	$resqVista=mysql_query($queryVista);  

	closedb();

	return $resqVista;

}

// retorna el array con los ids de los objetos que pertenecen a la vista pasada como parametro
function dameObjetosVista($id_vista){
	

	opendb();

	$queryObj="SELECT * FROM objeto_consola where idVista=".$id_vista.";"; 
	$resqObj=mysql_query($queryObj);  

	closedb();

	return $resqObj;

}

// se devuelve la vista que es referencia por su id que se pasa como parametro
function dameVista($idVista)
{
	opendb();

	$query_vista="SELECT * FROM vistas_consola where idVista='".$idVista."';"; 
	$resq1_vista=mysql_query($query_vista);
	$vista=mysql_fetch_array($resq1_vista);

	closedb();
		
	return $vista;
}

// se retorna el objeto que es referenciado por su idObjeto
function dameObjeto($idObjeto)
{
	opendb();

	$query_objeto="SELECT * FROM objeto_consola where id_objeto='".$idObjeto."';"; 
	$resq1_objeto=mysql_query($query_objeto);
	$objeto=mysql_fetch_array($resq1_objeto);

	closedb();
		
	return $objeto;
}

// Funcion que edita un objeto (de momento solo la imagen)
function editarObjeto($idObjeto,$nom_img)
{
	opendb();

	$query_objeto="UPDATE objeto_consola set nom_img='".$nom_img."' where id_objeto='".$idObjeto."';"; 
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);;


	closedb();

}

// Funcion que elimina un objeto
function eliminarObjeto($idObjeto)
{
	opendb();

	$query_objeto="DELETE from objeto_consola where id_objeto='".$idObjeto."';"; 
	mysql_query($query_objeto) or die("Failed Query of " . $query_objeto);


	closedb();

}

// retorna el nombre del objeto referenciado por su tipo y por su y por su id de tipo
function dameNombreObjeto($idTipo,$tipo)
{

	$nombre="";
	opendb();

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

	

	closedb();
		
	return $nombre;
}

//Inserta un nuevo perfil en la base de datos y retorna su id
function guardarNuevoPefil ($nombre,$descripcion)
{
	opendb();
	$query1="INSERT INTO perfil (`nombre`,`descripcion`) VALUES ('".$nombre."', '".$descripcion."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error()); 
	
	$idPerfil=mysql_insert_id();

	// Creamos una vista para el perfil
	$query3="INSERT INTO vistas_consola (`nombre`,`descripcion`) VALUES ('default', 'default');"; 
	$resq3=mysql_query($query3) or die('Error, insert query failed'.$query3. mysql_error());  

	$idVista=mysql_insert_id();


	$query2="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('".$idPerfil."', '".$idVista."');"; 
	$resq2=mysql_query($query2) or die('Error, insert query failed'.$query1. mysql_error()); 
	closedb();

	return $idPerfil;
}

// retorna el array con las vistas existentes para el perfil especificado mediante parametro
function dameVistasPerfil($idPerfil){
	

	opendb();

	$queryVista="SELECT * FROM perfil_vista where idPerfil=".$idPerfil.";"; 
	$resqVista=mysql_query($queryVista);  

	closedb();

	return $resqVista;

}

// retorna el array con las vistas existentes para el perfil especificado mediante parametro y tienen su campo activa = 1
function dameVistasPerfilActivas($idPerfil){
	

	opendb();

	$queryVista="SELECT * FROM perfil_vista where idPerfil=".$idPerfil." and activa=1;"; 
	$resqVista=mysql_query($queryVista);  

	closedb();

	return $resqVista;

}


// // Esta funcion intenta obtener el id del perfil que esta cargado actualmente
// function obtenerPerfilActivo()
// {
// 	opendb();
// 
// 	$queryVista="SELECT * FROM perfil_vista where idPerfil=2;"; 
// 	$resqVista=mysql_query($queryVista);  
// 	$perfil_vista=mysql_fetch_array($resqVista);
// 	
// 	$queryVista2="SELECT * FROM perfil_vista where idPerfil<>'2' and idVista='".$perfil_vista['idVista']."';"; 
// 	$resqVista2=mysql_query($queryVista2);  
// 	$perfil=mysql_fetch_array($resqVista2);
// 
// 	closedb();
// 
// 	return $perfil['idPerfil'];
// 
// }

// Retorna un array con todos los Perfiles excepto los especiales Default(id=1) y PerfilActivo(id=2)

function damePerfiles(){
	opendb();

	$query1="SELECT * FROM perfil;"; 
	$resq1=mysql_query($query1);  

	closedb();

	return $resq1;

}

// se devuelve el perfil referenciado por su id que se pasa como parametro
function damePerfil($idPerfil)
{
	opendb();

	$query_perfil="SELECT * FROM perfil where idPerfil=".$idPerfil.";"; 
	$resq1_perfil=mysql_query($query_perfil);
	$perfil=mysql_fetch_array($resq1_perfil);

	closedb();
		
	return $perfil;
}

// Devuelve un perfil cualquiera (Utilizado a la hora de borrar el perfil que esta en curso, con esto se carga otro perfil existente)
function damePerfilCualquiera(){
	opendb();

	$query1="SELECT * FROM perfil;"; 
	$resq1=mysql_query($query1);  
	$perfil=mysql_fetch_array($resq1);

	closedb();

	return $perfil;

}

// Devuelve una vista cualquiera (Utilizado a la hora de cerrar una Vista que esta en curso, con esto se carga otra Vista existente)
function dameVistaCualquiera($idPerfil){
	opendb();

	$query1="SELECT * FROM perfil_vista where idPerfil=".$idPerfil.";"; 
	$resq1=mysql_query($query1);  
	$vista=mysql_fetch_array($resq1);

	closedb();

	return $vista;

}


// // Existe un perfil especial (id = 2) que es el perfil activo. Así cuando se abre un perfil, se carga en el perfil activo y se representa. Esta funcion borra lo que haya en el perfil activo, y carga el nuevo perfil a representar.
// function cargarPerfil($idPerfil)
// {
// 
// 	opendb();
// 
// 	// Borramos todo lo que haya cargado en el perfil activo
// 	$deleteQuery="Delete from perfil_vista where idPerfil=2";
// 	$resqDelete=mysql_query($deleteQuery);
// 
// 	// Recogemos las vistas del nuevo Perfil
// 	$queryPerfil="SELECT * FROM perfil_vista where idPerfil=".$idPerfil.";"; 
// 	$resqPerfil=mysql_query($queryPerfil);  
// 
// 	// Cargamos en el perfil activo las vistas del nuevo Perfil
// 	while ($perfil_vista=mysql_fetch_array($resqPerfil)){
// 	
// 	$query1="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('2', '".$perfil_vista["idVista"]."');"; 
// 	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error()); 
// 	
// 	}
// 
// 
// 	closedb();
// 
// }

// // Recupera todas las vistas del perfil activo (id=2) y las añade al perfil que se le pasa como parametro
// function guardarPerfil($idPerfil)
// {
// 	opendb();
// 
// 	// Recogemos las vistas del Perfil Activo
// 	$queryPerfil="SELECT * FROM perfil_vista where idPerfil=2;"; 
// 	$resqPerfil=mysql_query($queryPerfil);  
// 
// 	// Borramos todo lo que habia en el antiguo perfil
// 	$deleteQuery="Delete from perfil_vista where idPerfil=".$idPerfil;
// 	$resqDelete=mysql_query($deleteQuery);
// 
// 	// Cargamos en el Perfil que queremos guardar, todas las vistas que existian en el activo
// 	while ($perfil_vista=mysql_fetch_array($resqPerfil)){
// 	
// 	$query1="INSERT INTO perfil_vista (`idPerfil`,`idVista`) VALUES ('".$idPerfil."', '".$perfil_vista["idVista"]."');"; 
// 	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error()); 
// 	
// 	}
// 
// 	closedb();
// 
// }

// Funcion que elimina una vista y todos sus objetos. Devuelve el codigo de error 1 si es la ultima vista, no borrandola.
function eliminarVista($idVista)
{
	opendb();

	// comprobamos que no es la ultima vista existente en el perfil
	$queryCheck="SELECT * FROM perfil_vista where idPerfil=(Select idPerfil from perfil_vista where idVista=".$idVista.") ;"; 
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
		
	
	}else return 1; // ERROR: solo queda una vista y no se puede borrar

	

	closedb();

}

// Retorna los valores de la consola para el usuario pasado como parametro
function dameEstadoConsola($usuario)
{
	opendb();

	$query1="SELECT * FROM estado_consola where id_usuario='".$usuario."';"; 
	$resq1=mysql_query($query1);  

	closedb();

	return mysql_fetch_array($resq1);

}

// Funcion que edita una Vista (id de la vista , nombre , descripcion)
function editarVista($idVista,$nombre,$descripcion)
{
	opendb();

	$query_vista="UPDATE vistas_consola set nombre='".$nombre."' , descripcion='".$descripcion."' where idVista='".$idVista."';"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;


	closedb();

}

// Funcion que edita un Perfil (id del Perfil , nombre , descripcion)
function editarPerfil($idPerfil,$nombre,$descripcion)
{
	opendb();

	$query_perfil="UPDATE perfil set Nombre='".$nombre."' , Descripcion='".$descripcion."' where idPerfil='".$idPerfil."';"; 
	mysql_query($query_perfil) or die("Failed Query of " . $query_perfil);;


	closedb();

}


// Elimina el perfil y las vistas asociadas al perfil referenciado por su id, y devuelve -1 como codigo de error si es el ultimo perfil, para indicar de que no se ha borrado por ser el ultimo.Si se ha borrado con exito, devuelve el id del perfil borrado
function eliminarPerfil($idPerfil)
{

	opendb();

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

	closedb();

}

// Funcion que pone a 1 el campo "activa" de la tabla perfil_vista (esto hace que la vista se visualice en la consola)

function activarVista($idVista)
{
	opendb();

	$query_vista="UPDATE perfil_vista set activa=1 where idVista=".$idVista.";"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;


	closedb();

}

// Funcion que pone a 0 el campo "activa" de la tabla perfil_vista (esto hace que la vista NO se visualice en la consola)

function desactivarVista($idVista)
{
	opendb();

	$query_vista="UPDATE perfil_vista set activa = 0 where idVista='".$idVista."';"; 
	mysql_query($query_vista) or die("Failed Query of " . $query_vista);;


	closedb();
}

// Funcion que devuelve 1 si es la ultima vista activa del perfil, o devuelve 0 si no lo es
function es_ultimaVistaActiva($idPerfil)
{

	opendb();

	$queryCheck="SELECT * FROM perfil_vista where idPerfil=".$idPerfil." and activa=1 ;"; 
	$numVistasexe=mysql_query($queryCheck); 
	$numVistas = mysql_num_rows($numVistasexe);
	if ($numVistas > 1)
		return 0;
	else return 1;

}

// Crea una relacion (linea) entre dos objetos
function crearRelacionObjetos($idObjeto1, $idObjeto2)
{

	opendb();

	$query1="INSERT INTO relacion_objetos (`idObjeto1`,`idObjeto2`) VALUES ('".$idObjeto1."', '".$idObjeto2."');"; 
	$resq1=mysql_query($query1) or die('Error, insert query failed'.$query1. mysql_error());  

	closedb();

}

//Devuelve todas las relaciones del objeto pasado como parametro y que sea el Objeto 1 de la relacion, si no tiene ninguna relación, devuelve -1
function dameRelacionesObjeto($idObjeto1)
{
	opendb();

	$query1="SELECT * FROM relacion_objetos where idObjeto1='".$idObjeto1."';"; 
	$resq1=mysql_query($query1);  

	closedb();

	$numRelaciones = mysql_num_rows($resq1);
	if ($numRelaciones > 0)
		return $resq1;
	else return -1;
	

}

//Funcion que devuelve 1 si el objeto pertence a la vista y 0 si no
function esObjetoDeVista($idObjeto,$idVista)
{
	opendb();
	$query1="SELECT * FROM objeto_consola where id_objeto='".$idObjeto."' and idVista='".$idVista."';"; 
	$resq1=mysql_query($query1);  

	closedb();

	$objVista = mysql_num_rows($resq1);
	if ($objVista > 0)
		return 1;
	else return 0;

}



?>