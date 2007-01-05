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


<?php

require("../lib/db_functions.php");
require("../lib/extra_functions.php");





?>

function insertFormulario(tipo) 
{


if (tipo == 'nuevo_agente')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=addagent&mode=edition method=\"post\">"
+ "<p>Agentes disponibles:</p>"


<?php
	$nomAgente = dameAgentes();
	echo "+ \"<select name=\'group1\'>\"";
	while ($row=mysql_fetch_array($nomAgente)){
// 		Antigua implementacion, lo dejo porque puede ser util para borrados utilizarlo como borrados multiples
// 		echo " + \"<input type=\'radio\' id=".$row['id_agente']." name=\'group1\' value=\'". $row['id_agente'] . "\'> <label for=".$row['id_agente'].">".$row['nombre']." </label><br>  \"";
		
	

		echo "+ \"<option value=".$row['id_agente'].">".$row['nombre']." </option> \"";
	}
	echo "+ \"</select> <br>\"";

	

	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";

	echo dameCajaImagenes("../imagenes/");

	echo dameCheckboxAutoVistas("A");


?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");


}

else if (tipo == 'nuevo_modulo'){

document.write(

"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=addmodulo&mode=edition method=\"post\">"
+  "  <P>"
<?php
	$nomAgentes = dameAgentes();
	while ($row_agente=mysql_fetch_array($nomAgentes)){
// 		echo "+   \" <LABEL for='agente'>".$row_agente['nombre']." </LABEL><br>\"";
		$nomModulo = dameModulos($row_agente['id_agente']);

		echo " + \"<input type=\'radio\' id=".$row_agente['id_agente']." name=\'group\' value=\'". $row_agente['id_agente']. "\'><label for=".$row_agente['id_agente']." >".$row_agente['nombre']." </label> <br> \"";		


		echo "+ \"<select name=\'group".$row_agente['id_agente']."\'>\"";
		while ($row_modulo=mysql_fetch_array($nomModulo)){
			
// 			echo " + \"<input type=\'radio\' id=".$row_modulo['id_agente_modulo']." name=\'group\' value=\'". $row_modulo['id_agente_modulo']. "\'><label for=".$row_modulo['id_agente_modulo']." >".$row_modulo['nombre']." </label> <br> \"";

		echo "+ \"<option value=".$row_modulo['id_agente_modulo'].">".$row_modulo['nombre']." </option> \"";
		}
		echo "+ \"</select> <br>\"";
		echo "+ \"<br>\"";
	}
	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";

	echo dameCajaImagenes("../imagenes/");
?>

+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}else if (tipo == 'nuevo_grupoAgente'){

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=addgrupoAgente&mode=edition method=\"post\">"
+  "  <P>"
<?php
	$nomgrupoAgente= dameGruposAgentesConAgentes();
	if ($nomgrupoAgente!=-1)
	{
		echo "+ \"<select name=\'group\'>\"";
		while ($row=mysql_fetch_array($nomgrupoAgente)){
			echo "+ \"<option value=".$row['id_grupo'].">".$row['nombre']." </option> \"";
	// 		echo " + \"<input type=\'radio\' id=".$row['id_grupo']." name=\'group\' value=\'". $row['id_grupo'] . "\'><label for=".$row['id_grupo'].">".$row['nombre']." </label><br>  \"";
		}
		echo "+ \"</select> <br>\"";
	
		echo "
			+ \"<BR>\"
			+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
			+ \"<BR><BR>\"
		";
		
	
		echo dameCajaImagenes("../imagenes/");
		echo dameCheckboxAutoVistas("GA");
		echo "+ \"<BR>\"
		+   \" <INPUT type='submit' value='Send'> <INPUT type='reset'>\"";
	}else
		{
		echo "
			+ \"<BR>\"
			+   \" <LABEL for='nombre'> No existe ningun grupo de agentes</LABEL>\"
			+ \"<BR><BR>\"
		";
		}





?>
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}
else if (tipo == 'nuevo_grupoModulo'){

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=addGrupoModulo&mode=edition method=\"post\">"
+  "  <P>"
<?php


	$agentes = dameAgentes();
	//Recorro los agentes en busca de modulos que esten asignados a un grupo de modulos
	while ($row_agente=mysql_fetch_array($agentes)){
		// Obtengo los grupos de modulo para un determinado agente
		$gruposM = dameGruposModuloDelAgente($row_agente['id_agente']);
		
		if (mysql_num_rows($gruposM) > 0)
		{
		echo " + \"<input type=\'radio\' id=".$row_agente['id_agente']." name=\'agente\' value=\'". $row_agente['nombre']. "\'><label for=".$row_agente['id_agente']." >".$row_agente['nombre']." </label> <br> \"";		


		echo "+ \"<select name=\'group\'>\"";

//    		echo "+ \"<INPUT type='hidden' id='agente' name='agente' value='".$row_agente['nombre']."'>\"";
// 
// 		echo "+   \" <LABEL for='agente' >".$row_agente['nombre']." </LABEL><br>\"";
		}


		// Recorro los grupos de modulos del agente
		while ($rowGModulo=mysql_fetch_array($gruposM)){
		
			// Creo el title con los nombres de los modulos que pertenecen a ese determinado grupo y agente
			$modulos=dameModulosDelGrupoModulosAgente($row_agente['id_agente'],$rowGModulo['id_mg']);
			$title="Los modulos de este grupo son: ";
			while ($rowModulo=mysql_fetch_array($modulos)){
				$title=$title." ".$rowModulo["nombre"].";";
			}

			echo "+ \"<option value=".$rowGModulo['id_mg'].">".$rowGModulo['name']." </option> \"";

// 			echo "+ \"<input name='group' value='".$rowGModulo['id_mg']."' id='".$rowGModulo['id_mg'].$row_agente['id_agente']."' type='radio' title='".$title."'> <label for='".$rowGModulo['id_mg'].$row_agente['id_agente']."' title='".$title."' >".$rowGModulo['name']."</label><br>\"";
		
		}
		echo "+ \"</select> <br>\"";
		echo "+ \"<br>\"";

	}

	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";

	echo dameCajaImagenes("../imagenes/");
	echo dameCheckboxAutoVistas("GM");
?>

+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}
else if (tipo == 'guardar_vista'){

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=guardarVista&mode=edition method=\"post\">"
+  "  <P>"
+ "<BR>"
+   " <LABEL for=\"nombre\">Nombre Vista: </LABEL>"
+   "           <INPUT type=\"text\" id=\"nombre\" name=\"nombre\"><BR>"
+ "<BR>"
+   " <LABEL for=\"descripcion\">Descripcion: </LABEL>"

+ "<BR><TEXTAREA NAME=\"descripcion\" ROWS=\"10\" COLS=\"20\" >"

+ "</TEXTAREA>"
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}
else if (tipo == 'nueva_vista'){

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=nuevaVista&mode=edition method=\"post\">"
+  "  <P>"
+ "<BR>"
+   " <LABEL for=\"nombre\">Nombre Vista: </LABEL>"
+   "           <INPUT type=\"text\" id=\"nombre\" name=\"nombre\"><BR>"
+ "<BR>"
+   " <LABEL for=\"descripcion\">Descripcion: </LABEL>"

+ "<BR><TEXTAREA NAME=\"descripcion\" ROWS=\"10\" COLS=\"20\" >"

+ "</TEXTAREA>"
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}

else if (tipo == 'nuevo_perfil'){

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=index.php?action=nuevoPerfil&mode=edition method=\"post\">"
+  "  <P>"
+ "<BR>"
+   " <LABEL for=\"nombre\">Nombre Perfil: </LABEL>"
+   "           <INPUT type=\"text\" id=\"nombre\" name=\"nombre\"><BR>"
+ "<BR>"
+   " <LABEL for=\"descripcion\">Descripcion: </LABEL>"

+ "<BR><TEXTAREA NAME=\"descripcion\" ROWS=\"10\" COLS=\"20\" >"

+ "</TEXTAREA>"
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}else if (tipo == 'abrir_perfil')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=abrirPerfil&mode=monitor method=\"post\">"
+ "<p>Perfiles disponibles:</p>"


<?php
	$perfiles = damePerfiles();

	while ($row=mysql_fetch_array($perfiles)){

		echo " + \"<input type=\'radio\' id=".$row['idPerfil']." name=\'group1\' value=\'". $row['idPerfil'] . "\'> <label for=".$row['idPerfil'].">".$row['Nombre']." </label><br>  \"";
	}

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}


