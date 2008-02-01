<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2008 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
$lang_label["view_agents"]="Ver agentes";
$lang_label["manage_incidents"]="Gestión de incidentes";
$lang_label["view_users"]="Ver usuarios";

$lang_label["new_incident"]="Nuevo incidente";
$lang_label["search_incident"]="Buscar incidente";
$lang_label["index_myuser"]="Editar mi usuario";

$lang_label["manage_agents"]="Gestión de agentes";
$lang_label["manage_alerts"]="Gestión de alertas";
$lang_label["manage_users"]="Gestión de usuarios";
$lang_label["manage_groups"]="Gestión de grupos";
$lang_label["system_audit"]="Auditoría del sistema";

$lang_label["ag_title"]="Agentes de Pandora";
$lang_label["agent"]="Agente";
$lang_label["os"]="SO";
$lang_label["alert"]="Alerta";
$lang_label["alerts"]="Alertas";

$lang_label["incident"]="Incidente";
$lang_label["author"]="Autor";
$lang_label["delete"]="Borrar";

$lang_label["in_state_0"]="Abierta y activa";
$lang_label["in_state_1"]="Abierta y con notas";
$lang_label["in_state_2"]="No válida";
$lang_label["in_state_3"]="Caducada";
$lang_label["in_state_13"]="Cerrada";

$lang_label["in_modinc"]="Actualizar incidente";
$lang_label["in_delinc"]="Borrar incidente";

$lang_label["in_notas_t1"]="Notas asociadas al incidente";

$lang_label["in_ipsrc"]="IP(s) Origen";
$lang_label["in_ipdst"]="IP(s) Destino";

$lang_label["priority"]="Prioridad";
$lang_label["status"]="Estado";
$lang_label["in_openedby"]="Propietario";
$lang_label["in_openedwhen"]="Abierto el";
$lang_label["updated_at"]="Actualizado el";

$lang_label["main_text"]="Esta es la Consola de Administración de Pandora. Desde aquí puede gestionar sus agentes, alertas e incidentes. La sesión permanecerá abierta mientras exista actividad.";

$lang_label["id_user"]="ID usuario";
$lang_label["real_name"]="Nombre Real";
$lang_label["telefono"]="Teléfono";
$lang_label["comments"]="Comentarios";
$lang_label["listGroupUser"]="Perfiles/Grupos asignados a este usuario";
$lang_label["user_edit_title"]="Editor de detalles de usuario";
$lang_label["group_avail"]="Grupo(s) disponible(s)";
$lang_label["none"]="Ninguno";

$lang_label["view_agent_title"]="Últimos datos recibidos por el agente";
$lang_label["view_agent_general_data"]="Información general del agente";

$lang_label["module_definition"]="Definición de módulos";
$lang_label["normal_mode"]="Modo normal";
$lang_label["update"]="Actualizar";
$lang_label["create"]="Crear";

$lang_label["module_name"]="Nombre módulo";
$lang_label["module_type"]="Tipo módulo";
$lang_label["module_update_create"]="Edición/Creación de módulo";

$lang_label["field1"]="Campo #1 (Alias, nombre)";
$lang_label["field2"]="Campo #2 (Línea sencilla)";
$lang_label["field3"]="Campo #3 (Texto completo)";
$lang_label["no_access_title"]="No tiene permiso para acceder a esta página";
$lang_label["no_access_text"]="El acceso a esta página está restringido a usuarios autorizados, contacte con el administrador del sistema si necesita ayuda. <br><br>Todos los intentos de acceso a esta página son grabados en los logs de seguridad de Pandora FMS";
$lang_label["header_title"]="Sistema Libre de Monitorización";

$lang_label["gpl_notice"]="es un <b>Proyecto de Software Libre bajo licencia GPL</b>";
$lang_label["gpl_used"]="Desarrollado utilizando herramientas y software GPL"; 
$lang_label["gen_date"]="Página generada el ";

$lang_label["informative"]="Informativo";
$lang_label["low"]="Baja";
$lang_label["medium"]="Media";
$lang_label["serious"]="Grave";
$lang_label["very_serious"]="Muy grave";
$lang_label["maintenance"]="Mantenimiento";
$lang_label["date"]="Fecha";

