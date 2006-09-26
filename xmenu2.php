<?php

require("db_functions.php"); // añadimos el php que contiene las funciones de acceso a base de datos
require("cookie_functions.php");


session_start();

	
?>
<!--<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">-->
<html id="idHtml" xmlns="http://www.w3.org/1999/xhtml"><head id="idHead">


<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"><title>Pandora Console </title>

<meta name="author" content="Mike Foster, cross-browser.com">
<meta name="description" content="Programming example for X, a cross-browser DHTML API for Internet Explorer, Netscape Navigator, Gecko, and Opera, available at cross-browser.com">
<link rel="stylesheet" href="styles/main.css" type="text/css">
<script type="text/javascript" src="scripts/x_core.js"></script>
<script type="text/javascript" src="scripts/x_event.js"></script>
<script type="text/javascript" src="scripts/x_slide.js"></script>
<script type='text/javascript' src='scripts/x_drag.js'></script>

<script type='text/javascript' src='objeto.php'></script>
<script type="text/javascript" src="xmenu2.js"></script>
<script type="text/javascript" src="xmenu2_html.js"></script>

<script type="text/javascript" src="xformulario.js"></script>
<script type="text/javascript" src="xformulario_html.php"></script>

<!-- <script type="text/javascript" src="onload.php"></script> -->
<script type="text/javascript" src="onunload.php"></script>

<script type='text/javascript' src='scripts/x_dom.js'></script>
<script type='text/javascript' src='scripts/lib/xtabpanelgroup.js'></script>

<script type="text/javascript" src="scripts/wz_jsgraphics.js"></script>


<script type="text/javascript">
// Obtenemos la cookie estado que define el valor de: pestaña selecionada, 

	vistaActiva=0;
	selectedIndex=0;
	modo="monitor";
	menuLeft=0;
	menuTop=0;
	perfil=0;
 	cookieEstado=GetCookie("estado");
	

	// Comprobamos que existe una cookie de estado
	if (cookieEstado != null)
	{
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
        		}
    		}
	}else{ // Cargamos los valores de la base de datos
	<?

//////////////////////////////////////////////////////////////////////////////////////////
//  		$usuario = dameUsuarioActivo();  Es una fase que siemrpe retorna admin hay que implementarla bien cuando se haga la integración real con Pandora
//////////////////////////////////////////////////////////////////////////////////////////
		$usuario="admin";
		$estado_consola=dameEstadoConsola($usuario);
		echo "vista_activa=".$estado_consola["idVistaActiva"].";";
		echo "menuLeft=".$estado_consola["menuX"].";";
		echo "menuTop=".$estado_consola["menuY"].";";

	?>
	}




var highZ = 3;
//creamos un array en javascript que contendrá la relacion entre el numero de pestaña y el id de la vista que se representa en dicha pestaña
var relacionPestaVista=new Array();
aRelacionesObjetos=new Array(); // Este array almacena el nombre de las relaciones (lineas)
 
</script>

    


<!-- Absolutely Positioned Elements -->

<script type="text/javascript">


<?


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
    '#FF6600', '#CC6600',       // activeBkgnd, inactiveBkgnd
    '#FF6600'                   // boxBkgnd
  );
  // bottom-left
  menu3 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    0, xClientHeight() - 80, 2, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140], 
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#cccc99', '#cccc99',       // activeBkgnd, inactiveBkgnd
    '#cccc99'             // boxWidthsArray,
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
echo "perfil=".$perfil.";"; // Tengo que crear una variable javascript para pasarsela en cookie (con $_SESSION no consigo que funcione)
echo "aVistas = new Array(), aVistas_count =".mysql_num_rows($vistas).";";
echo "cuentaVis=aVistas_count;";
$j=0;
while ($vista=mysql_fetch_array($vistas)){ //recorremos las vistas y creamos un array por cada vista con los objetos que se encuentran incluidos en la vista
	$objetos = dameObjetosVista($vista["idVista"]);
// 	while ($objeto=mysql_fetch_array($objetos)){
 		echo "aObjeto".$vista["idVista"]." = new Array(), aObjeto".$vista["idVista"]."_count =".mysql_num_rows($objetos).";";

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
		}
		relacionPestaVista[".$j."]=".$vista["idVista"].";
		
		";
		$j++;
// 	}

// Creamos las instancias de los objetos (que se corresponderan con los ids de los divs html)
	$i=1;
