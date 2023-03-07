START TRANSACTION;

UPDATE tconfig_os SET `icon_name` = 'linux@os.svg' WHERE `id_os` = 1;
UPDATE tconfig_os SET `icon_name` = 'solaris@os.svg' WHERE `id_os` = 2;
UPDATE tconfig_os SET `icon_name` = 'aix@os.svg' WHERE `id_os` = 3;
UPDATE tconfig_os SET `icon_name` = 'freebsd@os.svg' WHERE `id_os` = 4;
UPDATE tconfig_os SET `icon_name` = 'HP@os.svg' WHERE `id_os` = 5;
UPDATE tconfig_os SET `icon_name` = 'cisco@os.svg' WHERE `id_os` = 7;
UPDATE tconfig_os SET `icon_name` = 'apple@os.svg' WHERE `id_os` = 8;
UPDATE tconfig_os SET `icon_name` = 'windows@os.svg' WHERE `id_os` = 9;
UPDATE tconfig_os SET `icon_name` = 'other-OS@os.svg' WHERE `id_os` = 10;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 11;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 12;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 13;
UPDATE tconfig_os SET `icon_name` = 'embedded@os.svg' WHERE `id_os` = 14;
UPDATE tconfig_os SET `icon_name` = 'android@os.svg' WHERE `id_os` = 15;
UPDATE tconfig_os SET `icon_name` = 'vmware@os.svg' WHERE `id_os` = 16;
UPDATE tconfig_os SET `icon_name` = 'routers@os.svg' WHERE `id_os` = 17;
UPDATE tconfig_os SET `icon_name` = 'switch@os.svg' WHERE `id_os` = 18;
UPDATE tconfig_os SET `icon_name` = 'satellite@os.svg' WHERE `id_os` = 19;
UPDATE tconfig_os SET `icon_name` = 'mainframe@os.svg' WHERE `id_os` = 20;
UPDATE tconfig_os SET `icon_name` = 'cluster@os.svg' WHERE `id_os` = 100;

UPDATE tgrupo SET `icon` = 'servers@groups.svg' WHERE `id_grupo` = 2;
UPDATE tgrupo SET `icon` = 'firewall@groups.svg' WHERE `id_grupo` = 4;
UPDATE tgrupo SET `icon` = 'database@groups.svg' WHERE `id_grupo` = 8;
UPDATE tgrupo SET `icon` = 'network@groups.svg' WHERE `id_grupo` = 9;
UPDATE tgrupo SET `icon` = 'unknown@groups.svg' WHERE `id_grupo` = 10;
UPDATE tgrupo SET `icon` = 'workstation@groups.svg' WHERE `id_grupo` = 11;
UPDATE tgrupo SET `icon` = 'applications@groups.svg' WHERE `id_grupo` = 12;
UPDATE tgrupo SET `icon` = 'web@groups.svg' WHERE `id_grupo` = 13;

UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 1;
UPDATE `ttipo_modulo` SET `icon` = 'generic-boolean@svg.svg' WHERE `id_tipo` = 2;
UPDATE `ttipo_modulo` SET `icon` = 'generic-string@svg.svg' WHERE `id_tipo` = 3;
UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 4;
UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 5;
UPDATE `ttipo_modulo` SET `icon` = 'ICMP-network-boolean-data@svg.svg' WHERE `id_tipo` = 6;
UPDATE `ttipo_modulo` SET `icon` = 'ICMP-network-latency@svg.svg' WHERE `id_tipo` = 7;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-numeric-data@svg.svg' WHERE `id_tipo` = 8;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-boolean-data@svg.svg' WHERE `id_tipo` = 9;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-alphanumeric-data@svg.svg' WHERE `id_tipo` = 10;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-incremental-data@svg.svg' WHERE `id_tipo` = 11;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-numeric-data@svg.svg' WHERE `id_tipo` = 15;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-incremental-data@svg.svg' WHERE `id_tipo` = 16;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-alphanumeric-data@svg.svg' WHERE `id_tipo` = 17;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-incremental-data@svg.svg' WHERE `id_tipo` = 18;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 21;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 22;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 23;
UPDATE `ttipo_modulo` SET `icon` = 'wux@svg.svg' WHERE `id_tipo` = 25;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 30;
UPDATE `ttipo_modulo` SET `icon` = 'web-analisys-data@svg.svg' WHERE `id_tipo` = 31;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 32;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 33;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-numeric-data@svg.svg' WHERE `id_tipo` = 34;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-boolean-data@svg.svg' WHERE `id_tipo` = 35;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-alphanumeric-data@svg.svg' WHERE `id_tipo` = 36;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-incremental-data@svg.svg' WHERE `id_tipo` = 37;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 38;
UPDATE `ttipo_modulo` SET `icon` = 'keepalive@svg.svg' WHERE `id_tipo` = 100;