else if (tipo == 'editar_objetos')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=editarObjeto&mode=edition method=\"post\">"
+ "<p>Objetos disponibles:</p>"


<?php
	$idVista = obtenerVistaActiva(); 
// 	mensaje($idVista);
	$objetos = dameObjetosVista($idVista);


	echo "+ \"<select name=\'group1\'>\"";
	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo "+ \"<option value=".$objeto['id_objeto'].">".$nom_obj." </option> \"";

// 		echo " + \"<input type=\'radio\' id=".$objeto['id_objeto']." name=\'group1\' value=\'". $objeto['id_objeto'] . "\'> <label for=".$objeto['id_objeto'].">".$nom_obj." </label><br>  \"";
	}
	echo "+ \"</select> <br>\"";
?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'eliminar_objeto')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=eliminarObjeto&mode=edition method=\"post\">"
+ "<p>Objetos disponibles:</p>"


<?php
	$idVista = obtenerVistaActiva(); 
	$objetos = dameObjetosVista($idVista);



	echo "+ \"<select name=\'group1\'>\"";
	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo "+ \"<option value=". $objeto['id_objeto'].">".$nom_obj." </option> \"";

// 		echo " + \"<input type=\'radio\' id=".$objeto['id_objeto']." name=\'group1\' value=\'". $objeto['id_objeto'] . "\'> <label for=".$objeto['id_objeto'].">".$nom_obj." </label><br>  \"";
	}
	echo "+ \"</select> <br>\"";

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'eliminar_vista')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=eliminarVista&mode=edition method=\"post\">"
+ "<p>Vista que desea eliminar:</p>"