$lang_label["incident_main_view"]="Vista principal de incidentes";
$lang_label["all_inc"]="Todos los incidentes";
$lang_label["opened_inc"]="Incidentes activos";
$lang_label["openedcom_inc"]="Incidentes activos, con comentarios";
$lang_label["closed_inc"]="Incidentes cerrados";
$lang_label["rej_inc"]="Incidentes rechazados";
$lang_label["exp_inc"]="Incidentes expirados";
$lang_label["rev_incident"]="Revisar incidente";
$lang_label["note_title"]="Añadir nota al incidente";
$lang_label["audit_title"]="Auditoría de Pandora";
$lang_label["logs"]="Logs";
$lang_label["user"]="Usuario";
$lang_label["action"]="Acción";
$lang_label["src_address"]="IP Origen";
$lang_label["alert_listing"]="Lista completa de alertas";
$lang_label["monitor_listing"]="Lista completa de monitores";
$lang_label["times_fired"]="Número de veces lanzada";
$lang_label["description"]="Descripción";
$lang_label["last_fired"]="Lanzada por última vez";
$lang_label["type"]="Tipo";
$lang_label["last_contact"]="Último contacto";
$lang_label["last_data_chunk"]="Contenido de los últimos paquetes enviados por el agente";
$lang_label["graph"]="Gráfico";
$lang_label["raw_data"]="Datos";
$lang_label["data"]="Datos";
$lang_label["agent_name"]="Nombre del agente";
$lang_label["ip_address"]="Dirección IP";
$lang_label["interval"]="Intervalo";
$lang_label["group"]="Grupo";
$lang_label["total_packets"]="Número de paquetes totales";
$lang_label["main_agent_config"]="Pantalla principal de configuración del agente";
$lang_label["agent_selection"]="Seleccionar agente a modificar";
$lang_label["assigned_modules"]="Módulos asignados";
$lang_label["learning_mode"]="Modo de aprendizaje";
$lang_label["assigned_alerts"]="Alertas asignadas";
$lang_label["alert_asociation_form"]="Formulario de asociación de alerta";
$lang_label["module_asociation_form"]="Formulario de asociación de módulo";
$lang_label["name_type"]="Nombre / Tipo";
$lang_label["min_max"]="Min/Máx";
$lang_label["detailed_monitoragent_state"]="Detalle de monitores";
$lang_label["alert_detail"]="Detalle de alertas";
$lang_label["detailed_alert_view"]="Vista detallada de las alertas";
$lang_label["detailed_monitor_view"]="Vista detallada de los monitores";
$lang_label["detailed_full_view"]="Vista detallada";
$lang_label["setup_screen"]="Configuración";
$lang_label["block_size"]="Tamaño de bloque para la paginación";
$lang_label["agent_alert"]="Alerta por defecto de agente caído";
$lang_label["language_code"]="Código de lenguaje para Pandora";
$lang_label["setup_title"]="Configuración de Pandora";
$lang_label["db_maintenance"]="Mantenimiento BBDD";
$lang_label["aclog_pagination"]="Páginas de Auditoría Interna";
$lang_label["log_filter"]="Tipo de filtro de Log";
$lang_label["not_connected"]="No estás conectado/a";
$lang_label["administrator"]="Administrador";
$lang_label["normal_user"]="Usuario estándar";
$lang_label["has_connected"]="Estás conectado/a como";
$lang_label["logged_out"]="Desconectado/a";
$lang_label["logout_msg"]="La sesión ha finalizado. Cierre la ventana del navegador para cerrar la sesión de Pandora.<br><br>";
$lang_label["user_last_activity"]="Última actividad en Pandora";
$lang_label["err_auth"]="Error de Autenticación";
$lang_label["err_auth_msg"]="La combinación usuario/contraseña es incorrecta. Verifique que no está habilitado el Bloqueo de Mayúsculas, los campos distinguen entre mayúsculas y minúsculas.<br><br> Todas las acciones, incluidos los intentos fallidos de acceso son guardados en el sistema de logs de Pandora y pueden ser revisados por cada usuario. Comunique al Administrador cualquier incidente o fallo";

$lang_label["find_crit"]="Seleccione un criterio de búsqueda";

$lang_label["all"]="Todo";
$lang_label["free_text_search"]="Texto libre para buscar (*)";
$lang_label["free_text_search_msg"] ="(*) La búsqueda de texto se hará a partir de todas las palabras introducidas como subcadena, en la descripción del título o en la descripción de cada incidente";
$lang_label["confirmation"]="confirmación";
$lang_label["password"]="Contraseña";
$lang_label["users"]="Usuarios definidos en Pandora";
$lang_label["user_ID"]="ID de usuario";
$lang_label["profile"]="Perfil";
$lang_label["update_agent"]="Actualizar agente";
$lang_label["create_agent"]="Crear agente";
$lang_label["update_alert"]="Actualizar alerta";
$lang_label["create_alert"]="Crear alerta";
$lang_label["create_user"]="Crear usuario";
$lang_label["update_user"]="Actualizar usuario";
$lang_label["alert_config"]="Configuración de alertas";
$lang_label["alertname"]="Nombre de la alerta";
$lang_label["command"]="Comando";
$lang_label["group_management"]="Gestión de grupos";
$lang_label["group_name"]="Nombre de grupo";
$lang_label["user_management"]="Gestión de usuarios";
$lang_label["alert_type"]="Tipo de alerta";
$lang_label["max_value"]="Valor Máximo";
$lang_label["min_value"]="Valor Mínimo";
$lang_label["time_threshold"]="Umbral de tiempo";
$lang_label["assigned_module"] ="Módulo asignado";

$lang_label["green_light"]="Todos los monitores OK";
$lang_label["red_light"]="Al menos un monitor falla";
$lang_label["yellow_light"]="Cambia entre los estados verde y rojo";
$lang_label["blue_light"]="Agente sin datos";
$lang_label["no_light"]="Agente sin monitores";
$lang_label["broken_light"]="Agente caído";

$lang_label["dbmain_title"]="Mantenimiento de la Base de Datos";
$lang_label["purge_30day"]="Borrar los datos con más de 30 días";
$lang_label["purge_7day"]="Borrar los datos con mas de una semana";
$lang_label["purge_90day"]="Borrar los datos con mas de tres meses";
$lang_label["purge_14day"]="Borrar los datos con mas de dos semanas";
$lang_label["purge_3day"]="Borrar los datos con más de tres días";
$lang_label["purge_1day"]="Borrar los datos con más de un día";
$lang_label["purge_all"]="Borrar todos los datos";

$lang_label["rango3"]="Paquetes con menos de tres meses";
$lang_label["rango2"]="Paquetes con menos de un mes";
$lang_label["rango1"]="Paquetes con menos de una semana";
$lang_label["rango11"]="Paquetes con menos de dos semanas";
$lang_label["rango0"]="Paquetes con menos de tres días ";
$lang_label["rango00"]="Paquetes con menos de 24 horas";

$lang_label["db_info"]="Información BBDD";
$lang_label["db_operation"]="Manipulación de la BBDD";
$lang_label["data_received"]="Datos recibidos de";
$lang_label["last_24"]="Últimas 24 Horas";
$lang_label["last_week"]="Última semana";
$lang_label["last_month"]="Último mes";
$lang_label["noagentselected"]="No se ha seleccionado agente";
$lang_label["agentversion"]="Versión del agente";

$lang_label["deleting_data"]="Borrando datos";
$lang_label["while_delete_data"]="Mientras se borran datos para ";
$lang_label["please_wait"]="Por favor, espere";
$lang_label["all_agents"]="Todos los agentes";
$lang_label["db_purge"]="Borrado BBDD";
$lang_label["db_compact"]="Compactado de la BBDD";
$lang_label["db_stat_agent"]="Estadísticas de la Base de Datos por agente";
$lang_label["configure"]="Configurar";

