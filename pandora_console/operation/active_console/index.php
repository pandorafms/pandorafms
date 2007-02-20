<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require("../../include/config.php");
global $dbname;
global $dbuser;
global $dbpassword;
global $dbtype;
global $dbhost;

require("lib/db_functions.php"); // Add external functions to database 
require("lib/extra_functions.php");

//Variables globales

$widthGraph=200;
$heigthGraph=200;

session_start(); 

	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head id="idHead">
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<?php	// Add this line to refresh each X seconds
	// *TODO* Adjust a variable interval
	if ($_GET["mode"] == "monitor"){
		echo ("<meta http-equiv=\"refresh\" content=\"10\">"); 
	}
?>

<title>Pandora FMS Active Console </title>

<meta name="author" content="Jonathan Barajas, Sancho Lerena">
<meta name="description" content="Pandora Web Console">
<link rel="stylesheet" href="styles/main.css" type="text/css">
<script type="text/javascript" src="scripts/x_core.js"></script>
<script type="text/javascript" src="scripts/x_event.js"></script>
<script type="text/javascript" src="scripts/x_slide.js"></script>
<script type='text/javascript' src='scripts/x_drag.js'></script>
<script type='text/javascript' src='scripts/objeto.php'></script>
<script type="text/javascript" src="scripts/xmenu2.js"></script>
<script type="text/javascript" src="scripts/xmenu2_html.js"></script>
<script type="text/javascript" src="scripts/xformulario.js"></script>
<script type="text/javascript" src="scripts/xformulario_html.php"></script>
<script type="text/javascript" src="scripts/onunload.php"></script>
<script type='text/javascript' src='scripts/x_dom.js'></script>
<script type='text/javascript' src='scripts/xtabpanelgroup.js'></script>
<script type="text/javascript" src="scripts/wz_jsgraphics.js"></script>


<script type="text/javascript">
// Get the cookie that stores about what topslide its selected

	vistaActiva=0;
	selectedIndex=0;
	modo="monitor";
	menuLeft=0;
	menuTop=0;
	perfil=0;
 	cookieEstado=GetCookie("estado");
	// Comprobamos que existe una cookie de estado
	if (cookieEstado != null){
    		if (cookieEstado.length > 0){
        		var params=cookieEstado.split("&");
        		for (var i=0 ; i < params.length ; i++){
            			var pos = params[i].indexOf("=");
            			var name = params[i].substring(0, pos);
            			var value = params[i].substring(pos + 1);
	    			if (name == "vista_activa" )
				{
					var vis = value.indexOf("x");
            				vistaActiva = value.substring(0, vis);

				}else if (name == "modo")
				{
					modo=value
					
				}else if (name == "menu")
				{
					var coor = value.indexOf("x");
            				menuLeft = value.substring(0, coor);
					menuTop = value.substring(coor + 1);
				}
        		} // for
    		} 
	} else { // Cargamos los valores de la base de datos
	<?php
// $usuario = dameUsuarioActivo();
// Es una fase que siemrpe retorna admin hay que implementarla bien cuando
// se haga la integracion real con Pandora
		$usuario="admin";
		$estado_consola=dameEstadoConsola($usuario);
		echo "vista_activa='".$estado_consola["idVistaActiva"]."';";
		echo "menuLeft='".$estado_consola["menuX"]."';";
		echo "menuTop='".$estado_consola["menuY"]."';";
	?>
	} //else

// Create an array in javascript that stores relation between number of tabs
// and ID of view that it's showed in that tab.
	var highZ = 3;
	var relacionPestaVista = new Array();
	aRelacionesObjetos = new Array(); // This array stores name of relationships (lines)
</script>

<!-- Absolutely Positioned Elements -->
<script type="text/javascript">
<?php

///////////////ON-LOAD////////////////////

//Necesitamos declarar globalmente las variables que cuentan el número de objetos que hay en el array

// $vistas = dameVistas();
// echo "var cuentaVis=".mysql_num_rows($vistas).";";// Variable que contiene el numero de vistas representadas
// while ($vista=mysql_fetch_array($vistas)){
// echo "cuentaObj".$vista["idVista"]."=0";
// }

echo "
window.onload = function() {


 // Definimos los menus

 
  // relative, under heading
  menu1 = new xMenu2(
    false, true, false,         // absolute, horizontal, floating,
    0, 0, 3,                    // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#cccc99', '#cccc99',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );
  // top-right
  menu2 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    xClientWidth() - 75, xClientHeight() - 80, 5, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#ffccff', '#000080',       // activeColor, inactiveColor,
    '#1570a4', '#d3e3ff',       // activeBkgnd, inactiveBkgnd
    '#d3e3ff'                   // boxBkgnd
  );
  // bottom-left
  menu3 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    0, xClientHeight() - 80, 2, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140], 
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#727272', '#adadad',       // activeBkgnd, inactiveBkgnd
    '#adadad'             // boxWidthsArray,
