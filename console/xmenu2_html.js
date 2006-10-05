 var parMenuLeft;
 var parMenuTop;


function insertMenu(n,p) {
if (n == '3')
{
// MENU EDICION

document.write("<div id='menu"+n+"' class='"+p+"Menu'><!-- menu "+n+" -->"

+"<div id='label"+n+"1' class='mLabel'>Objeto</div>"
+"<div id='box"+n+"1' class='mBox'>"
+"  <a class='m' href=''><b><i>NUEVO: </b></i></a>"
+"<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_agente\")'>Agente</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_modulo\")'>Modulo</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_grupoAgente\")'>Grupo de Agentes</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_grupoModulo\")'>Grupo de Modulos</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_objetoVista\")'>Objeto Vista</a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"editar_objetos\")'><b>Editar</b></a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_objeto\")'><b>Eliminar</b></a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"relacionar_objetos\")'><b>Nueva Relacion</b></a>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_relacion\")'><b>Eliminar Relacion</b></a>"
+"</div>"

+"<div id='label"+n+"2' class='mLabel'>Vista</div>"
+"<div id='box"+n+"2' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"abrir_vista\")'>Abrir</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"nueva_vista\")'>Nueva</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"guardar_vista\")'>Guardar como ...</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"editar_vista\")'>Editar</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_vista\")'>Eliminar</a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"convertir_vista\")'>Convertir en Objeto</a>"
+"</div>"


+"<div id='label"+n+"3' class='mLabel'>Perfil</div>"
+"<div id='box"+n+"3' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"nuevo_perfil\")'>Nuevo</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"editar_perfil\")'>Editar</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_perfil\")'>Eliminar</a>"
+"</div>"

// +"  <a class='m' href='xmenu4_2.html'>Vista como</a>"	
+"<div id='label"+n+"4' class='mLabel'>Modo</div>"
+"<div id='box"+n+"4' class='mBox'>"
+"  <a class='m' href='javascript:cambioModo(\"monitor\")'>Monitor</a>"

+"<div id='label"+n+"5' class='mLabel'>Modo</div>"
+"<div id='box"+n+"5' class='mBox'>"
+"  <a class='m' href='javascript:cambioModo(\"monitor\")'>Monitor</a>"

+"</div>"
+"</div>"

+"</div><!-- end menu"+n+" -->"
); // end document.write()

}else{

// MENU MONITOR

document.write("<div id='menu"+n+"' class='"+p+"Menu'><!-- menu "+n+" -->"
+"<div id='label"+n+"1' class='mLabel'>Abrir</div>"
+"<div id='box"+n+"1' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"abrir_perfil\")'>Perfil</a>"
+"  <a class='m' href='javascript:abrirFormulario(\"abrir_vista\")'>Vista</a>"
+"  <hr>"
+"  <a class='m' href='http://www.hftonline.com/forum/'>Vista Pestanya</a>"
+"</div>"
+"<div id='label"+n+"2' class='mLabel'>Actualizar</div>"
+"<div id='box"+n+"2' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"actualizar\")'>Esta Vista</a>"
+"  <a class='m' href='xmenu4_2.html'>Todas Vistas</a>"
+"</div>"
+"<div id='label"+n+"3' class='mLabel'>Modo</div>"
+"<div id='box"+n+"3' class='mBox'>"
+"  <a class='m' href='javascript:cambioModo(\"edition\")'>Edicion</a>"
+"</div>"



+"</div><!-- end menu"+n+" -->"
); // end document.write()

}

} // end insertMenu()

function cambioModo(nuevoModo)
{

modo=nuevoModo;

location.href = location.pathname+"?mode="+nuevoModo;
}

function abrirFormulario(formulario)
{
location.href = location.pathname+"?mode="+modo+"&formulario="+formulario;
}

function hrefGenerator(n,hrefDst)
{
menu = xGetElementById('menu'+n);
parMenuLeft=xLeft(menu);
parMenuTop=xTop(menu);

location.href = location.pathname+hrefDst+"&menuLeft="+parMenuLeft+"&menuTop="+parMenuTop ;

}

function getLeftMenu(n)
{
menu = xGetElementById('menu'+n);
return xLeft(menu);

}

function getTopMenu(n)
{
menu = xGetElementById('menu'+n);
return xTop(menu);

}

