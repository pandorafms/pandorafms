<?php

// Pandora - the Free monitoring system
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

require("../lib/db_functions.php");
require("../lib/extra_functions.php");
?>


function getCookieVal (offset) {
var endstr = document.cookie.indexOf (";", offset);
if (endstr == -1)
endstr = document.cookie.length;
return unescape(document.cookie.substring(offset, endstr));
}

function GetCookie (name) {
var arg = name + "=";
var alen = arg.length;
var clen = document.cookie.length;
var i = 0;
while (i < clen) {
var j = i + alen;
if (document.cookie.substring(i, j) == arg)
return getCookieVal (j);
i = document.cookie.indexOf(" ", i) + 1;
if (i == 0) break;
}
return null;
}

function SetCookie (name, value) {
var argv = SetCookie.arguments;
var argc = SetCookie.arguments.length;
var expires = (argc > 2) ? argv[2] : null;
var path = (argc > 3) ? argv[3] : null;
var domain = (argc > 4) ? argv[4] : null;
var secure = (argc > 5) ? argv[5] : false;
document.cookie = name + "=" + escape (value) +
((expires == null) ? "" : ("; expires=" + expires.toGMTString())) +
((path == null) ? "" : ("; path=" + path)) +
((domain == null) ? "" : ("; domain=" + domain)) +
((secure == true) ? "; secure" : "");
}

function setCookieEstado()
{



	if ((getLeftMenu(3)>0) || (getTopMenu(3)>0))
	{
		menuX=getLeftMenu(3);
		menuY=getTopMenu(3);
	}else 
	{	
		menuX=getLeftMenu(2);
		menuY=getTopMenu(2);
	}
	nomCookie="estado";

	if (selectedIndex)
	{
		vistaActiva=selectedIndex;
	}
	else
	{	
		vistaActiva=0;
	}

	resultEstado = 'vista_activa='+vistaActiva+'x'+relacionPestaVista[vistaActiva]+'&modo='+modo+'&menu='+menuX+'x'+menuY+'&perfil_activo='+perfil;
	SetCookie (nomCookie ,resultEstado);
}




function guardarEstado()
{
	var expdate = new Date()
	expdate.setTime(expdate.getTime() + (24 * 60 * 60 * 1000 * 31));

	<?php

	$perfil_activo = obtenerPerfilActivo();


	// echo ("alert('".$perfil_activo."');");
	// echo "alert(".$perfil_activo.")";
	$vistasU = dameVistasPerfilActivas($perfil_activo);

	while ($vistaU=mysql_fetch_array($vistasU)){

		echo "
		
		result".$vistaU["idVista"]."='';

  		for (var i = 1; i < aObjeto".$vistaU["idVista"].".length; ++i) {

			result".$vistaU["idVista"]."= result".$vistaU["idVista"]." + i + '=' + aObjeto".$vistaU["idVista"]."[i].dameX() + 'x' + aObjeto".$vistaU["idVista"]."[i].dameY() + '&' ;
	
  		}

		SetCookie (\"objParams".$vistaU["idVista"]."\", result".$vistaU["idVista"].", expdate);

		";

	}

	echo "

	setCookieEstado();

	";
	
	?>

}