CREATE TABLE IF NOT EXISTS `tagent_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `group_id` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `search` TEXT,
  `id_os` INT NOT NULL DEFAULT 0,
  `policies` TEXT,
  `search_custom` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE `tevent_sound` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `sound` TEXT NULL,
    `active` TINYINT NOT NULL DEFAULT '1',
PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX agente_modulo_estado ON tevento (estado, id_agentmodule);
CREATE INDEX idx_disabled ON talert_template_modules (disabled);

INSERT INTO `treport_custom_sql` (`name`, `sql`) VALUES ('Agent&#x20;safe&#x20;mode&#x20;not&#x20;enable', 'select&#x20;alias&#x20;from&#x20;tagente&#x20;where&#x20;safe_mode_module&#x20;=&#x20;0');

CREATE TABLE IF NOT EXISTS `twelcome_tip` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_lang` VARCHAR(20) NULL,
  `id_profile` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `text` TEXT NOT NULL,
  `url` VARCHAR(255) NULL,
  `enable` TINYINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `twelcome_tip_file` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `twelcome_tip_file` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `twelcome_tip_file`
    FOREIGN KEY (`twelcome_tip_file`)
    REFERENCES `twelcome_tip` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

INSERT INTO `twelcome_tip` VALUES
(1,  'es', 0, '¿Sabías que puedes monitorizar webs?', 'De manera sencilla a través de chequeos HTTP estándar o transaccional mediante transacciones centralizadas WUX, o descentralizadas con el plugin UX de agente.', 'https://pandorafms.com/manual/es/documentation/03_monitoring/06_web_monitoring', '1'),
(2,  'es', 0, 'Monitorización remota de dispositivos SNMP', 'Los dispositivos de red como switches, AP, routers y firewalls se pueden monitorizar remotamente usando el protocolo SNMP. Basta con saber su IP, la comunidad SNMP y lanzar un wizard SNMP desde la consola.', 'https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_snmp', '1'),
(3,  'es', 0, 'Monitorizar rutas desde una IP a otra', 'Existe un plugin especial que sirve para monitorizar visualmente las rutas desde una IP a otra de manera visual y dinámica, según va cambiando con el tiempo.', 'https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_de_rutas', '1'),
(4,  'es', 0, '¿Tu red pierde paquetes?', 'Se puede medir la pérdida de paquetes en tu red usando un agente y un plugin libre llamado “Packet Loss”. Esto es especialmente útil en redes Wifi o redes compartidas con muchos usuarios. Escribimos un artículo en nuestro blog hablando de ello, echale un vistazo', 'https://pandorafms.com/blog/es/perdida-de-paquetes/', '1'),
(5,  'es', 0, 'Usar Telegram con Pandora FMS', 'Perfecto para recibir alertas con gráficas empotradas y personalizar así la recepción de avisos de manera individual o en un canal común con mas personas. ', 'https://pandorafms.com/library/telegram-bot-cli/', '1'),
(6,  'es', 0, 'Monitorizar JMX (Tomcat, Websphere, Weblogic, Jboss, Apache Kafka, Jetty, GlassFish…)', 'Existe un plugin Enterprise que sirve para monitorizar cualquier tecnología JMX. Se puede usar de manera local (como plugin local) o de manera remota con el plugin server.', 'https://pandorafms.com/library/jmx-monitoring/', '1'),
(7,  'es', 0, '¿Sabes que cada usuario puede tener su propia Zona Horaria?', 'Se puede establecer zonas horarias diferentes para cada usuario, de manera que interprete los datos teniendo en cuenta la diferencia horaria. Pandora FMS también puede tener servidores y agentes en diferentes zonas horarias. ¡Por todo el mundo!', '', '1'),
(8,  'es', 0, 'Paradas planificadas', 'Se puede definir, a nivel de agente y a nivel de módulo, períodos en los cuales se ignoren las alertas y/o los datos recogidos. Es perfecto para planificar paradas de servicio o desconexión de los sistemas monitorizados. También afecta a los informes SLA, evitando que se tengan en cuenta esos intervalos de tiempo.    ', 'https://pandorafms.com/manual/es/documentation/04_using/11_managing_and_administration%23paradas_de_servicio_planificadas&sa=D&source=editors&ust=1676638674480651&usg=AOvVaw1BmHf4fVOaJQJHwuO1rMNO', '1'),
(9,  'es', 0, 'Personalizar los emails de alerta ', '¿Sabías que se pueden personalizar los mails de alertas de Pandora? Solo tienes que editar el código HTML por defecto de las acciones de alerta de tipo email.  ', 'https://pandorafms.com/manual/en/documentation/04_using/01_alerts%23editing_an_action&sa=D&source=editors&ust=1676638674481790&usg=AOvVaw3z5Lw49GQ8KFtlQHR11vph', '1'),
(10,  'es', 0, 'Usando iconos personalizados en consolas visuales ', 'Gracias a los iconos personalizados se pueden crear vistas muy personalizadas, como la de la imagen, que representa racks con los tipos de servidores en el orden que están colocados dentro del rack. Perfecto para que un técnico sepa exactamente qué máquina esta fallando. Más visual no puede ser, de ahi el nombre.  ', 'https://pandorafms.com/manual/start?id%3Des/documentation/04_using/05_data_presentation_visual_maps&sa=D&source=editors&ust=1676638674483113&usg=AOvVaw06ylqbW4fZP3MQ1pToOoPz', '1'),
(11,  'es', 0, 'Consolas visuales: mapas de calor ', 'La consola permite integrar en un fondo personalizado una serie de datos, que en función de su valor se representen con unos colores u otros, en tiempo real. Las aplicaciones son infinitas, solo depende de tu imaginación.   ', 'https://pandorafms.com/manual/es/documentation/04_using/05_data_presentation_visual_maps%23mapa_de_calor_o_nube_de_color&sa=D&source=editors&ust=1676638674484261&usg=AOvVaw0Rpv60CbSLAQ4gw1gHQf0P', '1'),
(12,  'es', 0, 'Auditoría interna de la consola ', 'La consola registra todas las actividades relevantes de cada usuario conectado a la consola. Esto incluye la aplicación de configuraciones, validaciones de eventos y alertas, conexión y desconexión y cientos de otras operaciones. La seguridad en Pandora FMS ha sido siempre una de las características del diseño de su arquitectura.  ', 'https://pandorafms.com/manual/es/documentation/04_using/11_managing_and_administration%23log_de_auditoria&sa=D&source=editors&ust=1676638674485278&usg=AOvVaw1ogY55xIHuik8w-96E6od_', '1'),
(13,  'es', 0, 'Sistema de provisión automática de agentes ', 'El sistema de autoprovisión de agentes, permite que un agente recién ingresado en el sistema aplique automáticamente cambios en su configuración (como moverlo de grupo, asignarle ciertos valores en campos personalizados) y por supuesto aplicarle determinadas politicas de monitorización. Es una de las funcionalidades más potentes, orientadas a gestionar parques de sistemas muy extensos.  ', 'https://pandorafms.com/manual/start?id%3Des/documentation/02_installation/05_configuration_agents%23configuracion_automatica_de_agentes&sa=D&source=editors&ust=1676638674486118&usg=AOvVaw1t6SHcIPB9JP4iPC1c0--6', '1'),
(14,  'es', 0, 'Modo oscuro ', '¿Sabes que existe un modo oscuro en Pandora FMS? Un administrador lo puede activar a nivel global desde las opciones de configuración visuales o cualquier usuario a nivel individual, en las opciones de usuario. ', '', '1'),
(15,  'es', 0, 'Google Sheet ', '¿Sabes que se puede coger el valor de una celda de una hoja de cálculo de Google Sheet?, utilizamos la API para pedir el dato a través de un plugin remoto. Es perfecto para construir cuadros de mando de negocio, obtener alertas en tiempo real y crear tus propios informes a medida.  ', 'https://pandorafms.com/library/google-sheets-plugin/&sa=D&source=editors&ust=1676638674487428&usg=AOvVaw0aX-6JauDZiERCuux3ykbB', '1'),
(16,  'es', 0, 'Tablas de ARP', '¿Sabes que existe un módulo de inventario para sacar las tablas ARP de tus servidores windows? Es fácil de instalar y puede darte información muy detallada de tus equipos.', 'https://pandorafms.com/library/arp-table-windows-local/&sa=D&source=editors&ust=1676638674488086&usg=AOvVaw11Y88pIzllG8GLKFjCr_Nd', '1'),
(17,  'es', 0, 'Enlaces de red en la consola visual ', 'Existe un elemento de consola visual llamado “Network link” que permite mostrar visualmente la unión de dos interfaces de red, su estado y el tráfico de subida/bajada, de una manera muy visual.  ', 'https://pandorafms.com/manual/es/documentation/04_using/05_data_presentation_visual_maps%23enlace_de_red&sa=D&source=editors&ust=1676638674489181&usg=AOvVaw1xXPgSPbKL1OX7T7BPVFzw', '1'),
(18,  'es', 0, '¿Conoces los informes de disponibilidad? ', 'Son muy útiles ya que te dicen el tiempo (%) que un chequeo ha estado en diferentes estados a lo largo de un lapso de tiempo, por ejemplo, una semana. Ofrece datos crudos completos de lo que se ha hecho con el detalle suficiente para convencer a un proveedor o un cliente.  ', '', '1'),
(19,  'es', 0, 'Gráficas de disponibilidad ', 'Parecidos a los informes de disponibilidad, pero mucho mas visuales, ofrecen el detalle de estado de un monitor a lo largo del tiempo. Se pueden agrupar con otro módulo para ofrecer datos finales teniendo en cuenta la alta disponibilidad de un servicio. Son perfectos para su uso en informes a proveedores y/o clientes.  ', 'https://pandorafms.com/manual/es/documentation/04_using/08_data_presentation_reports%23grafico_de_disponibilidad&sa=D&source=editors&ust=1676638674490944&usg=AOvVaw3KB58Q9eCoB_Dw3zc5qkDx', '1'),
(20,  'es', 0, 'Zoom en gráficas de datos ', '¿Sabes que Pandora FMS permite hacer zoom en una parte de la gráfica. Con eso ampliarás la información de la gráfica. Si estás viendo una gráfica de un mes y amplías, podrás ver los datos de ese intervalo. Si utilizas una gráfica con datos de resolución completa (los llamamos gráficas TIP) podrás ver el detalle de cada dato, aunque tu gráfica tenga miles de muestras.  ', '', '1'),
(21,  'es', 0, 'Gráficas de resolución completa ', 'Pandora FMS y otras herramientas cuando tienen que mostrar una gráfica obtienen los datos de la fuente de datos y luego “simplifican” la gráfica, ya que si la serie de datos tiene 10,000 elementos y la gráfica solo tiene 300 pixeles de ancho no pueden caber todos, asi que se “simplifican” esos 10,000 puntos en solo 300.   Sin embargo al simplificar se pierde “detalle” en la gráfica, y por supuesto no podemos “hacer zoom”. Las gráficas de Pandora FMS permiten mostrar y usar todos los datos en una gráfica, que llamamos “TIP” que muestra todos los puntos superpuestos y además permite que al hacer zoom no se pierda resolución.   ', '', '1'),
(22,  'es', 0, 'Política de contraseñas', 'La consola de Pandora FMS tiene un sistema de gestión de política de credenciales, para reforzar la seguridad local (además de permitir la autenticación externa contra un LDAP, Active Directory o SAML). A través de este sistema podemos forzar cambios de password cada X días, guardar un histórico de passwords usadas o evitar el uso de ciertas contraseñas entre otras acciones.  ', 'https://pandorafms.com/manual/es/documentation/04_using/12_console_setup?s%5B%5D%3Dcontrase%25C3%25B1as%23password_policy&sa=D&source=editors&ust=1676638674493801&usg=AOvVaw2elhhahAZZW0jNTGb92co6', '1'),
(23,  'es', 0, 'Autenticación de doble factor ', 'Es posible activar (y forzar su uso a todos los usuarios) un sistema de doble autenticación (usando Google Auth) para que cualquier usuario se autentique además de con una contraseña, con un sistema de token de un solo uso, dando al sistema mucha más seguridad.  ', 'https://pandorafms.com/manual/en/documentation/04_using/12_console_setup?s%5B%5D%3Dgoogle%26s%5B%5D%3Dauth%23authentication&sa=D&source=editors&ust=1676638674495119&usg=AOvVaw0MF0XAyBfKulQQ5ZwtMUXA', '1');

