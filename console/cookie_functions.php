<?

// require("db_functions.php");

function obtenerVistaActiva()
{
	
	// Obtenemos la cookie estado y consultamos cual es la vista activa
	$cookieVista = $_COOKIE["estado"];

	if ($cookieVista != null)
	{ 


		$params = explode("&" ,$cookieVista);
		for ($i=0; $i<sizeof($params)-1;$i++)
		{	
			
			$name_data= explode("=",$params[$i]);
			
			if ($name_data[0] == "vista_activa") // Obtenemos la vista activa
			{
				$pestaVista = explode ("x",$name_data[1]);
				$tmp_vistaActiva=$pestaVista[1];
// 				mensaje($tmp_vistaActiva);
// 				mensaje($tmp_vistaActiva);
// 				if (isset($tmp_vistaActiva))
// 				{	
// 					$vista_activa=$tmp_vistaActiva;
//  					mensaje($tmp_vistaActiva);
// 				}
				$vista_activa=$tmp_vistaActiva;
// 				mensaje($vista_activa);
				if ($vista_activa=="undefined") // cuando cerramos una pestaña hay que elegir otra pestaña de las disponibles
				{
					
					$perfil = obtenerPerfilActivo();
					$vista_activa=dameVistaCualquiera($perfil);
// 					mensaje($vista_activa["idVista"]);
				}
			
			}
		}

	}else {

//////////////////////////////////////////////////////////////////////////////////////////
//  		$usuario = dameUsuarioActivo();  Es una fase que siemrpe retorna admin hay que implementarla bien cuando se haga la integración real con Pandora
//////////////////////////////////////////////////////////////////////////////////////////
		$usuario="admin";
		$estado_consola=dameEstadoConsola($usuario);
		$vista_activa=$estado_consola["idVistaActiva"];
		
	
	}
// 	mensaje("la vista es:".$vista_activa);
	return $vista_activa;

}

function obtenerPerfilActivo()
{


	// Obtenemos la cookie estado y consultamos cual es la vista activa
	$cookiePerfil = $_COOKIE["estado"];

	if ($cookiePerfil != null)
	{
		$params = explode("&" ,$cookiePerfil);
		for ($i=0; $i<sizeof($params);$i++)
		{	
			
			$name_data= explode("=",$params[$i]);
			
			if ($name_data[0] == "perfil_activo")
			{

			$perfil = $name_data[1];
			
			}
		}

	}else {

//////////////////////////////////////////////////////////////////////////////////////////
//  		$usuario = dameUsuarioActivo();  Es una fase que siemrpe retorna admin hay que implementarla bien cuando se haga la integración real con Pandora
//////////////////////////////////////////////////////////////////////////////////////////
		$usuario="admin";
		$estado_consola=dameEstadoConsola($usuario);
		$perfil=$estado_consola["idPerfilActivo"];
		
	
	}
	
	return $perfil;






/*


// Comprobamos si ya existe una sesion PHP con el perfil definido
if ($_SESSION['perfil']==null){
//////////////////////////////////////////////////////////////////////////////////////////
//  		$usuario = dameUsuarioActivo();  Es una fase que siemrpe retorna admin hay que implementarla bien cuando se haga la integración real con Pandora
//////////////////////////////////////////////////////////////////////////////////////////
		$usuario="admin";
		$estado_consola=dameEstadoConsola($usuario);
		$perfil=$estado_consola["idPerfilActivo"];
}else {
	$perfil=$_SESSION['perfil'];
}

echo "alert('hola".$_SESSION['perfil']."');";

return $perfil;*/
}


function dameCajaImagenes()
{
	$dir = "./imagenes/";
	
	$resultado="";

	// Open a known directory, and proceed to read its contents
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			$resultado = "+ \"<select name='nom_imagen'  >\"";
		while (($file = readdir($dh)) !== false) {
				$aFile = explode("." ,$file);
				if ($aFile[1] == "gif") 
				{
					$aValue= explode("_",$aFile[0]);
					$resultado .= "+ \"<option value='".$aValue[0]."'>".$aValue[0]."</option> \"";
				}
			
		}
		closedir($dh);
		$resultado .= "+ \"</select>\"
			+ \"<BR>\"";
		}
	}
	
	return $resultado;

}

// function representaEnCaja($datos)
// {
// 	$resultado="";
// 
// 		$resultado = "+ \"<select name='nom_imagen'  >\"";
// 		for ($i=0;$i < size($datos);$i++)
// 		{
// 			$resultado .= "+ \"<option value='".$datos[$i]."'>".$datos[$i]."</option> \"";
// 		}
// 
// 		$resultado .= "+ \"</select>\"
// 				+ \"<BR>\"";
// 		
// 	return $resultado;
// }



function mensaje($msj)
{
echo "alert('".$msj."');";
}

?>
