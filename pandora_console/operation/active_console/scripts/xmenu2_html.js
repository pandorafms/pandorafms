//<!--// Pandora - the Free monitoring system
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
+"  <a class='m' href='javascript:abrirFormulario(\"editar_objetos\")'><b>Editar Objeto</b></a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_objeto\")'><b>Eliminar Objeto</b></a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"relacionar_objetos\")'><b>Nuevo Enlace Grafico</b></a>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_relacion\")'><b>Eliminar Enlace Grafico</b></a>"
+ "<hr>"
+"  <a class='m' href='javascript:abrirFormulario(\"relacionar_estado\")'><b>Nueva Relacion Estado</b></a>"
+"  <a class='m' href='javascript:abrirFormulario(\"eliminar_relacion_estado\")'><b>Eliminar Relacion Estado</b></a>"
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
+ "<hr>"
+"  <a class='m' href='index.php?action=guardar_posicion&mode=edition'>Guardar posicion objetos</a>"
+"</div>"


+"<div id='label"+n+"3' class='mLabel'>Perfil</div>"
+"<div id='box"+n+"3' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"abrir_perfil\")'>Abrir</a>"
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
+"</div>"
+"<div id='label"+n+"2' class='mLabel'>Actualizar</div>"
+"<div id='box"+n+"2' class='mBox'>"
+"  <a class='m' href='javascript:abrirFormulario(\"actualizar\")'>Forzar Actualizacion</a>"
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