//global $aObjetos;
//mysql_data_seek($objetos,0);
// 	$objetos = dameObjetosVista($vista["idVista"]);
	while ($objeto=mysql_fetch_array($objetos)){

		$aObjetos[$i-1]=$objeto["id_objeto"]; // Array php de objetos

		echo "aObjeto".$vista["idVista"]."[".$i."] = new xFenster('fen_".$vista["idVista"]."_".$objeto["id_objeto"]."', parseInt(aPos".$vista["idVista"]."['".$i."X']), parseInt(aPos".$vista["idVista"]."['".$i."Y']), 'fenBar".$vista["idVista"].$objeto["id_objeto"]."', 'fenResBtn".$vista["idVista"].$objeto["id_objeto"]."', 'fenMaxBtn".$vista["idVista"].$objeto["id_objeto"]."');";

		$i=$i+1;
	}


	// Creamos los objetos, lineas que representan las relaciones entre objetos
	for ($i=0;$i<sizeof($aObjetos);$i++){
		if (esObjetoDeVista($aObjetos[$i],$vista["idVista"]))
		{
// 			mensaje($aObjetos[$i]);
			$aRelaciones=dameRelacionesObjeto($aObjetos[$i]);
			if ($aRelaciones !=-1)
			{
				while ($relacion=mysql_fetch_array($aRelaciones))
				{
				$irelacionObj1=$i+1; // parece que no me deja sumarlo despues
				$idObjeto2=$relacion["idObjeto2"];
				$irelacionObj2=array_search($idObjeto2,$aObjetos) + 1; //Buscamos la posicion del array php en el que se encuentra el id del segundo objeto. Esto nos sirve para saber cual sera el nombre del objeto javascript, ya que exite una relacion entre los indices de los diferentes arrays. El indexPHP=indexJavascrip - 1;
				echo " jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2." = new jsGraphics('Canvas_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2."');
					jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2.".setColor('#000000'); 
					jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2.".drawLine(aObjeto".$vista["idVista"]."[".$irelacionObj1."].dameX(), aObjeto".$vista["idVista"]."[".$irelacionObj1."].dameY(), aObjeto".$vista["idVista"]."[".$irelacionObj2."].dameX(), aObjeto".$vista["idVista"]."[".$irelacionObj2."].dameY()); 
					jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2.".paint();
					";
				//Añado la nueva relacion al array que guarda todos las creadas
				echo "aRelacionesObjetos['jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2."']=jg_".$vista["idVista"]."_".$aObjetos[$i]."_".$idObjeto2.";";
				 
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




  tpg2 = new xTabPanelGroup('tpg2', 1000, 1000, 25, 'tabPanel', 'tabGroup', 'tabDefault', 'tabSelected');
 

tpg2.select(parseInt(vistaActiva)+1); 

  FormSetup();

}
";



$vista_activa=obtenerVistaActiva();
// Obtenemos la acción a realizar mediante los parametros GET y el nombre "action"


// Una vez aparecido el formulario añadimos a la base de datos, los valores recogidos
if ($HTTP_GET_VARS["action"]=="addagent")
{
	// El tipo A es Agente
	nuevoObjEnVista($_POST["nom_imagen"],'A',0,0,$_POST["group1"],$vista_activa);
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";


}elseif ($HTTP_GET_VARS["action"]=="addmodulo")
{
	// El tipo M es un Modulo
	nuevoObjEnVista($_POST["nom_imagen"],'M',0,0,$_POST["group"],$vista_activa);
	
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="addgrupoAgente")
{
	// El tipo GA es un Grupo de Agentes
	nuevoObjEnVista($_POST["nom_imagen"],'GA',0,0,$_POST["group"],$vista_activa);

	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="addGrupoModulo")
{ 
	// El tipo GM es un grupo de Modulos
	nuevoObjEnVista($_POST["nom_imagen"],'GM',0,0,$_POST["group"],$vista_activa);

	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="guardarVista")
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

}elseif ($HTTP_GET_VARS["action"]=="nuevaVista")
{
	$perfil_activo=obtenerPerfilActivo();
	// Guardamos la vista
  	guardarNuevaVista($_POST["nombre"],$_POST["descripcion"],$perfil_activo);
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?action=guardarPerfil&mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="nuevoPerfil")
{
	// Guardamos la vista
  	$idPerfil=guardarNuevoPefil($_POST["nombre"],$_POST["descripcion"]);

	// Abrimos el nuevo Perfil
// 	cargarPerfil($idPerfil);
	echo "perfil=".$idPerfil.";";
;
	// Actualizamos la pagina para que aparezca el nuevo objeto
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="abrirPerfil")
{
	
// 	cargarPerfil($_POST["group1"]);
// 	echo "alert('33');";
	$idperfil=$_POST["group1"];
	echo "perfil='".$idperfil."'; ";
	echo "setCookieEstado();";
   	echo "window.location.href=location.pathname+'?mode=monitor';";

}/*elseif ($HTTP_GET_VARS["action"]=="guardarPerfil")
{
	guardarPerfil($_SESSION['perfil']);
	echo "window.location.href=location.pathname+'?mode=edition';";

}*/elseif ($HTTP_GET_VARS["action"]=="editarObjeto")
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
	+\"<FORM action=xmenu2.php?action=guardarEdicionObjeto&mode=edition method='post'>\"
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

	echo dameCajaImagenes();



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

}elseif ($HTTP_GET_VARS["action"]=="guardarEdicionObjeto")
{
	editarObjeto($_POST["idObjeto"],$_POST["nom_imagen"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="eliminarObjeto")
{
	eliminarObjeto($_POST["group1"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="eliminarVista")
{
	$err=eliminarVista($_POST["group1"]);
	
	if ($err == 1)
	{
		mensaje("No se ha podido borrar la vista, ya que es la unica vista de la que dispone en este perfil"); 
	}
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="editarVista")
{

	$vista = dameVista($_POST["group1"]);

//Ya se que lo siguiente no es muy elegante, pero javascript y php no es que sean muy amigos. Ya lo cambiare por algo mejor

	echo "
	
	document.write(
	\"<div id='xForm' class='demoBox'>\"
	+ \"<div id='formCerrBtn' class='demoBtn'>X</div>\"
	+  \"<div id='xFormBar' class='demoBar'>FORMULARIO</div>\"
	+  \"<div class='demoContent'>\"
	+\"<FORM action=xmenu2.php?action=guardarEdicionVista&mode=edition method='post'>\"
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


}elseif ($HTTP_GET_VARS["action"]=="guardarEdicionVista")
{

	editarVista($_POST["idVista"],$_POST["nombre"],$_POST["descripcion"]);
	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="editarPerfil")
{
	
 	editarPerfil($_POST["idPerfil"],$_POST["nombre"],$_POST["descripcion"]);
 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="eliminarPerfil")
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

}elseif ($HTTP_GET_VARS["action"]=="convertirVista")
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

}elseif ($HTTP_GET_VARS["action"]=="cerrarVista")
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

}elseif ($HTTP_GET_VARS["action"]=="abrirVista")
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

}elseif ($HTTP_GET_VARS["action"]=="nuevoObjetoVista")
{
	$idVistaActiva=$_POST["idVistaActiva"];
	$idVistaObj=$_POST["group1"];
	$perfilActivo = obtenerPerfilActivo();
	// El tipo V es Vista
	nuevoObjEnVista($_POST["nom_imagen"],'V',0,0,$idVistaObj,$idVistaActiva );

 	echo "window.location.href=location.pathname+'?mode=edition';";

}elseif ($HTTP_GET_VARS["action"]=="clickAbrirVista")
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

}elseif ($HTTP_GET_VARS["action"]=="relacionarObjetos")
{
	crearRelacionObjetos($_POST["group1"],$_POST["group2"]);

	// Actualizamos la pagina para que aparezca el nuevo objeto
 	echo "window.location.href=location.pathname+'?mode='+modo;";
}


// El siguiente if controla el estado del menu

if (($HTTP_GET_VARS["mode"]=="monitor" ) or ($HTTP_GET_VARS["mode"]==""))
{
	echo "\ninsertMenu(2,'a');";

	if ($HTTP_GET_VARS["formulario"]=="actualizar")
	{
		echo "insertFormulario('actualizar');";

	}elseif ($HTTP_GET_VARS["formulario"]=="abrir_perfil")
	{
		echo "insertFormulario('abrir_perfil');";

	}elseif ($HTTP_GET_VARS["formulario"]=="abrir_vista")
	{
		echo "insertFormulario('abrir_vista');";
	}

}elseif ($HTTP_GET_VARS["mode"]=="edition"){ 
	echo "\ninsertMenu(3,'a');";

	if ($HTTP_GET_VARS["formulario"]=="nuevo_agente")
	{
		echo "insertFormulario('nuevo_agente');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nuevo_modulo")
	{
		echo "insertFormulario('nuevo_modulo');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nuevo_grupoAgente")
	{
		echo "insertFormulario('nuevo_grupoAgente');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nuevo_grupoModulo")
	{
		echo "insertFormulario('nuevo_grupoModulo');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="guardar_vista")
	{
		echo "insertFormulario('guardar_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nueva_vista")
	{
		echo "insertFormulario('nueva_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nuevo_perfil")
	{
		echo "insertFormulario('nuevo_perfil');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="guardar_perfil")
	{
		echo "insertFormulario('guardar_perfil');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="editar_objetos")
	{
		echo "insertFormulario('editar_objetos');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="eliminar_objeto")
	{
		echo "insertFormulario('eliminar_objeto');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="eliminar_vista")
	{
		echo "insertFormulario('eliminar_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="editar_vista")
	{
		echo "insertFormulario('editar_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="editar_perfil")
	{
		echo "insertFormulario('editar_perfil');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="eliminar_perfil")
	{
		echo "insertFormulario('eliminar_perfil');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="convertir_vista")
	{
		echo "insertFormulario('convertir_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="abrir_vista")
	{
		echo "insertFormulario('abrir_vista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="nuevo_objetoVista")
	{
		echo "insertFormulario('nuevo_objetoVista');";

	}elseif	 ($HTTP_GET_VARS["formulario"]=="relacionar_objetos")
	{
		echo "insertFormulario('relacionar_objetos');";
	}
}


echo "</script>\n";
echo "<BODY  onUnload=\"javascript:guardarEstado()\" >
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
	$etiqueta_nombre=" <TABLE ALIGN=\"center\">
	  <TR>
    	  	<TD>
	  		".$datos_vista["nombre"]."
	  	</TD>
    	  	<TD>
	  		<IMG SRC='imagenes/utiles/dot_green.gif' border=0 ALT='imagen'>
	  	</TD>
    	  	<TD>
	  		<IMG SRC='imagenes/utiles/dot_red.gif' border=0 ALT='imagen'>
	  	</TD>
		<TD></TD><TD></TD><TD></TD><TD></TD><TD></TD><TD></TD>
		<TD>
	  		<IMG  SRC='imagenes/utiles/cancel.gif' border=0 onclick=\"setCookieEstado();location.href='xmenu2.php?action=cerrarVista&mode='+modo\">
	  	</TD>

	  </TR>
	  		</TABLE>";

    	echo "<a href='#tpg2".$vista["idVista"]."' class='tabDefault'  title='".$datos_vista["descripcion"]."'>".$etiqueta_nombre."</a><span class='linkDelim'>&nbsp;|&nbsp;</span>";

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
	while ($objeto=mysql_fetch_array($objetos)){
	$datos_objeto=dameObjeto($objeto["id_objeto"]);

	$nombre_objeto=dameNombreObjeto($datos_objeto["id_tipo"], $datos_objeto["tipo"]);
	$tipo_objeto=$datos_objeto["tipo"];
	if ($tipo_objeto == "V")
	{
		$tipo_objeto ="  <IMG  SRC='imagenes/utiles/play.gif' border=0 onclick=\"setCookieEstado();location.href='xmenu2.php?action=clickAbrirVista&vista=".$datos_objeto["id_tipo"]."&mode='+modo\">"; 
	}

	echo "
		<div id='fen_".$vista["idVista"]."_".$objeto["id_objeto"]."' class='fenster'>
  			<div id='fenBar".$vista["idVista"].$objeto["id_objeto"]."' class='fenBar' title='".$nombre_objeto." [".$datos_objeto["tipo"]."]'>".$nombre_objeto." [".$tipo_objeto."]</div>
  			<div class='fenContent'>
    			<IMG SRC='imagenes/".$datos_objeto["nom_img"]."_1.gif' ALT='imagen' >
			<br>
			<IMG SRC='imagenes/utiles/dot_green.gif' ALT='imagen'>
			<IMG SRC='imagenes/utiles/dot_red.gif' ALT='imagen'>
			<IMG SRC='imagenes/utiles/b_down.gif' ALT='imagen'>
  			</div>  
  	
		</div>

	";

	//Creamos las lineas 
		$aRelaciones=dameRelacionesObjeto($objeto["id_objeto"]);
		if ($aRelaciones !=-1)
		{
			while ($relacion=mysql_fetch_array($aRelaciones))
			{
			$idObjeto2=$relacion["idObjeto2"];
			echo "<div id='Canvas_".$vista["idVista"]."_".$objeto["id_objeto"]."_".$idObjeto2."' style='position:relative;height:5px;width:5px;'></div>
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