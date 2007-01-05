<!--// Pandora - the Free monitoring system
// ====================================
// Copyright (c) Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Copyright (c) INDISEG S.L, contacto[AT]indiseg[DOT]net www.indiseg.net

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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.-->

<?

// require("db_functions.php");

$graphURL="https://161.116.1.66/pandora_console_graph/pandora_console/reporting/fgraph.php";


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


function dameCajaImagenes($dir)
{
// 	$dir = "../imagenes/";
	
	$resultado="";

	// Open a known directory, and proceed to read its contents
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			$resultado = "+ \"<select name='nom_imagen' >\"";
		while (($file = readdir($dh)) !== false) {
				$aFile = explode("." ,$file);
				if ($aFile[1] == "png") 
				{
					$aValue= explode("_",$aFile[0]);
					$resultado .= "+ \"<option value='".$aValue[0]."' onmouseover=''>".$aValue[0]." </option>  \"";
				}
			
		}
		closedir($dh);
		$resultado .= "+ \"</select>\"
			+ \"<BR>\"



				";
		}
	}
	
	return $resultado;

}

// Devuelve codigo html que crea una caja de texto con un checkbox. Este checkbox será utilizado cuando se desean añadir un elemento y que automaticamente cree una vista con los subelementos de los que esta compuesto
function dameCheckboxAutoVistas($tipo)
{
$result="";

		switch ($tipo) {
			case "A": //agente
				$result=" + \"<BR><input type=\'checkbox\' id=vista_MG name=\'vista_MG\' value=\'vista_MG\'> <label for=vista_MG>Crear vista con grupos de modulos </label>  \"";
				$result.=" + \"<BR><input type=\'checkbox\' id=vista_M name=\'vista_M\' value=\'vista_M\'> <label for=vista_M>Crear vista con modulos </label><br>  \"";
				break;
			case "GA": //Grupo Agentes
				$result=" + \"<BR><input type=\'checkbox\' id=vista_A name=\'vista_A\' value=\'vista_A\'> <label for=vista_A>Crear vista con agentes </label><br>  \"";
				$result.=" + \"<BR><input type=\'checkbox\' id=vista_MG name=\'vista_MG\' value=\'vista_MG\'> <label for=vista_MG>Crear vista con grupos de modulos </label>  \"";
				$result.=" + \"<BR><input type=\'checkbox\' id=vista_M name=\'vista_M\' value=\'vista_M\'> <label for=vista_M>Crear vista con modulos </label><br>  \"";
				break;
			case "M": //Modulo
// 				
				break;
			case "GM": //Grupo Modulos
				$result=" + \"<BR><input type=\'checkbox\' id=vista_M name=\'vista_M\' value=\'vista_M\'> <label for=vista_M>Crear vista con modulos </label><br>  \"";
				break;
			case "V": //Vista
// 				
				break;
		}	


return $result;

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
// 

function mensaje($msj)
{
echo "alert('".$msj."');";
}


// Funcion que devuelve la imagen de la gráfica que lo representa
function dameGrafica($idModulo)
{
	global $graphURL;
	$origin = time() -3600;
	return "$graphURL?tipo=sparse&id=$idModulo&color=40d840&periodo=60&intervalo=12&label=Hourly%20graph&tipo=sparse&id=$idModulo&refresh=30&zoom=100&draw_events=1&origin=$origin";
}


?>