<?php
	$idPerfil = obtenerPerfilActivo();
	$vistas = dameVistasPerfil($idPerfil);
	
	while ($row=mysql_fetch_array($vistas)){
		$vista = dameVista($row['idVista']);
	
		echo " + \"<input type=\'radio\' id=".$row['idVista']." name=\'group1\' value=\'". $row['idVista'] . "\'> <label for=".$row['idVista'].">".$vista['nombre']." </label><br>  \"";
	}

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'editar_vista')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=editarVista&mode=edition method=\"post\">"
+ "<p>Vista que desea editar:</p>"


<?php
	$idPerfil = obtenerPerfilActivo();
	$vistas = dameVistasPerfil($idPerfil);
	
	while ($row=mysql_fetch_array($vistas)){
		$vista = dameVista($row['idVista']);
	
		echo " + \"<input type=\'radio\' id=".$row['idVista']." name=\'group1\' value=\'". $row['idVista'] . "\'> <label for=".$row['idVista'].">".$vista['nombre']." </label><br>  \"";
	}

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}

else if (tipo == 'editar_perfil')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=editarPerfil&mode=edition method=\"post\">"
<?php


	$idPerfil = obtenerPerfilActivo();
	$perfil = damePerfil($idPerfil);

echo "
+ \"<p>Edicion del Perfil:</p>\"
+ \"<INPUT type='hidden' id='idPerfil' name='idPerfil' value='".$perfil["idPerfil"]."'>\"
+  \"  <P>\"
+ \"<BR>\"
+   \" <LABEL for='nombre'>Nombre Perfil: </LABEL>\"
+   \"           <INPUT type='text' id='nombre' name='nombre' value='".$perfil["Nombre"]."' ><BR>\"
+ \"<BR>\"
+   \" <LABEL for='descripcion'>Descripcion: </LABEL>\"

+ \"<BR><TEXTAREA NAME='descripcion' ROWS='10' COLS='20' >".$perfil["Descripcion"]."\"