$lang_label["event_main_view"]="Vista principal de eventos";
$lang_label["event_name"]="Nombre del evento";
$lang_label["view_events"]="Ver eventos";
$lang_label["timestamp"]="Fecha/Hora";
$lang_label["links_header"]="Enlaces";
$lang_label["godmode_header"]="Administración";
$lang_label["operation_header"]="Operación";
$lang_label["db_audit"]="BBDD de auditoría";
$lang_label["db_purge_audit"]="Depuración de la Base de Datos de auditoría";
$lang_label["latest_date"]="Última fecha";
$lang_label["first_date"]="Primera fecha";
$lang_label["records"]="Registros";
$lang_label["total"]="Total";
$lang_label["checked_by"]="Validado por";

$lang_label["disabled"]="Desactivado";
$lang_label["active"]="Activo";

$lang_label["begin_date"]="Fecha comienzo (*)";
$lang_label["end_date"]="Fecha fin (*)";
$lang_label["resolution"]="Resolución (%)";
$lang_label["date_format"]="(*) Por favor introduzca la fecha con formato yyyy/mm/dd hh:mm:ss";
$lang_label["please_wait"]="Por favor sea paciente, esta operación puede tardar varios minutos (1-10 minutos)";

$lang_label["welcome_title"]="Bienvenido a la consola de Pandora";
$lang_label["incident_view_filter"]="Visualizando los incidentes";
$lang_label["there_are"]="Hay ";
$lang_label["user_defined"]="usuarios definidos en Pandora";
$lang_label["agent_defined"]="agentes definidos en Pandora";
$lang_label["agent_defined2"]="Agentes definidos en Pandora";
$lang_label["alert_defined"]="alertas definidas en Pandora";
$lang_label["alert_defined2"]="Alertas definidas en Pandora";
$lang_label["data_harvested"]="módulos de datos recogidos por Pandora";
$lang_label["data_timestamp"]="Datos recogidos por un agente por última vez el ";
$lang_label["stat_title"]="Estadísticas generales de Pandora";
$lang_label["no_permission_text"]="No tiene suficientes permisos para acceder a este recurso";
$lang_label["no_permission_title"]="No tiene acceso";

$lang_label["add_note"]="Insertar nota";

$lang_label["search"]="Buscar";
$lang_label["login"]="Login";
$lang_label["logout"]="Salir";

$lang_label["show"]="Mostrar";
$lang_label["doit"]="¡Hazlo!";
$lang_label["add"]="Añadir";

$lang_label["db_purge_event"]="Limpieza de la Base de Datos de eventos";
$lang_label["db_event"]="BBDD de eventos";
$lang_label["max_min"]="Máx/Mín";
$lang_label["max"]="Máximo";
$lang_label["min"]="Mínimo";
$lang_label["med"]="Media";
$lang_label["month_graph"]="Gráfico mensual";
$lang_label["week_graph"]="Gráfico semanal";
$lang_label["day_graph"]="Gráfico diario";
$lang_label["hour_graph"]="Gráfico horario";

$lang_label["days_compact"]="Máx. días antes de comprimir datos";
$lang_label["days_purge"]="Máx. días antes de eliminar datos";

$lang_label["fired"]="Alerta lanzada";
$lang_label["not_fired"]="Alerta no lanzada";
$lang_label["validate_event"]="Validar evento";
$lang_label["validated_event"]="Evento validado";
$lang_label["not_validated_event"]="Evento no validado";

$lang_label["create_group"]="Crear Grupo";

$lang_label["create_group_ok"]="Grupo creado con éxito";
$lang_label["create_group_no"]="Ha habido un problema al crear el grupo";
$lang_label["modify_group_no"]="Ha habido un problema al modificar el grupo";
$lang_label["delete_group_no"]="Ha habido un problema al borrar el grupo";
$lang_label["agent_error"]="Ha habido un error al cargar la configuración del agente";
$lang_label["delete_alert"]="Borrar alerta";
$lang_label["create_alert_no"]="Ha habido un problema al crear la alerta";
$lang_label["update_alert_no"]="Ha habido un problema al actualizar la alerta";
$lang_label["delete_alert_no"]="Ha habido un problema al borrar la alerta";
$lang_label["create_agent_no"]="Ha habido un problema al crear el agente";
$lang_label["update_agent_no"]="Ha habido un problema al actualizar el agente";
$lang_label["delete_agent_no"]="Ha habido un problema al borrar el agente";
$lang_label["update_module_no"]="Ha habido un problema al actualizar el módulo";
$lang_label["add_module_no"]="Ha habido un problema al añadir el módulo";
$lang_label["delete_module_no"]="Ha habido un problema al borrar el módulo";
$lang_label["update_user_no"]="Ha habido un problema al actualizar el usuario";
$lang_label["group_error"]="Ha habido un error al cargar la configuración del grupo";
$lang_label["create_keep_no"]="Ha habido un problema al crear el módulo keepalive en el nuevo agente";
$lang_label["pass_nomatch"]="Las contraseñas no coinciden. Inténtelo de nuevo";
$lang_label["purge_audit_30day"]="Borrar los datos de auditoría excepto los últimos 30 días";
$lang_label["purge_audit_7day"]="Borrar los datos de auditoría excepto la última semana";
$lang_label["purge_audit_90day"]="Borrar los datos de auditoría excepto el último trimestre";
$lang_label["purge_audit_14day"]="Borrar los datos de auditoría excepto las últimas dos semanas";
$lang_label["purge_audit_3day"] ="Borrar los datos de auditoría excepto los últimos tres días";
$lang_label["purge_audit_1day"]="Borrar los datos de auditoría excepto el ultimo día";
$lang_label["purge_audit_all"]="Borrar todos los datos de auditoría";
$lang_label["purge_event_30day"]="Borrar los datos de eventos excepto los últimos 30 días";
$lang_label["purge_event_7day"]="Borrar los datos de eventos excepto la última semana";
$lang_label["purge_event_90day"]="Borrar los datos de eventos excepto el último trimestre";
$lang_label["purge_event_14day"]="Borrar los datos de eventos excepto las últimas dos semanas";
$lang_label["purge_event_3day"]="Borrar los datos de eventos excepto los últimos tres días";
$lang_label["purge_event_1day"]="Borrar todos los datos de eventos, excepto las últimas 24 horas";
$lang_label["purge_event_all"]="Borrar todos los datos de eventos";

