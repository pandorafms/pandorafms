<?php
/*
 * @package Include/help/es/
 */
?>

<h1>Perfil</h1>

<p><?php echo get_product_name(); ?> es una herramienta de gestión Web que permite que trabajen múltiples usuarios con diferentes permisos en múltiples grupos de agentes que hay definidos. En los perfiles se definen los permisos que puede tener un usuario.</p>


<p>Esta lista define qué habilita cada perfil:</p>

<br>

<table cellpadding=4 cellspacing=0 class='bg_f0'>
<tr><th class='bg_caca'>Operación<th class='bg_caca'>Bit de acceso

<tr><td>Ver datos agente (todas las vistas)    <td>AR
<tr><td>Vista táctica    <td>AR
<tr><td>Vista de grupos    <td>AR
<tr><td>Crear un visual console    <td>RW
<tr><td>Crear un informe    <td>RW
<tr><td>Crear una gráfica combinada    <td>RW
<tr><td>Ver informe, gráfica, etc    <td>RR
<tr><td>Aplicar una plantilla de informe<td>RR
<tr><td>Crear una plantilla de informe<td>RM
<tr><td>Crear incidente    <td>IW
<tr><td>Leer incidente    <td>IR
<tr><td>Borrar incidente    <td>IW
<tr><td>Incidente “Become owner”    <td>IM
<tr><td>Borrar incidente que no es tuyo    <td>IM
<tr><td>Ver evento    <td>ER
<tr><td>Validar/Comentar evento    <td>EW
<tr><td>Borrar evento    <td>EM
<tr><td>Ejecutar respuestas<td>EW
<tr><td>Crear incidencia a través del evento (Respuesta)    <td>EW&IW
<tr><td>Gestionar respuestas<td>PM
<tr><td>Gestionar filtros<td>EW
<tr><td>Personalizar columnas de eventos<td>PM
<tr><td>Cambiar propietario/Re-abrir evento    <td>EM
<tr><td>Ver usuarios    <td>AR
<tr><td>Ver Consola SNMP    <td>AR
<tr><td>Validar traps     <td>IW
<tr><td>Mensajes    <td>IW
<tr><td>Cron jobs    <td>PM
<tr><td>Tree view    <td>AR
<tr><td>Update manager (Operación y Administración)    <td>PM
<tr><td>Extension Module Group<td>AR
<tr><td>Vista de gestión agente    <td>AW
<tr><td>Edición del agente y de su .conf    <td>AW
<tr><td>Asignación de alertas ya creadas    <td>LW
<tr><td>Definir, modificar plantillas, comandos y acciones    <td>LM
<tr><td>Gestión de grupos    <td>PM
<tr><td>Crear módulos de inventario    <td>PM
<tr><td>Gestionar módulos (Incluidas todas las subopciones)<td>PM
<tr><td>Operaciones masivas    <td>AW
<tr><td>Crear agente    <td>AW
<tr><td>Duplicar configuración remota<td>AW
<tr><td>Gestión de paradas de servicio<td>AW
<tr><td>Gestión de alertas    <td>LW
<tr><td>Gestión de usuarios    <td>UM
<tr><td>Gestión de consola SNMP<td>PM
<tr><td>Gestión de perfiles<td>PM
<tr><td>Gestión de servidores<td>PM
<tr><td>Auditoría del sistema (edición y visualización)<td>PM
<tr><td>Setup (todas las solapas inferiores incl)    <td>PM
<tr><td>Mantenimiento de la BBDD    <td>DM
<tr><td>Extensiones administración    <td>PM
<tr><td>Barra búsqueda    <td>AR
<tr><td>Gestión de Políticas<td>AW
<tr><td>Desactivar agente/módulo/alerta<td>AD
<tr><td>Validar alertas<td>LM&AR o AW&LW
<tr><td>Vista de mapas de red<td>MR
<tr><td>Edición de mapas de red<td>MW
<tr><td>Borrado de mapas de red propios<td>MW
<tr><td>Borrado de cualquier mapa de red<td>MM

</table>


