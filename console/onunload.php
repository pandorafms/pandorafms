<?
require("db_functions.php");
require("cookie_functions.php");
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

	<?
	

	?>


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

	<?



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