$lang_label["deleting_records"]="Borrando registros para el módulo ";
$lang_label["purge_task"]="Tarea de borrado lanzada para el agente ";

$lang_label["manage_config"]="Gestionar conf.";
$lang_label["config_manage"]="Gestión de Configuraciones";
$lang_label["get_info"]="Obtener info.";

$lang_label["are_you_sure"]="¿Está usted seguro?";
$lang_label["users_msg"]="Los perfiles de usuario en Pandora definen qué usuarios pueden acceder a Pandora y que puede hacer cada uno. Los grupos definen elementos en común, cada usuario puede pertenecer a uno o más grupos, y tiene asignado un perfil a cada grupo que pertenezca. Un perfil es una lista de lo que puede y no puede hacer cada grupo, como por ejemplo 'ver incidentes' o 'gestionar bases de datos'. Abajo se muestra una lista de los perfiles disponibles (definidos por los administradores locales de Pandora)";

$help_label["users_msg1"]="Este usuario es especial y tiene permiso para todo, pasando por encima de los privilegios asignados mediante grupos/perfiles";
$help_label["users_msg2"]="Este usuario tiene permisos segregados para ver datos en los agente de su grupo, crear incidentes dentro de aquellos grupos sobre los que tenga acceso y añadir notas en incidentes propios o de terceros";

$help_label["db_purge1"]="Este botón refresca la información sobre el uso de la Base de Datos a lo largo del tiempo";
$help_label["db_purge0"]="Use este control para seleccionar un agente. Es necesario seleccionar un agente tanto para obtener información de la Base de Datos como para borrar datos de la misma";

$lang_label["profiles"] ="Perfiles";
$lang_label["current_dbsetup"]="Configuración actual de la Base de Datos";
$lang_label["dbsetup_info"]="Por favor, asegúrate de que la gestión de la Base de Datos es correcta y de que el sistema automático de gestión de Base de Datos de Pandora está correctamente instalado y funcionando. Es muy importante para el correcto funcionamiento y rendimiento de Pandora.";
$lang_label["profile_title"]="Gestión de perfiles";
$lang_label["create_profile"]="Crear perfil";
$lang_label["profile_name"]="Nombre del perfil";

$lang_label["pandora_management"]="Gestión de Pandora";
$lang_label["manage_db"]="Gestión de BD";
$lang_label["incident_view"]="Ver incidentes";
$lang_label["incident_edit"]="Editar incidentes";
$lang_label["agent_edit"]="Editar agentes";
$lang_label["alert_edit"]="Editar alertas";
$lang_label["global_profile"]="Perfil global";
$lang_label["name"]="Nombre";
$lang_label["manage_profiles"]="Gestión de perfiles";

$lang_label["error_profile"]="ERROR: En este momento sólo el Administrador General puede administrar perfiles"; 
$lang_label["never"]="Nunca";
$lang_label["graph_res"]="Resolución de los gráficos (1 baja, 5 alta)";

$help_label["AR"]="Permisos de Lectura de agentes";
$help_label["AW"]="Permisos de Escritura sobre agentes";
$help_label["AM"]="Permisos de gestión de agentes";
$help_label["IR"]="Permisos de lectura al sistema de incidentes";
$help_label["IW"]="Permisos de escritura al sistema de incidentes";
$help_label["IM"]="Permisos de gestión al sistema de incidentes";
$help_label["LW"]="Permisos de asignación de alertas";
$help_label["LM"]="Permisos de gestión de alertas";
$help_label["UM"]="Permisos de gestión de usuarios";
$help_label["DM"]="Permisos de gestión de la BD";
$help_label["PM"]="Permisos de gestión de Pandora";

$lang_label["copy_conf"]="Copiar configuración";
$lang_label["fromagent"]="desde el agente";
$lang_label["toagent"]="Agente(s) destino";

$lang_label["step_compact"]="Interpolación de la compactación (Horas: 1 bueno, 10 medio, 20 malo)";

$lang_label["setup_links"]="Enlaces";
$lang_label["create_link_no"]="Ha habido un problema al crear el enlace";
$lang_label["create_link_ok"]="El enlace se ha creado correctamente";
$lang_label["modify_link_no"]="Ha habido un problema al modificar el enlace";
$lang_label["delete_link_no"]="Ha habido un problema al borrar el enlace";
$lang_label["link_management"]="Gestión de Enlaces";
$lang_label["link_name"]="Nombre enlace";
$lang_label["link"]="Enlace";

$lang_label["attached_files"]="Ficheros adjuntos";

$lang_label["export_data"]="Exportar datos";
$lang_label["date_range"]="Rango de fechas";
$lang_label["from"]="Desde";
$lang_label["to"]="Hasta";
$lang_label["export"]="Exportar";
$lang_label["csv"]="Formato CSV";
$lang_label["export_title"]="Resultados del volcado de datos de la BD";
$lang_label["source_agent"]="Agente origen";

$lang_label["definedprofiles"]="Perfiles definidos en Pandora";
$lang_label["attachfile"]="Añadir archivo";
$lang_label["filename"]="Nombre del archivo";
$lang_label["size"]="Tamaño";

$lang_label["upload"]="Subir";
$lang_label["module"]="Módulo";
$lang_label["modules"]="Módulos";
$lang_label["incident_status"]="Estado de los incidentes";
$lang_label["statistics"]="Estadísticas";
$lang_label["incident_priority"]="Prioridades de los incidentes";
$lang_label["copy"]="Copiar";
$lang_label["choose_agent"]="Escoja agente";
$lang_label["press_db_info"]="Pulse aquí para ver información de la BD como texto";
$lang_label["event_statistics"]="Estadísticas de eventos";

