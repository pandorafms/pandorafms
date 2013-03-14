<?php
/*
* @package Include/help/es/
*/
?>

<h1>Perfil</h1>

<p>Pandora FMS es una herramienta de gestión Web que permite que trabajen múltiples usuarios con diferentes permisos en múltiples grupos de agentes que hay definidos. En los perfiles se definen los permisos que puede tener un usuario.</p>


<p>Esta lista define qué habilita cada perfil:</p>

<br>

<table cellpadding=4 cellspacing=0 style='background-color: #f0f0f0; border: 1px solid #acacac'>
<tr><th style='background-color: #cacaca'>Operacion<Th  style='background-color: #cacaca'>Bit de acceso
<tr><td>Ver datos agente (todas las vistas)	<td>AR
<tr><td>Vista táctica	<td>AR
<tr><td>Vista mapas de red	<td>AR
<tr><td>Vista de grupos	<td>AR
<tr><td>Crear un visual console	<td>RW
<tr><td>Crear un informe	<td>RW
<tr><td>Crear una grafica combinada	<td>RW
<tr><td>Ver informe, mapa, grafica, etc	<td>RR
<tr><td>Aplicar una plantilla de informe<td>RW
<tr><td>Crear una plantilla de informe<td>RM
<tr><td>Crear incidente	<td>IW
<tr><td>Leer incidente	<td>IR
<tr><td>Borrar incidente	<td>IW
<tr><td>Incidente “Become owner”	<td>IM
<tr><td>Borrar incidente que no es tuyo	<td>IM
<tr><td>Ver evento	<td>ER
<tr><td>Validar/Comentar evento	<td>EW
<tr><td>Borrar evento	<td>EW
<tr><td>Ejecutar respuestas<td>EW
<tr><td>Crear incidencia a traves del evento (Respuesta)	<td>EW&IW
<tr><td>Cambiar propietario/Re-abrir evento	<td>EM
<tr><td>Ver usuarios	<td>AR
<tr><td>Ver Consola SNMP	<td>AR
<tr><td>Validar traps 	<td>IW
<tr><td>Mensajes	<td>IW
<tr><td>Cron jobs	<td>PM
<tr><td>Tree view	<td>AR
<tr><td>Update manager (Operación y Administración)	<td>PM
<tr><td>Extension Module Group<td>AR
<tr><td>Vista de gestion agente	<td>AW
<tr><td>Edición del agente y de su .conf	<td>AW
<tr><td>Asignación de alertas ya creadas	<td>LW
<tr><td>Definir, modificar plantillas, comandos y acciones	<td>LM
<tr><td>Gestión de grupos	<td>PM
<tr><td>Crear modulos de inventario	<td>PM
<tr><td>Gestionar modulos (Incluidas todas las subopciones)<td>PM
<tr><td>Operaciones masivas	<td>AW
<tr><td>Crear agente	<td>AW
<tr><td>Duplicar configuración remota<td>AW
<tr><td>Gestión de paradas de servicio<td>AW
<tr><td>Gestión de alertas	<td>AM
<tr><td>Gestión de usuarios	<td>UM
<tr><td>Gestión de consola SNMP<td>PM
<tr><td>Gestión de perfiles<td>PM
<tr><td>Gestión de servidores<td>PM
<tr><td>Auditoría del sistema (edicion y visualizacion)<td>PM
<tr><td>Setup (todas las solapas inferiores incl)	<td>PM
<tr><td>Mantenimiento de la BBDD	<td>DM
<tr><td>Extensiones administracion	<td>PM
<tr><td>Barra busqueda	<td>AR
<tr><td>Gestión de Políticas<td>AW
<tr><td>Desactivar agente/módulo/alerta<td>AD

</table>