/*    '#000080', '#FF9999',       // activeColor, inactiveColor,
    '#00CCFF', '#0099FF',       // activeBkgnd, inactiveBkgnd
    '#00CCFF'    */               // boxBkgnd
  );
  // bottom-right
  menu4 = new xMenu2(
    true, true, true,           // absolute, horizontal, floating,
    xClientWidth()-205, xClientHeight()-20, 4, // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000066', '#ffff66',       // activeColor, inactiveColor,
    '#cccc99', '#9999cc',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );

  scrollListener(); // initial slide
  xAddEventListener(document, \"mousemove\", menuHideListener, false);
  xAddEventListener(window, \"scroll\", scrollListener, false);

";

$perfil = obtenerPerfilActivo();

// Obtenemos las vistas asignadas al perfil especial activo con id igual a 2 y creamos un array JavaScript que contendrá las Vistas
$vistas = dameVistasPerfilActivas($perfil);
echo "perfil='".$perfil."';"; // Tengo que crear una variable javascript para pasarsela en cookie (con $_SESSION no consigo que funcione)
echo "aVistas = new Array(), aVistas_count ='".mysql_num_rows($vistas)."';";
echo "cuentaVis=aVistas_count;";
$k=0;
while ($vista=mysql_fetch_array($vistas)){ //recorremos las vistas y creamos un array por cada vista con los objetos que se encuentran incluidos en la vista
	$objetos = dameObjetosVista($vista["idVista"]);
// 	while ($objeto=mysql_fetch_array($objetos)){
 		echo "aObjeto".$vista["idVista"]." = new Array(), aObjeto".$vista["idVista"]."_count =".mysql_num_rows($objetos).";";
////////
		echo "
     		var aPos".$vista["idVista"]." = new Array(); // Creamos array que guarde las posiciones de los objetos

    		var cookieData=GetCookie(\"objParams".$vista["idVista"]."\"); // Parseamos la cookie de la vista
		if (cookieData !=null){
    			if (cookieData.length > 0){
        			var parametros=cookieData.split(\"&\");
				for (var i=0 ; i < parametros.length ; i++){
            				var posicion = parametros[i].indexOf(\"=\");
            				var nombre = parametros[i].substring(0, posicion);
            				var valor = parametros[i].substring(posicion + 1);

				    if (nombre == i+1 )
					{
					metaposicion = valor;
		    			if (metaposicion.length > 0){
        					var metaparams=metaposicion.split(\"x\");
						posX=metaparams[0];
						posY=metaparams[1];
						indexX=nombre+\"X\";
						indexY=nombre+\"Y\";
						aPos".$vista["idVista"]."[indexX]=posX;
						aPos".$vista["idVista"]."[indexY]=posY;

					 }
					}
        			}
    			}
		} else { 
			";	// Cargamos de la base de datos
			$objetos = dameObjetosVista($vista["idVista"]);
			$n = 1; 
			while ($objeto =mysql_fetch_array($objetos)){
			echo "  aPos".$vista["idVista"]."['".$n."X']=".$objeto["left"].";
				aPos".$vista["idVista"]."['".$n."Y']=".$objeto["top"].";";
			$n++;
			}
		echo "
		}
		
		relacionPestaVista[".$k."]=".$vista["idVista"].";
		
		";
		$k++;
// 	}

// Creamos las instancias de los objetos (que se corresponderan con los ids de los divs html)
	$i=1;
//global $aObjetos;
//mysql_data_seek($objetos,0);

 	$objetos = dameObjetosVista($vista["idVista"]);
	while ($objeto=mysql_fetch_array($objetos)){
 		
		$aObjetos[$i-1]=$objeto["id_objeto"]; // Array php de objetos
		
		echo "aObjeto".$vista["idVista"]."[".$i."] = new xFenster('fen_".$vista["idVista"]."_".$objeto["id_objeto"]."', parseInt(aPos".$vista["idVista"]."['".$i."X']), parseInt(aPos".$vista["idVista"]."['".$i."Y']), 'fenBar".$vista["idVista"].$objeto["id_objeto"]."', 'fenResBtn".$vista["idVista"].$objeto["id_objeto"]."', 'fenMaxBtn".$vista["idVista"].$objeto["id_objeto"]."');";
 		
		$i=$i+1;
		
	}


	// Creamos los objetos, lineas que representan las relaciones entre objetos
	for ($j=0;$j<sizeof($aObjetos);$j++){

		if (esObjetoDeVista($aObjetos[$j],$vista["idVista"]))
		{
			
			$aRelaciones=dameRelacionesObjeto($aObjetos[$j]);

			if ($aRelaciones !=-1)
			{
				
				while ($relacion=mysql_fetch_array($aRelaciones))
				{
				$irelacionObj1=$j+1; // parece que no me deja sumarlo despues
				$idObjeto2=$relacion["idObjeto2"];
				$irelacionObj2=array_search($idObjeto2,$aObjetos) + 1; //Buscamos la posicion del array php en el que se encuentra el id del segundo objeto. Esto nos sirve para saber cual sera el nombre del objeto javascript, ya que exite una relacion entre los indices de los diferentes arrays. El indexPHP=indexJavascrip - 1;
				echo " jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2." = new jsGraphics('Canvas_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2."');
					jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2.".setColor('#000000'); 
					jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2.".drawLine(aObjeto".$vista["idVista"]."[".$irelacionObj1."].dameCentroX(), aObjeto".$vista["idVista"]."[".$irelacionObj1."].dameCentroY(), aObjeto".$vista["idVista"]."[".$irelacionObj2."].dameCentroX(), aObjeto".$vista["idVista"]."[".$irelacionObj2."].dameCentroY()); 
					jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2.".paint();
					";
				//Añado la nueva relacion al array que guarda todos las creadas
				echo "aRelacionesObjetos['jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2."']=jg_".$vista["idVista"]."_".$aObjetos[$j]."_".$idObjeto2.";";
				 
				}
			}
		}
	
	$i=$i+1;
	}

// echo "var jg_doc = new jsGraphics();
// 
//   jg_doc.setColor('#ff0000'); // red
//   jg_doc.drawLine(10, 113, 220, 55); // co-ordinates related to 'myCanvas'
//   jg_doc.setColor('#0000ff'); // blue
//   jg_doc.fillRect(110, 120, 30, 60);
//   jg_doc.paint();
// ";


}
//$objetos = dameObjetos();
// echo "var aObjeto_count = 0;";
// echo "aObjeto_count=1;";
//echo "aObjeto = new Array(), aObjeto_count =".mysql_num_rows($objetos).";";







echo "


var tpg2;

  tpg2 = new xTabPanelGroup('tpg2', 1000, 1000, 25, 'tabPanel', 'tabGroup', 'tabDefault', 'tabSelected');
 

tpg2.select(parseInt(vistaActiva)+1); 

  FormSetup();

}
";



$vista_activa=obtenerVistaActiva();
// Obtenemos la acción a realizar mediante los parametros GET y el nombre "action"


// Una vez aparecido el formulario añadimos a la base de datos, los valores recogidos
if ($_GET["action"]=="addagent")
{
	$aSub[0]=$_POST["vista_MG"]; // Crear el objeto en forma de vista y que se cree una vista con los grupos de modulos que lo forman
	$aSub[1]=$_POST["vista_M"]; // Crear el objeto en forma de vista y que se cree una vista con los modulos que lo forman
	
	if (($aSub[0]=="") and ($aSub[1]=="")) // Solo se crea el objeto
	{
		// El tipo A es Agente
		nuevoObjEnVista($_POST["nom_imagen"],'A',0,0,$_POST["group1"],$vista_activa);

	}elseif (($aSub[0]=="vista_MG") and ($aSub[1]=="")) // Se crea el objeto en forma de vista y se añaden los grupos de modulos
	{
		$idAgente=$_POST["group1"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idAgente,"A");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (grupos de modulos) de este objeto
		$gruposM=dameGruposModuloDelAgente($idAgente);
		
		//Recorremos el array de grupos de modulos, creando los objetos y añadiendolos a la vista
		while ($grupoM=mysql_fetch_array($gruposM))
		{
			nuevoObjEnVista("undefined",'GM',0,0,$grupoM["id_mg"]."#".$nombre_vista,$idVista);	
		}


	
	}elseif (($aSub[0]=="") and ($aSub[1]=="vista_M")) // Se crea el objeto en forma de vista y se añaden los modulos que lo forman
	{
		$idAgente=$_POST["group1"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idAgente,"A");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (modulos) de este objeto
		$modulos=dameModulos($idAgente);
		
		//Recorremos el array  de modulos, creando los objetos y añadiendolos a la vista
		while ($modulo=mysql_fetch_array($modulos))
		{
			nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"],$idVista);	
		}


	
	}else if (($aSub[0]=="vista_MG") and ($aSub[1]=="vista_M")) // Se crea el objeto en forma de vista y se añaden los grupos de modulos en forma de vista que a su vez contienen los modulos
	{
		$idAgente=$_POST["group1"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idAgente,"A");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (grupos de modulos) de este objeto
		$gruposM=dameGruposModuloDelAgente($idAgente);
		
		//Recorremos el array de grupos de modulos, creando los objetos y añadiendolos a la vista
		while ($grupoM=mysql_fetch_array($gruposM))
		{
			$id_mg=$grupoM["id_mg"];
			
			// Recogemos el nombre del Objeto para añadirselo a la vista
			$nombre_vista=dameNombreObjeto($id_mg,"GM");
	
			// Guardamos la vista
			$idVista_GM=guardarNuevaVista($nombre_vista,"",$perfil_activo);
	
			// Desactivamos la vista
			desactivarVista($idVista_GM);
	
			// Creamos el objeto vista en la vista actual
			nuevoObjEnVista("undefined",'V',0,0,$idVista_GM,$idVista);
	
			// Obtenemos los sub_objetos (modulos) de este objeto
			$modulos=dameModulosDelGrupoModulosAgente($idAgente,$id_mg);
			
			//Recorremos el array de modulos, creando los objetos y añadiendolos a la vista
			while ($modulo=mysql_fetch_array($modulos))
			{
				nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"],$idVista_GM);	
			}	
		}

		// Añadimos los modulos que no pertenecen a un grupo
		$modulos=dameModulosSinGrupo($idAgente);
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_agente=dameNombreObjeto($idAgente,"A");

		while ($modulo=mysql_fetch_array($modulos))
		{
			nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"],$idVista);	
		}

	}
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";


}elseif ($_GET["action"]=="addmodulo")
{

	$idAgente = $_POST["group"];
	// El tipo M es un Modulo
	nuevoObjEnVista($_POST["nom_imagen"],'M',0,0,$_POST["group".$idAgente],$vista_activa);
	
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="addgrupoAgente")
{

	$aSub[0]=$_POST["vista_A"]; // Crear el objeto en forma de vista y que se cree una vista con los agentes que lo forman
	$aSub[1]=$_POST["vista_MG"]; // Crear el objeto en forma de vista y que se cree una vista con los grupos de modulos que lo forman
	$aSub[2]=$_POST["vista_M"]; // Crear el objeto en forma de vista y que se cree una vista con los modulos que lo forman
	
	if (($aSub[0]=="") and ($aSub[1]=="") and ($aSub[2]=="")) // Solo se crea el objeto
	{
		// El tipo GA es un Grupo de Agentes
		nuevoObjEnVista($_POST["nom_imagen"],'GA',0,0,$_POST["group"],$vista_activa);

	}elseif (($aSub[0]=="vista_A") and ($aSub[1]=="") and ($aSub[2]=="")) // Se crea el objeto en forma de vista y se añaden los agentes
	{
		$idGrupoAgente=$_POST["group"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idGrupoAgente,"GA");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (grupos de modulos) de este objeto
		$agentes=dameAgentesDelGrupoAgentes($idGrupoAgente);
		
		//Recorremos el array de agentes, creando los objetos y añadiendolos a la vista
		while ($agente=mysql_fetch_array($agentes))
		{
			nuevoObjEnVista("undefined",'A',0,0,$agente["id_agente"],$idVista);	
		}


	}elseif (($aSub[0]=="vista_A") and ($aSub[1]=="vista_MG") and ($aSub[2]=="")) // Se crea el objeto en forma de vista y se añaden los agentes en forma de vista y los grupos de modulos
	{
		$idGrupoAgente=$_POST["group"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idGrupoAgente,"GA");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (agentes) de este objeto
		$agentes=dameAgentesDelGrupoAgentes($idGrupoAgente);
		
		//Recorremos el array de agentes, creando los objetos y añadiendolos a la vista
		while ($agente=mysql_fetch_array($agentes))
		{
			// Guardamos la vista
  			$idVista_a=guardarNuevaVista($agente["nombre"],"",$perfil_activo);
			// Desactivamos la vista
			desactivarVista($idVista_a);

			// Creamos el objeto vista en la vista actual
			nuevoObjEnVista("undefined",'V',0,0,$idVista_a,$idVista);

			// Obtenemos los sub_objetos (grupos de modulos) de este objeto
			$gruposM=dameGruposModuloDelAgente($agente["id_agente"]);
			
			while ($grupoM=mysql_fetch_array($gruposM))
			{
			nuevoObjEnVista("undefined",'GM',0,0,$grupoM["id_mg"]."#".$agente["nombre"],$idVista_a);	
			}
		}


	}elseif (($aSub[0]=="vista_A") and ($aSub[1]=="vista_MG") and ($aSub[2]=="vista_M")) // Se crea el objeto en forma de vista y se añaden los agentes en forma de vista y los grupos de modulos en forma de vista y sus modulos
	{
		$idGrupoAgente=$_POST["group"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($idGrupoAgente,"GA");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		// Obtenemos los sub_objetos (agentes) de este objeto
		$agentes=dameAgentesDelGrupoAgentes($idGrupoAgente);
		
		//Recorremos el array de agentes, creando los objetos y añadiendolos a la vista
		while ($agente=mysql_fetch_array($agentes))
		{
			// Guardamos la vista
  			$idVista_a=guardarNuevaVista($agente["nombre"],"",$perfil_activo);
			// Desactivamos la vista
			desactivarVista($idVista_a);

			// Creamos el objeto vista en la vista actual
			nuevoObjEnVista("undefined",'V',0,0,$idVista_a,$idVista);

			// Obtenemos los sub_objetos (grupos de modulos) de este objeto
			$gruposM=dameGruposModuloDelAgente($agente["id_agente"]);
			
			while ($grupoM=mysql_fetch_array($gruposM))
			{

				// Guardamos la vista
				$idVista_gm=guardarNuevaVista($grupoM["name"],"",$perfil_activo);
				// Desactivamos la vista
				desactivarVista($idVista_gm);
	
				// Creamos el objeto vista en la anterior
				nuevoObjEnVista("undefined",'V',0,0,$idVista_gm,$idVista_a);
	
				// Obtenemos los sub_objetos (modulos) de este objeto
				$modulos=dameModulosDelGrupoModulosAgente($agente["id_agente"],$grupoM["id_mg"]);
			
				//Recorremos el array de modulos, creando los objetos y añadiendolos a la vista
				while ($modulo=mysql_fetch_array($modulos))
				{
					nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"],$idVista_gm);	
				}	

			}
			// Añadimos los modulos que no pertenecen a un grupo
			$modulos=dameModulosSinGrupo($agente["id_agente"]);
	
			while ($modulo=mysql_fetch_array($modulos))
			{
				nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"]."#".$agente["nombre"],$idVista_a);	
			}
		}


	}

// elseif (($aSub[0]=="vista_MG") and ($aSub[1]=="")) // Se crea el objeto en forma de vista y se añaden los grupos de modulos
// 	{
// 		$idAgente=$_POST["group1"];
// 
// 		$perfil_activo=obtenerPerfilActivo();
// 		
// 		// Recogemos el nombre del Objeto para añadirselo a la vista
// 		$nombre_vista=dameNombreObjeto($idAgente,"A");
// 
// 		// Guardamos la vista
//   		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);
// 
// 		// Desactivamos la vista
// 		desactivarVista($idVista);
// 
// 		// Creamos el objeto vista en la vista actual
// 		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);
// 
// 		// Obtenemos los sub_objetos (grupos de modulos) de este objeto
// 		$gruposM=dameGruposModuloDelAgente($idAgente);
// 		
// 		//Recorremos el array de grupos de modulos, creando los objetos y añadiendolos a la vista
// 		while ($grupoM=mysql_fetch_array($gruposM))
// 		{
// 			nuevoObjEnVista("undefined",'GM',0,0,$grupoM["id_mg"]."#".$nombre_vista,$idVista);	
// 		}
// 
// 
// 	
// 	}elseif (($aSub[0]=="") and ($aSub[1]=="vista_M")) // Se crea el objeto en forma de vista y se añaden los modulos que lo forman
// 	{
// 		$idAgente=$_POST["group1"];
// 
// 		$perfil_activo=obtenerPerfilActivo();
// 		
// 		// Recogemos el nombre del Objeto para añadirselo a la vista
// 		$nombre_vista=dameNombreObjeto($idAgente,"A");
// 
// 		// Guardamos la vista
//   		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);
// 
// 		// Desactivamos la vista
// 		desactivarVista($idVista);
// 
// 		// Creamos el objeto vista en la vista actual
// 		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);
// 
// 		// Obtenemos los sub_objetos (modulos) de este objeto
// 		$modulos=dameModulos($idAgente);
// 		
// 		//Recorremos el array  de modulos, creando los objetos y añadiendolos a la vista
// 		while ($modulo=mysql_fetch_array($modulos))
// 		{
// 			nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"]."#".$nombre_vista,$idVista);	
// 		}





	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="addGrupoModulo")
{ 

	$aSub[0]=$_POST["vista_M"]; // Este array indica si se debe crear este objeto en forma de vista e insertar dentro el conjunto de objetos que lo forman
	// El tipo GM es un grupo de Modulos
	if ($aSub[0]=="") // Objeto basico
	{
		nuevoObjEnVista($_POST["nom_imagen"],'GM',0,0,$_POST["group"]."#".$_POST["agente"],$vista_activa);

	}else {// Objeto-vista con sus subelementos

		$id_mg=$_POST["group"];

		$perfil_activo=obtenerPerfilActivo();
		
		// Recogemos el nombre del Objeto para añadirselo a la vista
		$nombre_vista=dameNombreObjeto($id_mg,"GM");

		// Guardamos la vista
  		$idVista=guardarNuevaVista($nombre_vista,"",$perfil_activo);

		// Desactivamos la vista
		desactivarVista($idVista);

		// Creamos el objeto vista en la vista actual
		nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVista,$vista_activa);

		//Obtenemos el id del Agente que contiene el grupo de modulos
		$id_agente=dameIdAgente($_POST["agente"]);

		// Obtenemos los sub_objetos (modulos) de este objeto
		$modulos=dameModulosDelGrupoModulosAgente($id_agente,$id_mg);
		
		//Recorremos el array de modulos, creando los objetos y añadiendolos a la vista
		while ($modulo=mysql_fetch_array($modulos))
		{
			nuevoObjEnVista("undefined",'M',0,0,$modulo["id_agente_modulo"],$idVista);	
		}
		
		
	}

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="guardarVista")
{
	$perfil_activo=obtenerPerfilActivo();
	// Guardamos la vista
  	$idVista=guardarNuevaVista($_POST["nombre"],$_POST["descripcion"],$perfil_activo);

	// Ahora que hemos obtenido la vista activa, accedemos a su cookie y obtenemos sus objetos
	$cookie = $_COOKIE["objParams".$vista_activa];
	
	if ($cookie != null)
	{
		$objs = explode("&" ,$cookie);
		for ($i=0; $i<sizeof($objs)-1;$i++)
		{	
			
			$obj= explode("=",$objs[$i]);
			$coordenadas = explode ("x",$obj[1]);
			copiaObjEnNuevaVista($idVista,$aObjetos[$i]);
#nuevoObjEnVista($_POST["nom_imagen"],'A',0,0,$_POST["group1"],19);
		}

	}
	
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="nuevaVista")
{
	$perfil_activo=obtenerPerfilActivo();
	// Guardamos la vista
  	guardarNuevaVista($_POST["nombre"],$_POST["descripcion"],$perfil_activo);
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?action=guardarPerfil&mode=edition';";

}elseif ($_GET["action"]=="nuevoPerfil")
{
	// Guardamos la vista
  	$idPerfil=guardarNuevoPefil($_POST["nombre"],$_POST["descripcion"]);

	// Abrimos el nuevo Perfil
// 	cargarPerfil($idPerfil);
	echo "perfil=".$idPerfil.";";
;
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="abrirPerfil")
{
	
// 	cargarPerfil($_POST["group1"]);
// 	echo "alert('33');";
	$idperfil=$_POST["group1"];
	echo "perfil='".$idperfil."'; ";
	echo "setCookieEstado();";
   	echo "window.location.href=location.pathname+'?mode=monitor';";

}/*elseif ($_GET["action"]=="guardarPerfil")
{
	guardarPerfil($_SESSION['perfil']);
	echo "window.location.href=location.pathname+'?mode=edition';";

}*/elseif ($_GET["action"]=="editarObjeto")
{

	$objeto = dameObjeto($_POST["group1"]);
	$nombre_objeto=dameNombreObjeto($objeto["id_tipo"],$objeto["tipo"]);

//Ya se que lo siguiente no es muy elegante, pero javascript y php no es que sean muy amigos. Ya lo cambiare por algo mejor

	echo "
	
	document.write(
	\"<div id='xForm' class='demoBox'>\"
	+ \"<div id='formCerrBtn' class='demoBtn'>X</div>\"
	+  \"<div id='xFormBar' class='demoBar'>FORMULARIO</div>\"
	+  \"<div class='demoContent'>\"
	+\"<FORM action=index.php?action=guardarEdicionObjeto&mode=edition method='post'>\"
	+  \"  <P>\"
	+ \"<BR> <INPUT type='hidden' id='idObjeto' name='idObjeto' value=".$objeto["id_objeto"]." >\"
	+   \" <LABEL for='nombre'>Objeto ".$nombre_objeto.": </LABEL>\"
	+ \"<BR>\"
	";

	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>La imagen utilizada actualmente es: ".$objeto["nom_img"]." </LABEL>\"
		+ \"<BR><BR>\"


		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";


	echo dameCajaImagenes("./imagenes");



	echo "
	+ \"<BR>\"
	
	+   \" <INPUT type='submit' value='Send'> <INPUT type='reset'>\"
	+   \" </P>\"
	+\" </FORM>\"
	+  \"</div>\"
	+ \"</div>\");

";
// 	formEditarObjeto(1);
// 	echo "alert ('editando');";

}elseif ($_GET["action"]=="guardarEdicionObjeto")
{
	editarObjeto($_POST["idObjeto"],$_POST["nom_imagen"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="eliminarObjeto")
{
	eliminarObjeto($_POST["group1"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="eliminarVista")
{
	$err=eliminarVista($_POST["group1"]);
	
	if ($err == 1)
	{
		mensaje("No se ha podido borrar la vista, ya que es la unica vista de la que dispone en este perfil"); 
	}
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="editarVista")
{

	$vista = dameVista($_POST["group1"]);

//Ya se que lo siguiente no es muy elegante, pero javascript y php no es que sean muy amigos. Ya lo cambiare por algo mejor

	echo "
	
	document.write(
	\"<div id='xForm' class='demoBox'>\"
	+ \"<div id='formCerrBtn' class='demoBtn'>X</div>\"
	+  \"<div id='xFormBar' class='demoBar'>FORMULARIO</div>\"
	+  \"<div class='demoContent'>\"
	+\"<FORM action=index.php?action=guardarEdicionVista&mode=edition method='post'>\"
	+  \"  <P>\"
	+ \"<BR> <INPUT type='hidden' id='idVista' name='idVista' value='".$vista["idVista"]."' >\"
	+   \" <LABEL for='nombre'>Vista ".$vista["nombre"].": </LABEL>\"
	+ \"<BR>\"
	
	+ \"<BR>\"
	+   \" <LABEL for='nombre'>Nombre: </LABEL>\"
	+   \"           <INPUT type='text' id='nombre' name='nombre' value='".$vista["nombre"]."'><BR>\"
	+ \"<BR>\"
	+   \" <LABEL for='descripcion'>Descripcion: </LABEL>\"
	+ \"<BR><TEXTAREA NAME='descripcion' ROWS='10' COLS='20' >".$vista["descripcion"]."\"

	+ \"</TEXTAREA>\"

	+ \"<BR>\"
	
	+   \" <INPUT type='submit' value='Send'> <INPUT type='reset'>\"
	+   \" </P>\"
	+\" </FORM>\"
	+  \"</div>\"
	+ \"</div>\");

";


}elseif ($_GET["action"]=="guardarEdicionVista")
{

	editarVista($_POST["idVista"],$_POST["nombre"],$_POST["descripcion"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="editarPerfil")
{
	
 	editarPerfil($_POST["idPerfil"],$_POST["nombre"],$_POST["descripcion"]);
 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="eliminarPerfil")
{
	
 	$err=eliminarPerfil($_POST["group1"]);
	if ($err == 1)
	{
		mensaje("No se ha podido borrar este perfil, ya que es el unico perfil disponible"); 
	} else{
		// Se comprueba si el perfil cargado es el que se ha borrado, si es así se carga uno nuevo
		$perfilActivo = obtenerPerfilActivo();
		if ($perfilActivo == $_POST["group1"] )
		{
			$perfil=damePerfilCualquiera();
			echo "  perfil = ".$perfil["idPerfil"].";
				setCookieEstado();";
		}	
		
	}

 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="convertirVista")
{
	$idVistaActiva=$_POST["idVistaActiva"];
	$idVistaGuardaObj=$_POST["group1"];
	$perfilActivo = obtenerPerfilActivo();
	// El tipo V es Vista
	nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVistaActiva,$idVistaGuardaObj );
	// Desactivamos la vista para que no aparezca en la consola
	desactivarVista($idVistaActiva);
	// Actualizamos la cookie para que no intente cargar esta vista
// 	echo "----------------------------------".$vista["idVista"];
	echo " perfil = ".$perfilActivo.";
		vistaActiva = '".$idVistaGuardaObj."';
		setCookieEstado();";

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="cerrarVista")
{
	$idVistaActiva=obtenerVistaActiva();
	$perfilActivo = obtenerPerfilActivo();
	if (! es_ultimaVistaActiva($perfilActivo))
	{
		// Desactivamos la vista para que no aparezca en la consola
		desactivarVista($idVistaActiva);
		$vista=dameVistaCualquiera($perfilActivo);
		// Actualizamos la cookie para que no intente cargar esta vista
	// 	echo "----------------------------------".$vista["idVista"];
		echo " perfil = ".$perfilActivo.";
			vistaActiva = '".$vista['idVista']."';
			setCookieEstado();";
	
		// Actualizamos la pagina para que aparezca el nuevo objeto
		echo "window.location.href=location.pathname+'?mode='+modo;";
	} else 	{
		mensaje("No se puede cerrar esta vista: Es la ultima");
		echo "window.location.href=location.pathname+'?mode='+modo;";
	}

}elseif ($_GET["action"]=="abrirVista")
{

	$perfilActivo = obtenerPerfilActivo();

	// Desactivamos la vista para que no aparezca en la consola
	activarVista($_POST["group1"]);

	// Actualizamos la cookie para que abra la nueva vista

	echo " perfil = ".$perfilActivo.";
		vistaActiva = '".$_POST["group1"]."';
		setCookieEstado();";

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="nuevoObjetoVista")
{
	$idVistaActiva=$_POST["idVistaActiva"];
	$idVistaObj=$_POST["group1"];
	$perfilActivo = obtenerPerfilActivo();
	// El tipo V es Vista
	$result=nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVistaObj,$idVistaActiva );
	if ($result==-1){mensaje("La vista que quiere representa como objeto, tiene un objeto vista que coincide con la vista donde quiere insertar el nuevo objeto. El objeto NO se creara");}
 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($_GET["action"]=="clickAbrirVista")
{
	$perfilActivo = obtenerPerfilActivo();
	$vistaActiva = $_GET["vista"];
	// Desactivamos la vista para que no aparezca en la consola
	activarVista($vistaActiva);

	// Actualizamos la cookie para que abra la nueva vista

	echo " perfil = ".$perfilActivo.";
		vistaActiva = '".$vistaActiva."';
		setCookieEstado();";

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="relacionarObjetos")
{
	crearRelacionObjetos($_POST["group1"],$_POST["group2"]);

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="eliminarRelacion")
{/*
	crearRelacionObjetos($_POST["group1"],$_POST["group2"]);

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";*/
	
	$aObj= explode("_",$_POST["group1"]);
	$obj1 = $aObj[0];
	$obj2 = $aObj[1];
	eliminarRelacion($obj1,$obj2);
	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="guardar_posicion")
{
// 	
// 	aObjetoPos= new Array();
	if ($_GET["estado"] <> 1)
	{
		echo "window.location.href=location.pathname+'?mode='+modo+'&action=guardar_posicion&estado=1';";
	} else{

		$cookie = $_COOKIE["objParams".$vista_activa];
		
		if ($cookie != null)
		{
			$objs = explode("&" ,$cookie);
			for ($i=0; $i<sizeof($objs)-1;$i++)
			{	
				
				$obj= explode("=",$objs[$i]);
				$aObjetoPos[$obj[0]]=$obj[1];
				$coordenadas = explode ("x",$obj[1]);
			}
	
		}
		
		$cont=1;
		$objetos=dameObjetosVista($vista_activa);
		while ($objeto=mysql_fetch_array($objetos))
		{
			$coordenadas = explode ("x",$aObjetoPos[$cont]);
			$cont++;
			guardarPosicion($objeto["id_objeto"],$coordenadas[0],$coordenadas[1]);
		}
	
		echo "window.location.href=location.pathname+'?mode='+modo;";
	}

}elseif ($_GET["action"]=="relacionarEstado")
{
 	$result=crearRelacionEstado($_POST["group1"],$_POST["expresion"]);
	if ($result==-1)
	{
		mensaje("ERROR: Ya existe un relacion para este objeto");
	}
	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="eliminarRelacionEstado")
{
 	eliminarRelacionEstado($_POST["group1"]);
	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";

}elseif ($_GET["action"]=="clickSetImagenGrafica")
{
 	setImagenGrafica($_GET["idModulo"]);
	// Actualizamos la pagina para que aparezca el nuevo objeto
  	echo "window.location.href=location.pathname+'?mode='+modo;";

}





// El siguiente if controla el estado del menu

if (($_GET["mode"]=="monitor" ) or ($_GET["mode"]==""))
{
	echo "\ninsertMenu(2,'a');";

	if ($_GET["formulario"]=="actualizar")
	{
		echo "insertFormulario('actualizar');";

	}elseif ($_GET["formulario"]=="abrir_perfil")
	{
		echo "insertFormulario('abrir_perfil');";

	}elseif ($_GET["formulario"]=="abrir_vista")
	{
		echo "insertFormulario('abrir_vista');";
	}

}elseif ($_GET["mode"]=="edition"){ 
	echo "\ninsertMenu(3,'a');";

	if ($_GET["formulario"]=="nuevo_agente")
	{
		echo "insertFormulario('nuevo_agente');";

	}elseif	 ($_GET["formulario"]=="nuevo_modulo")
	{
		echo "insertFormulario('nuevo_modulo');";

	}elseif	 ($_GET["formulario"]=="nuevo_grupoAgente")
	{
		echo "insertFormulario('nuevo_grupoAgente');";

	}elseif	 ($_GET["formulario"]=="nuevo_grupoModulo")
	{
		echo "insertFormulario('nuevo_grupoModulo');";

	}elseif	 ($_GET["formulario"]=="guardar_vista")
	{
		echo "insertFormulario('guardar_vista');";

	}elseif	 ($_GET["formulario"]=="nueva_vista")
	{
		echo "insertFormulario('nueva_vista');";

	}elseif	 ($_GET["formulario"]=="nuevo_perfil")
	{
		echo "insertFormulario('nuevo_perfil');";

	}elseif	 ($_GET["formulario"]=="guardar_perfil")
	{
		echo "insertFormulario('guardar_perfil');";

	}elseif	 ($_GET["formulario"]=="editar_objetos")
	{
		echo "insertFormulario('editar_objetos');";

	}elseif	 ($_GET["formulario"]=="eliminar_objeto")
	{
		echo "insertFormulario('eliminar_objeto');";

	}elseif	 ($_GET["formulario"]=="eliminar_vista")
	{
		echo "insertFormulario('eliminar_vista');";

	}elseif	 ($_GET["formulario"]=="editar_vista")
	{
		echo "insertFormulario('editar_vista');";

	}elseif	 ($_GET["formulario"]=="editar_perfil")
	{
		echo "insertFormulario('editar_perfil');";

	}elseif	 ($_GET["formulario"]=="eliminar_perfil")
	{
		echo "insertFormulario('eliminar_perfil');";

	}elseif	 ($_GET["formulario"]=="convertir_vista")
	{
		echo "insertFormulario('convertir_vista');";

	}elseif	 ($_GET["formulario"]=="abrir_vista")
	{
		echo "insertFormulario('abrir_vista');";

	}elseif	 ($_GET["formulario"]=="nuevo_objetoVista")
	{
		echo "insertFormulario('nuevo_objetoVista');";

	}elseif	 ($_GET["formulario"]=="relacionar_objetos")
	{
		echo "insertFormulario('relacionar_objetos');";

	}elseif	 ($_GET["formulario"]=="eliminar_relacion")
	{
		echo "insertFormulario('eliminar_relacion');";

	}elseif	 ($_GET["formulario"]=="relacionar_estado")
	{
		echo "insertFormulario('relacionar_estado');";

	}elseif	 ($_GET["formulario"]=="eliminar_relacion_estado")
	{
		echo "insertFormulario('eliminar_relacion_estado');";

	}elseif ($_GET["formulario"]=="abrir_perfil")
	{
		echo "insertFormulario('abrir_perfil');";

	}
}


	
echo "</script>\n";
echo "</head>\n";
//background='/images/console/background/europa.jpg'
echo "<body onUnload=\"javascript:guardarEstado()\" >
<div>
	<div id='tpg2' class='tabPanelGroup'>

  		<div class='tabGroup'>
";



$perfil = obtenerPerfilActivo();

// Obtenemos las vistas asignadas al perfil especial activo con id igual a 2 y creamos un array JavaScript que contendrá las Vistas
$vistas = dameVistasPerfilActivas($perfil);
// mysql_data_seek($vistas,0);
while ($vista=mysql_fetch_array($vistas)){
	$datos_vista=dameVista($vista["idVista"]);
	$estado_vista=dameEstadoVista($vista["idVista"]);
	$css_estado_vista="";
	switch ($estado_vista) {
		case -1: $css_estado_vista.="\"\"";
			break;
		case  1: $css_estado_vista.="\"\"";
			break;
		case  0: $css_estado_vista.="\"style=background-color:red\"";
			break;
		case  2: $css_estado_vista.="\"style=background-color:yellow\"";
			break;
	}

	$alerta_vista=comprobarAlertaVista($vista["idVista"]);
	$etiqueta=$datos_vista["nombre"];
	if ($alerta_vista==1) $etiqueta.=" (ALERTA)";
	$etiqueta_nombre=" <TABLE ALIGN=\"center\">
	  <TR>
    	  	<TD>
	  		".$etiqueta."
			
	  	</TD>

		<TD></TD><TD></TD><TD></TD><TD></TD><TD></TD><TD></TD>
		<TD>
	  		<IMG  SRC='imagenes/utiles/cancel.gif' border=0 onclick=\"setCookieEstado();location.href='index.php?action=cerrarVista&mode='+modo\">
	  	</TD>

	  </TR>
	  		</TABLE>";

    	echo "<a href='#tpg2".$vista["idVista"]."' class='tabDefault'  title='".$datos_vista["descripcion"]."' ".$css_estado_vista.">".$etiqueta_nombre."
	</a><span class='linkDelim'>&nbsp;|&nbsp;</span>";

}

echo "</div>";


$perfil = obtenerPerfilActivo();

// Obtenemos las vistas asignadas al perfil especial activo con id igual a 2 y creamos un array JavaScript que contendrá las Vistas
$vistas = dameVistasPerfilActivas($perfil);
// mysql_data_seek($vistas,0);
while ($vista=mysql_fetch_array($vistas)){


	echo "
		<div id='tpg2".$vista["idVista"]."' class='tabPanel'>
		 ";	
	
	$objetos = dameObjetosVista($vista["idVista"]);

	while ($objeto=mysql_fetch_array($objetos))
	{
		$estado_objeto=dameEstadoObjeto($objeto["id_objeto"]);//0=MAL, 1=BIEN, 2=CAMBIANDO, -1=DESCONECTADO

		$css_estado="style=";
		switch ($estado_objeto) {
			case -1: $css_estado.="\"border-color:red\"";
				break;
			case  1: $css_estado.="\"border-color:green\"";
				break;
			case  0: $css_estado.="\"background-color:red\"";
				break;
			case  2: $css_estado.="\"background-color:yellow\"";
				break;
		}
		
		$alerta_objeto=comprobarAlertaObjeto($objeto["id_objeto"]); // 0=NO ALERTA 1=SI ALERTA
		$css_alerta="style=\"background-color:white\"";
		
		$datos_objeto=dameObjeto($objeto["id_objeto"]);
		$nombre_objeto=dameNombreObjeto($datos_objeto["id_tipo"], $datos_objeto["tipo"]);
		$tipo_objeto=$datos_objeto["tipo"];
		if ($tipo_objeto == "V")
		{
			$tipo_objeto ="  <IMG  SRC='imagenes/utiles/play.gif' border=0 onclick=\"setCookieEstado();location.href='index.php?action=clickAbrirVista&vista=".$datos_objeto["id_tipo"]."&mode='+modo\">"; 
		}
	
		echo "
			<div id='fen_".$vista["idVista"]."_".$objeto["id_objeto"]."' class='fenster' ".$css_estado.">
				<div id='fenBar".$vista["idVista"].$objeto["id_objeto"]."' class='fenBar'  align=center title='".$nombre_objeto." [".$datos_objeto["tipo"]."]'>".$nombre_objeto." [".$tipo_objeto."]</div>
				<div class='fenContent'>
				<IMG SRC='";echo ($datos_objeto["nom_img"]=="grafica")?dameGrafica($datos_objeto["id_tipo"])."' width=$widthGraph heigth=$heighGraph":"imagenes/".$datos_objeto["nom_img"]."_1.png' ALT='imagen' title=";echo ($tipo_objeto=="M")?ultimoValorModulo($datos_objeto["id_tipo"]):'';echo ">";
				if ($alerta_objeto==1)
					echo "<div ".$css_alerta." ALIGN=center>ALERTA</div>";

				if (($tipo_objeto == "M") and ($datos_objeto["nom_img"] <> "grafica"))
				
					echo "<IMG  SRC='imagenes/utiles/grafica_h.gif' border=0 onclick=\"setCookieEstado();location.href='index.php?action=clickSetImagenGrafica&idModulo=".$datos_objeto["id_objeto"]."&mode='+modo\">";
				
				echo "</div>  
		
			</div>
	
		";

		//Creamos las lineas 
		$aRelaciones=dameRelacionesObjeto($objeto["id_objeto"]);
		if ($aRelaciones !=-1)
		{
			while ($relacion=mysql_fetch_array($aRelaciones))
			{
			$idObjeto2=$relacion["idObjeto2"];
			echo "<div id='Canvas_".$vista["idVista"]."_".$objeto["id_objeto"]."_".$idObjeto2."' style='position:relative;height:5px;width:5px;z-index:0'></div>
				";
			}
		}
	}
echo "";
echo "</div>";
}



// 	


?>

  
 		</div>
	</div>
</div>
	

</body></html>