$lang_label["deletedata"]="Borrar datos";
$lang_label["source"]="Origen";
$lang_label["destination"]="Destino";
$lang_label["noagents_del"]="No se han seleccionado agentes destino para el borrado";
$lang_label["noagents_cp"]="No se han seleccionado agentes destino para la copia";
$lang_label["datacopy"]="Copia de datos";
$lang_label["copymod"]="Copiando módulo";
$lang_label["copyale"]="Copiando alerta";
$lang_label["copyage"]="Copiando agente";
$lang_label["notfoundmod"]="No se ha encontrado el módulo ";
$lang_label["inagent"]=" en el agente ";
$lang_label["you_must_select_modules"]="Se deben seleccionar módulos y/o alertas como objeto de la copia";
$lang_label["packets_by_date"]="Paquetes por rangos de fecha";
$lang_label["packets_by_agent"]="Paquetes por agente";
$lang_label["modules_per_agent"]="Módulos por agente"; // Graphic title, dont use tildes
$lang_label["event_total"]="Eventos totales";
$lang_label["events_per_user"]="Eventos por usuario";
$lang_label["events_per_group"]="Eventos por grupo";
$lang_label["multormod"]="Módulo de origen múltiple";

$lang_label["db_refine"]="Depurar BBDD";
$lang_label["filtering_datamodule"]="Filtrando módulo de datos";
$lang_label["nomodules_selected"]="No se han seleccionado módulos";
$lang_label["purge_below_limits"]="Borrar datos fuera de estos límites";
$lang_label["max_eq_min"]="Máximo igual al mínimo";

$lang_label["agent_conf"]="Configuración de agentes";
$lang_label["mod_alert"]="Modificar alerta";
$lang_label["filter"]="Filtro";

$lang_label["summary"]="Lista de agentes";
$lang_label["users_"]="Usuarios de Pandora";
$lang_label["incidents"]="Incidentes"; 
$lang_label["events"]="Eventos";
$lang_label["definedgroups"]="Grupos definidos en Pandora";
$lang_label["update_profile"]="Actualizar perfil";
$lang_label["update_group"]="Actualizar grupo";
$lang_label["create_incident"]="Crear incidente";
$lang_label["attach_error"]="El archivo no ha podido ser guardado.<br>";
$lang_label["db_info2"]="Información de la Base de Datos";
$lang_label["db_agent_bra"]="Datos del agente "; 
$lang_label["db_agent_ket"]=" en la Base de Datos";
$lang_label["get_data"]="Obtener datos";
$lang_label["get_data_agent"]="Obtener datos de un agente"; 
$lang_label["purge_data"]="Borrar datos";

$lang_label["group_detail"]="Detalle de grupos"; 
$lang_label["monitors"]="Monitores";
$lang_label["group_view"]="Detalle de los grupos de agentes";
$lang_label["agents"]="Agentes";
$lang_label["down"]="Caídos"; 
$lang_label["ok"]="Ok"; 
$lang_label["fail"]="Fallo";  
$lang_label["pandora_db"]="Base de datos de Pandora";
$lang_label["create_profile_ok"]="Perfil creado correctamente";
$lang_label["profile_upd"]="Perfil actualizado correctamente";
$lang_label["update_agent_ok"]="Agente actualizado correctamente";
$lang_label["create_agent_ok"]="Agente creado correctamente";
$lang_label["delete_agent_ok"]="Agente eliminado correctamente";
$lang_label["update_alert_ok"]="Alerta actualizada correctamente";
$lang_label["create_alert_ok"]="Alerta creada correctamente";
$lang_label["delete_alert_ok"]="Alerta eliminada correctamente";
$lang_label["update_module_ok"]="Módulo actualizado correctamente";
$lang_label["add_module_ok"]="Módulo añadido correctamente";
$lang_label["delete_module_ok"]="Módulo eliminado correctamente";
$lang_label["alert_error"]="Ha habido un error al cargar la configuración de la alerta";
$lang_label["modify_group_ok"]="Grupo actualizado correctamente";
$lang_label["delete_group_ok"]="Grupo eliminado correctamente";
$lang_label["modify_link_ok"]="Enlace actualizado correctamente";
$lang_label["delete_link_ok"]="Enlace eliminado correctamente";
$lang_label["from2"]=" desde ";
$lang_label["to2"]=" hasta ";
$lang_label["del_sel_err"]="Se deben seleccionar módulos o alertas para borrar";
$lang_label["graf_error"]="Ha habido un error al localizar la fuente del gr&aactue;fico"; 
$lang_label["create_user_ok"]="Usuario creado correctamente";
$lang_label["create_user_no"]="Ha habido un problema al crear el usuario";
$lang_label["delete_user_ok"]="Usuario eliminado correctamente";
$lang_label["delete_user_no"]="Ha habido un problema al eliminar el usuario"; 
$lang_label["delete_profile_ok"]="Perfil eliminado correctamente";
$lang_label["delete_profile_no"]="Ha habido un problema al eliminar el perfil";
$lang_label["profile_error"]="Ha habido un problema al cargar el perfil";
$lang_label["user_error"]="Ha habido un problema al cargar el usuario";
$lang_label["del_incid_ok"]="Incidente eliminado correctamente";
$lang_label["del_incid_no"]="Ha habido un problema al eliminar el incidente";
$lang_label["upd_incid_ok"]="Incidente actualizado correctamente";
$lang_label["upd_incid_no"]="Ha habido un problema al actualizar el incidente";
$lang_label["create_incid_ok"]="Incidente creado correctamente";
$lang_label["create_note_ok"]="Nota añadida correctamente";
$lang_label["del_note_ok"]="Nota eliminada correctamente";
$lang_label["del_note_no"]="Ha habido un problema al eliminar la nota";
$lang_label["delete_event_ok"]="Evento eliminado correctamente";
$lang_label["validate_event_ok"]="Evento validado correctamente";
$lang_label["delete_event"]="Eliminar evento";
$lang_label["validate"]="Validar";
$lang_label["incident_user"]="Autores de los incidentes";
$lang_label["incident_source"]="Origenes de los incidentes";
$lang_label["incident_group"]="Grupos de los incidentes";
$lang_label["users_statistics"]="Estadísticas de actividad de los usuarios";
$lang_label["update_user_ok"]="Usuario actualizado correctamente";

$lang_label["agent_detail"]="Detalle agente";

$lang_label["snmp_console_alert"]="Alertas SNMP";
$lang_label["OID"]="OID";
$lang_label["SNMP_agent"]="Agente SNMP";
$lang_label["SNMP_console"]="Consola SNMP";
$lang_label["customvalue"]="Valor de usuario";