+ \"</TEXTAREA>\"

";
?>

+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'eliminar_perfil')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=eliminarPerfil&mode=monitor method=\"post\">"
+ "<p>Perfil a eliminar:</p>"


<?php
	$perfiles = damePerfiles();

	while ($row=mysql_fetch_array($perfiles)){

		echo " 
		+ \"<input type=\'radio\' id=".$row['idPerfil']." name=\'group1\' value=\'". $row['idPerfil'] . "\'> <label for=".$row['idPerfil'].">".$row['Nombre']." </label><br>  \"";
	}

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'convertir_vista')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=convertirVista&mode=edition method=\"post\">"
+ "<p>Vista en la que desea adjuntar el objeto:</p>"

<?php
	$idPerfil = obtenerPerfilActivo();
	$vistas = dameVistasPerfil($idPerfil);
	$idVistaActiva = obtenerVistaActiva();	


	while ($row=mysql_fetch_array($vistas)){
		if ($row['idVista'] != $idVistaActiva)
		{
			$vista = dameVista($row['idVista']);
	
			echo " 

			+ \"<INPUT type='hidden' id='idVistaActiva' name='idVistaActiva' value='".$idVistaActiva."'>\"
				
			+ \"<input type=\'radio\' id=".$row['idVista']." name=\'group1\' value=\'". $row['idVista'] . "\'> <label for=".$row['idVista'].">".$vista['nombre']." </label><br>  \"";
		}
	}

	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";
	echo dameCajaImagenes("../imagenes/");

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"
+   " <LABEL for=\"imagen\">ATENCION: La vista que se convertira sera la que este visualizando en el momento de pulsar el boton Aceptar </LABEL>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'abrir_vista')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=abrirVista&mode=edition method=\"post\">"
+ "<p>Vista que desea abrir:</p>"

<?php
	$idPerfil = obtenerPerfilActivo();
	$vistas = dameVistasPerfil($idPerfil);
	$idVistaActiva = obtenerVistaActiva();	


	while ($row=mysql_fetch_array($vistas)){
			$vista = dameVista($row['idVista']);
			if ($row["activa"]==0)
			{
			echo " 
			+ \"<input type=\'radio\' id=".$row['idVista']." name=\'group1\' value=\'". $row['idVista'] . "\'> <label for=".$row['idVista'].">".$vista['nombre']." </label><br>  \"";
			}
		
	}

?>

+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"


+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}

else if (tipo == 'nuevo_objetoVista')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=nuevoObjetoVista&mode=edition method=\"post\">"
+ "<p>Vista que desea representar como objeto:</p>"

<?php
	$idPerfil = obtenerPerfilActivo();
	$vistas = dameVistasPerfil($idPerfil);
	$idVistaActiva = obtenerVistaActiva();	


	while ($row=mysql_fetch_array($vistas)){
		if ($row['idVista'] != $idVistaActiva)
		{
			$vista = dameVista($row['idVista']);
	
			echo " 

			+ \"<INPUT type='hidden' id='idVistaActiva' name='idVistaActiva' value='".$idVistaActiva."'>\"
				
			+ \"<input type=\'radio\' id=".$row['idVista']." name=\'group1\' value=\'". $row['idVista'] . "\'> <label for=".$row['idVista'].">".$vista['nombre']." </label><br>  \"";
		}
	}

	echo "
		+ \"<BR>\"
		+   \" <LABEL for='nombre'>Selecione una imagen de las disponibles: </LABEL>\"
		+ \"<BR><BR>\"
	";

	echo dameCajaImagenes("../imagenes/");


?>

+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"


+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'relacionar_objetos')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=relacionarObjetos&mode=edition method=\"post\">"
+ "<p>Objeto1 de la relacion:</p>"


<?php
	$idVista = obtenerVistaActiva(); 

	$objetos = dameObjetosVista($idVista);

	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo " + \"<input type=\'radio\' id=group1".$objeto['id_objeto']." name=\'group1\' value=\'". $objeto['id_objeto'] . "\'> <label for=group1".$objeto['id_objeto'].">".$nom_obj." </label><br>  \"";
	}