INSERT INTO `twelcome_tip_file` (`twelcome_tip_file`, `filename`, `path`) VALUES
(1, 'monitorizar_web.png', 'images/tips/'),
(2, 'monitorizar_snmp.png', 'images/tips/'),
(3, 'monitorizar_desde_ip.png', 'images/tips/'),
(4, 'tu_red_pierde_paquetes.png', 'images/tips/'),
(5, 'telegram_con_pandora.png', 'images/tips/'),
(6, 'monitorizar_con_jmx.png', 'images/tips/'),
(7, 'usuario_zona_horaria.png', 'images/tips/'),
(8, 'paradas_planificadas.png', 'images/tips/'),
(9, 'personalizar_los_emails.png', 'images/tips/'),
(10, 'iconos_personalizados.png', 'images/tips/'),
(11, 'mapa_de_calor.png', 'images/tips/'),
(12, 'auditoria.png', 'images/tips/'),
(15, 'google_sheets.png', 'images/tips/'),
(17, 'enlaces_consola_visual.png', 'images/tips/'),
(18, 'informe_disponibiliad.png', 'images/tips/'),
(19, 'graficas_disponibilidad.png', 'images/tips/'),
(20, 'zoom_en_graficas.png', 'images/tips/'),
(22, 'politica_de_pass.png', 'images/tips/');

COMMIT;