$lang_label["agent_type"]="Tipo de agente";
$lang_label["snmp_assigned_alerts"]="Alertas SNMP";

$lang_label["max_alerts"]="Número máximo de alertas";
$lang_label["min_alerts"]="Número mínimo de alertas";

$lang_label["module_group"]="Grupo módulos";
$lang_label["ip_target"]="IP destino";
$lang_label["tcp_rcv"]="Recibir TCP";
$lang_label["tcp_send"]="Enviar TCP";
$lang_label["tcp_port"]="Puerto TCP";
$lang_label["maxdata"]="Dato máx.";
$lang_label["mindata"]="Dato mín.";
$lang_label["snmp_oid"]="SNMP OID";
$lang_label["module_interval"]="Intervalo módulo";
$lang_label["snmp_community"]="Comunidad SNMP";
$lang_label["server_asigned"]="Servidor asignado";
$lang_label["remote"]="Remota";
$lang_label["default_server"]="Servidor activo";
$lang_label["incident_manag"]="Gestión de incidentes";

$lang_label["del_message_ok"]="Mensaje borrado correctamente";
$lang_label["del_message_no"]="Error al borrar el mensaje";
$lang_label["read_mes"]="Leer mensajes";
$lang_label["message"]="Mensaje";
$lang_label["messages"]="Mensajes";
$lang_label["messages_g"]="Mensajes a grupos";
$lang_label["subject"]="Asunto";
$lang_label["new_message"]="Nuevo mensaje";
$lang_label["new_message_g"]="Nuevo mensaje a un grupo";
$lang_label["send_mes"]="Enviar mensaje";
$lang_label["m_from"]="Remitente"; //from en mensajes
$lang_label["m_to"]="Destinatario"; //to en mensajes
$lang_label["sender"]="Remitente";
$lang_label["message_ok"]="Mensaje insertado correctamente";
$lang_label["message_no"]="No se ha podido crear el mensaje";
$lang_label["no_messages"]="No hay mensajes";
$lang_label["new_message_bra"]="Tienes ";
$lang_label["new_message_ket"]=" mensaje(s) por leer.";
$lang_label["no_subject"]="Sin Asunto";
$lang_label["read"]="Leído";
$lang_label["reply"]="Responder";

$lang_label["general_config"]="Configuración general";
$lang_label["no_profile"]="Este usuario no tiene ningún perfil/grupo asociado";
$lang_label["no_agent"]="No hay ningún agente incluido en este grupo";
$lang_label["no_change_field"]="Este campo no puede modificarse en el modo edición";
$lang_label["no_alert"]="Ningún agente de este grupo tiene alertas definidas";
$lang_label["total_data"]="Datos totales";

$lang_label["no_incidents"]="Ningún incidente se ajusta a tu filtro de búsqueda";
$lang_label["no_agent_alert"]=", por tanto no hay alertas";

$lang_label["wrote"]=" escribió";
$lang_label["no_snmp_agent"]="No hay definido ningún agente SNMP";
$lang_label["no_snmp_alert"]="No hay definida ninguna alerta SNMP";
$lang_label["no_agent_def"]="No hay ningún agente definido";

$lang_label["view_servers"]="Servidores Pandora";
$lang_label["no_server"]="No hay ningún servidor configurado en la base de datos";
$lang_label["master"]="Principal";
$lang_label["checksum"]="Check";
$lang_label["snmp"]="SNMP";
$lang_label["laststart"]="Arrancado el";
$lang_label["lastupdate"]="Actualizado el";
$lang_label["network"]="Red";
$lang_label["server_detail"]="Detalle de configuración";
$lang_label["no_modules"]="Este agente no tiene ningún módulo definido";
$lang_label["no_monitors"]="Este agente no tiene ningún monitor definido";
$lang_label["no_alerts"]="Este agente no tiene ninguna alerta definida";
$lang_label["server"]="Servidor";

$lang_label["no_sel_mod"]="No se ha seleccionado ningún módulo";

$lang_label["no_event"]="No hay eventos";
$lang_label["agent_access_rate"]="Accesibilidad agente (24h)";
$lang_label["agent_module_shareout"]="Distribución de módulos";
$lang_label["int"]="Itv."; // Nombre corto para intervalo
$lang_label["manage_servers"]="Gestión servidores";
$lang_label["update_server"]="Actualizar servidor";
$lang_label["upd_server_ok"]="Servidor actualizado correctamente";
$lang_label["upd_server_no"]="Ha habido un problema al actualizar el servidor";
$lang_label["del_server_ok"]="Servidor eliminado correctamente";
$lang_label["del_server_no"]="Ha habido un problema al eliminar el servidor";
$lang_label["groups"]="grupos";

$lang_label["other"]="Otro";
$lang_label["icon"]="Icono";
$lang_label["agent_exists"]="El agente ya existe";
$lang_label["graph_order"]="Orden del gráfico";
$lang_label["truetype"]="Fuentes truetype";

$lang_label["right_left"]="Der. a Izq."; //derecha a izquierda
$lang_label["left_right"]="Izq. a Der."; //izquierda a derecha

$lang_label["cannot_read_snmp"]="No se puede leer SNMP del origen";
$lang_label["ok_read_snmp"]="El origen SNMP ha sido analizado";
$lang_label["cancel"]="Cancelar";
$lang_label["network_module_refresh_exec"]="Ejecutado el refresco del módulo de red";
$lang_label["next_contact"]="Siguiente contacto con el agente";
$lang_label["out_of_limits"]="Fuera de limites";
$lang_label["background_image"]="Imagen de fondo";
$lang_label["help"]="Ayuda";
$lang_label["no_monitors_g"]="Este grupo no tiene ningún monitor definido";
$lang_label["dbprohelp"]="El proceso de borrado de la base de datos es usado para borrar aquellas entradas de la BBDD que por alguna razon necesitan ser borradas";
$lang_label["valprohelp"]="El valor del módulo del agente mínimo y máximo que delimitan los valores válidos. Cualquier otro valor fuera de ese rango será eliminado";