?>

+ "<p>Objeto2 de la relacion:</p>"


<?php
	$idVista = obtenerVistaActiva(); 

	$objetos = dameObjetosVista($idVista);

	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo " + \"<input type=\'radio\' id=group2".$objeto['id_objeto']." name=\'group2\' value=\'". $objeto['id_objeto'] . "\'> <label for=group2".$objeto['id_objeto'].">".$nom_obj." </label><br>  \"";
	}

?>
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'eliminar_relacion')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=eliminarRelacion&mode=edition method=\"post\">"
+ "<p>Relaciones de esta Vista:</p>"


<?php
	$idVista = obtenerVistaActiva(); 

	$relaciones = dameRelacionesVista($idVista);

	while ($relacion =mysql_fetch_array($relaciones)){
		$objeto1 = dameObjeto($relacion['idObjeto1']);
		$objeto2 = dameObjeto($relacion['idObjeto2']);
		$nom_obj1 = dameNombreObjeto($objeto1['id_tipo'],$objeto1['tipo']);
		$nom_obj2 = dameNombreObjeto($objeto2['id_tipo'],$objeto2['tipo']);
		echo " + \"<input type=\'radio\' id=group1".$objeto1['id_objeto']."_".$objeto2['id_objeto']." name=\'group1\' value=\'".$objeto1['id_objeto']."_".$objeto2['id_objeto']. "\'> <label for=group1".$objeto1['id_objeto']."_".$objeto2['id_objeto'].">".$nom_obj1." <--> ".$nom_obj2." </label><br>  \"";
	}
?>


+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'relacionar_estado')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=relacionarEstado&mode=edition method=\"post\">"
+ "<p>Objeto a configurar su estado:</p>"


<?php
	$idVista = obtenerVistaActiva(); 

	$objetos = dameObjetosVista($idVista);
	echo "+ \"<select name=\'group1\' >\"";
	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo "+ \"<option value='".$objeto['id_objeto']."\'>".$nom_obj."</option> \"";
	}
	echo "+ \"</select> <br>\"";




?>

+ "<p>Objetos que pueden influenciar en el estado del objeto anterior:</p>"


<?php
// 	$idVista = obtenerVistaActiva(); 

	$objetos = dameObjetos();
	echo "+ \"<select >\"";
	while ($objeto =mysql_fetch_array($objetos)){
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		echo "+ \"<option value=group2\'".$objeto['id_objeto']."\'>".$nom_obj." [atajo: #".$objeto['id_objeto']."]</option> \"";
	}
	echo "+ \"</select> <br>\"";

?>

+ "<p>Tipo de relacion:</p>"
+ "<P>Expresion de estado: <INPUT TYPE=\"Text\" name=\"expresion\"> <br> Operadores Validos: <br> + = OR <br> * = AND <br> ! = NOT"
+ "<BR>"
+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}
else if (tipo == 'eliminar_relacion_estado')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
+ "<p>"
 +"<FORM action=index.php?action=eliminarRelacionEstado&mode=edition method=\"post\">"
+ "<p>Relaciones de esta Vista:</p>"


<?php
	$idVista = obtenerVistaActiva(); 

	$relaciones = dameRelacionesEstadoVista($idVista);

	while ($relacion =mysql_fetch_array($relaciones)){
		$objeto = dameObjeto($relacion['id_objeto']);
		
		$nom_obj = dameNombreObjeto($objeto['id_tipo'],$objeto['tipo']);
		
		echo " + \"<input type=\'radio\' id=group1".$objeto['id_objeto']." name=\'group1\' value=\'".$objeto['id_objeto']." \'> <label for=group1".$objeto['id_objeto'].">".$nom_obj."</label><br>  \"";
	}
?>


+ "<BR>"
+   " <INPUT type=\"submit\" value=\"Aceptar\"> <INPUT type=\"reset\">"
+ "<BR>"

+" </FORM>"
+ "</p>"
+  "</div>"
+ "</div>");
}

}