// PANDORA 1.3

$lang_label["reporting"]="Informes";
$lang_label["agent_general_reporting"]="Foto general";
$lang_label["load"]="Carga";
$lang_label["information"]="Información general";
$lang_label["parent"]="Padre";
$lang_label["validate_event_failed"]="Validación de evento fallida";
$lang_label["tactical_server_information"]="Información táctica del servidor";
$lang_label["show_unknown"]="Mostrar módulos desconocidos en vista global";
$lang_label["show_lastalerts"]="Mostrar alertas disparadas en la vista global";
$lang_label["manage_modules"]="Gestionar módulos";
$lang_label["modify_module_ok"]="Actualización de módulos correcta";
$lang_label["modify_module_no"]="Problema al modificar los módulos";
$lang_label["module_management"]="Gestión de módulos";
$lang_label["defined_modules"]="Módulos definidos";
$lang_label["cat_0"]="Datos del agente de software";
$lang_label["cat_1"]="Monitores del agente de software";
$lang_label["cat_2"]="Datos del agente de red";
$lang_label["cat_3"]="Monitores del agente de red";
$lang_label["unknown"]="Desconocido";
$lang_label["create_module"]="Crear módulo";
$lang_label["network_templates"]="Plantillas de red";
$lang_label["snmp_modules"]="Módulos SNMP";
$lang_label["network_components_groups"]="Grupos de componentes de red";
$lang_label["network_components"]="Componentes de red";
$lang_label["create_ok"]="Creado satisfactoriamente";
$lang_label["create_no"]="No se ha podido crear. Error al insertar los datos en la BD";
$lang_label["modify_ok"]="Actualizado correctamente";
$lang_label["modify_no"]="No se ha podido actualizar. Error al actualizar los datos en la BD";
$lang_label["delete_no"]="No se ha podido borrar. Error al borrar los datos de la BD";
$lang_label["delete_ok"]="Borrado correctamente";
$lang_label["network_component_group_management"]="Gestión de grupos de componentes de red";
$lang_label["network_component_management"]="Gestión de componentes de red";
$lang_label["oid"]="OID";
$lang_label["recon_server"]="Servidor Recon";
$lang_label["snmp_console"]="Consola SNMP";
$lang_label["network_server"]="Servidor de red";
$lang_label["data_server"]="Servidor de datos";
$lang_label["md5_checksum"]="Comprobación MD5";
$lang_label["nc_groups"]="Grupos de componentes de red";
$lang_label["nc.group"]="Grupo C.R";
$lang_label["manual_config"]="Configuración manual";
$lang_label["network_component"]="Componente de red";
$lang_label["not_available_in_edit_mode"]="No disponible en modo edición";
$lang_label["using_network_component"]="Usando componente de red";
$lang_label["view_mode"]="Modo vista";
$lang_label["setup_mode"]="Modo gestión";
$lang_label["refresh_data"]="Refrescar datos";
$lang_label["lag"]="Demora";
$lang_label["N/A"]="N/A";
$lang_label["done"]="Hecho";
$lang_label["pending"]="Pendiente";
$lang_label["progress"]="Progreso";
$lang_label["task_name"]="Nombre tarea";
$lang_label["days"]="días";
$lang_label["day"]="día";
$lang_label["week"]="semana";
$lang_label["weeks"]="semanas";
$lang_label["month"]="mes";
$lang_label["months"]="meses";
$lang_label["hours"]="horas";
$lang_label["network_profile"]="Perfil de red";
$lang_label["manage_recontask"]="Gestión recontask";
$lang_label["yes"]="Sí";
$lang_label["no"]="No";
$lang_label["view"]="Ver";
$lang_label["number_of_modules"]="Nº Módulos";
$lang_label["network_profile_management"]="Gestión de perfiles de red";
$lang_label["graph_builder"]="Creador de gráficos";
$lang_label["combined_image"]="Visualización de imágenes combinadas";
$lang_label["redraw"]="Redibujar";
$lang_label["graph_builder_modulelist"]="Lista del creador de imágenes";
$lang_label["seconds"]="Segundos";
$lang_label["custom_graph_name"]="Nombre de gráfica combinada";
$lang_label["save"]="Grabar";
$lang_label["Manage"]="Gestionar";
$lang_label["group_view_tab"]="Vista de grupo";
$lang_label["alerts"]="alertas";
$lang_label["Alerts"]="Alertas";
$lang_label["data"]="datos";
$lang_label["Data"]="Datos";
$lang_label["Main"]="Principal";
$lang_label["version"]="Versión";
$lang_label["tactical_server_information"]="Vista táctica del servidor";
$lang_label["no_rtask"]="No hay ninguna tarea de reconocimiento configurada";
$lang_label["no_netprofiles"]="No hay ningún perfil de red definido";
$lang_label["site_news"]="Noticias del sistema";
$lang_label["at"]="A las";
$lang_label["says"]="dijo";
$lang_label["delete_sel"]="Borrar seleccionados";
$lang_label["available_templates"]="Available templates";
$lang_label["assign"]="Asignar";
$lang_label["graph_store"]="Guardar imagen combinada de usuario";
$lang_label["private"]="Privado";
$lang_label["store"]="Guardar";
$lang_label["store_graph_suc"]="Gráfica creada correctamente";
$lang_label["store_graph_error"]="Hubo un problema al guardar la gráfica";
$lang_label["custom_graph_viewer"]="Visor de imágenes combinadas";
$lang_label["graph_name"]="Nombre gráfica";
$lang_label["custom_graphs"]="Gráficas combinadas";
$lang_label["custom_reporting"]="Informes personalizados";
$lang_label["alert_text"]="Alerta de texto";
$lang_label["text"]="Texto";
$lang_label["delete_data_above"]="Dato borrado";
$lang_label["enabled"]="Habilitado";
$lang_label["average_per_hourday"]="Media por hora/día";
$lang_label["datatable"]="Tabla de datos";
$lang_label["export_type"]="Tipo de exportación";
$lang_label["sunday"]="Domingo";
$lang_label["monday"]="Lunes";
$lang_label["tuesday"]="Martes";
$lang_label["wednesday"]="Miércoles";
$lang_label["thurdsday"]="Jueves";
$lang_label["friday"]="Sábado";
$lang_label["saturday"]="Domingo";
$lang_label["hr"]="Hr";
$lang_label["get_file"]="Descargar fichero";
$lang_label["visual_console"]="Consola visual";
$lang_label["elements"]="Elementos";
$lang_label["minutes"]="minutos";
$lang_label["avg_only"]="Sólo la media";
$lang_label["avg_value"]="Valor medio";
$lang_label["auto_refresh_time"]="Tiempo de autorefresco";
$lang_label["refresh"]="refrescar";
$lang_label["threshold"]="intervalo";
$lang_label["min_valid_value_help"]="Valor mínimo posible a considerar como 'válido', por debajo de ese límite Pandora FMS disparará la alerta";
$lang_label["max_valid_value_help"]="Valor máximo posible a considerar como 'válido', por encima de ese límite Pandora FMS disparará la alerta";
$lang_label["alert_time_threshold_help"]="Este valor debe ser como mínimo, el intervalo del módulo multiplicado por el mínimo número de alertas + 1";
$lang_label["style_template"]="Plantilla de estilo";
$lang_label["report_name"]="Nombre de informe";
$lang_label["custom_reporting_builder"]="Creador de informes personalizados";
$lang_label["report_builder"]="Creador de informes";
$lang_label["manage_reporting"]="Gestionar informes";
$lang_label["report_items"]="Reportar elementos";
$lang_label["period"]="Periodo";
$lang_label["reporting_item_add"]="Añadir elemento al informe";
$lang_label["template"]="Plantilla";
$lang_label["add_mod_ok"]="Módulos añadidos correctamente ";
$lang_label["simple_graph"]="Gráfica simple";
$lang_label["custom_graph"]="Gráfica combinada";
$lang_label["SLA"]="S.L.A";
$lang_label["event_report"]="Informe de eventos";
$lang_label["alert_report"]="Informe de alertas";
$lang_label["monitor_report"]="Informe de monitores";
$lang_label["reporting_type"]="Tipo de informe";
$lang_label["sla_max"]="Valor máximo para el SLA";
$lang_label["sla_min"]="Valor mínimo para el SLA";
$lang_label["sla_limit"]="Límite (%) para el SLA";
$lang_label["up"]="Activo";
$lang_label["map_builder"]="Creador de mapas";
$lang_label["map_name"]="Nombre de mapa";
$lang_label["tactical_view"]="Vista táctica";
$lang_label["tactical_indicator"]="Indicador táctico";
$lang_label["monitor_checks"]="Comprobaciones de monitor";
$lang_label["data_checks"]="Comprobaciones de datos";
$lang_label["group_view_menu"]="Vista de grupo";
$lang_label["site_news_management"]="Gestión de noticias";
$lang_label["Pandora_FMS_summary"]="Resumen de Pandora FMS";
$lang_label["by"]="por";
$lang_label["create_reporting_ok"]="El informe ha sido creado satisfactoriamente";
$lang_label["create_reporting_no"]="Hubo un problema al crear el informe";
$lang_label["delete_reporting_ok"]="Informe borrado satisfactoriamente";
$lang_label["delete_reporting_no"]="Hubo un problema al borrar el informe";
$lang_label["hour"]="hour";
$lang_label["2_hours"]="Dos horas";
$lang_label["6_hours"]="Seis horas";
$lang_label["12_hours"]="12 horas";
$lang_label["last_day"]="Un día";
$lang_label["two_days"]="Dos días";
$lang_label["five_days"]="Cinco días";
$lang_label["15_days"]="15 días";
$lang_label["two_month"]="Dos meses";
$lang_label["six_months"]="Seis meses";
$lang_label["min."]="Mín.";
$lang_label["max."]="Máx.";
$lang_label["alert_status"]="Estado alertas";
$lang_label["background"]="Imagen de fondo";
$lang_label["width"]="Ancho";
$lang_label["height"]="Alto";
$lang_label["static_graph"]="Imagen estática";
$lang_label["line"]="Línea";
$lang_label["pos_x"]="Posición eje X";
$lang_label["pos_y"]="Posición eje Y";
$lang_label["image"]="Imagen";
$lang_label["label"]="Etiqueta";
$lang_label["parent_item"]="Objeto padre";
$lang_label["map_linked"]="Mapa enlazado";
$lang_label["link_color"]="Color de enlace";
$lang_label["label_color"]="Color de etiqueta";
$lang_label["white"]="Blanco";
$lang_label["black"]="Nigro";
$lang_label["time_from"]="Fecha desde";
$lang_label["time_to"]="Fecha hasta";
$lang_label["time"]="Hora";
$lang_label["module_graph"]="Gráfico de módulo";
$lang_label["map_item_add"]="Añadir objeto al mapa";
$lang_label["graph_event_total"]="Gráfica de eventos";
$lang_label["graph_event_group"]="Gráfica de eventos por grupo";
$lang_label["graph_event_user"]="Gráfica de eventos por usuario";
$lang_label["db_agente_paquetes"]="Paquetes por agente";
$lang_label["db_agente_modulo"]="Módulos por agente";
$lang_label["inc_stat_status"]="Incidentes por estado";
$lang_label["inc_stat_priority"]="Incidentes por prioridad";
$lang_label["inc_stat_user"]="Incidentes por usuario";
$lang_label["inc_stat_source"]="Incidentes por origen";
$lang_label["inc_stat_group"]="Incidentes por grupo";
$lang_label["no_layout_def"]="No hay esquemas definidos";
$lang_label["no_reporting_def"]="No hay informes definidos";
$lang_label["no_map_def"]="No hay mapas definidos";
$lang_label["no_repitem_def"]="No hay definidos elementos en el informe";
$lang_label["message_read"]="Mensaje leído";
$lang_label["message_not_read"]="Mensaje sin leer";
$lang_label["Factor"]="Factor";
$lang_label["render_now"]="Ver ahora";
$lang_label["ntemplates"]="Plantillas Red";
$lang_label["setup_agent"]="Configurar agente";

global $lang_label;
global $help_label;
?>
