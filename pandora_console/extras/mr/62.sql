START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tconsole` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_console` BIGINT NOT NULL DEFAULT 0,
  `description` TEXT,
  `version` TINYTEXT,
  `last_execution` INT UNSIGNED NOT NULL DEFAULT 0,
  `console_type` TINYINT NOT NULL DEFAULT 0,
  `timezone` TINYTEXT,
  `public_url` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tuser_task_scheduled` ADD COLUMN `id_console` BIGINT NOT NULL DEFAULT 0;

ALTER TABLE `tdatabase` ADD COLUMN `ssh_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `db_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `replication_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `replication_delay` BIGINT DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `master` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `utimestamp` BIGINT DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `mysql_version` VARCHAR(10) DEFAULT '';
ALTER TABLE `tdatabase` ADD COLUMN `pandora_version` VARCHAR(10) DEFAULT '';

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
(1,'es',0,'¿Sab&iacute;as&#x20;que&#x20;puedes&#x20;monitorizar&#x20;webs?','De&#x20;manera&#x20;sencilla&#x20;a&#x20;trav&eacute;s&#x20;de&#x20;chequeos&#x20;HTTP&#x20;est&aacute;ndar&#x20;o&#x20;transaccional&#x20;mediante&#x20;transacciones&#x20;centralizadas&#x20;WUX,&#x20;o&#x20;descentralizadas&#x20;con&#x20;el&#x20;plugin&#x20;UX&#x20;de&#x20;agente.','https://pandorafms.com/manual/es/documentation/03_monitoring/06_web_monitoring','1'),
(2,'es',0,'Monitorizaci&oacute;n&#x20;remota&#x20;de&#x20;dispositivos&#x20;SNMP','Los&#x20;dispositivos&#x20;de&#x20;red&#x20;como&#x20;switches,&#x20;AP,&#x20;routers&#x20;y&#x20;firewalls&#x20;se&#x20;pueden&#x20;monitorizar&#x20;remotamente&#x20;usando&#x20;el&#x20;protocolo&#x20;SNMP.&#x20;Basta&#x20;con&#x20;saber&#x20;su&#x20;IP,&#x20;la&#x20;comunidad&#x20;SNMP&#x20;y&#x20;lanzar&#x20;un&#x20;wizard&#x20;SNMP&#x20;desde&#x20;la&#x20;consola.','https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_snmp','1'),
(3,'es',0,'Monitorizar&#x20;rutas&#x20;desde&#x20;una&#x20;IP&#x20;a&#x20;otra','Existe&#x20;un&#x20;plugin&#x20;especial&#x20;que&#x20;sirve&#x20;para&#x20;monitorizar&#x20;visualmente&#x20;las&#x20;rutas&#x20;desde&#x20;una&#x20;IP&#x20;a&#x20;otra&#x20;de&#x20;manera&#x20;visual&#x20;y&#x20;din&aacute;mica,&#x20;seg&uacute;n&#x20;va&#x20;cambiando&#x20;con&#x20;el&#x20;tiempo.','https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_de_rutas','1'),
(4,'es',0,'¿Tu&#x20;red&#x20;pierde&#x20;paquetes?','Se&#x20;puede&#x20;medir&#x20;la&#x20;p&eacute;rdida&#x20;de&#x20;paquetes&#x20;en&#x20;tu&#x20;red&#x20;usando&#x20;un&#x20;agente&#x20;y&#x20;un&#x20;plugin&#x20;libre&#x20;llamado&#x20;&ldquo;Packet&#x20;Loss&rdquo;.&#x20;Esto&#x20;es&#x20;especialmente&#x20;&uacute;til&#x20;en&#x20;redes&#x20;Wifi&#x20;o&#x20;redes&#x20;compartidas&#x20;con&#x20;muchos&#x20;usuarios.&#x20;Escribimos&#x20;un&#x20;art&iacute;culo&#x20;en&#x20;nuestro&#x20;blog&#x20;hablando&#x20;de&#x20;ello,&#x20;echale&#x20;un&#x20;vistazo','https://pandorafms.com/blog/es/perdida-de-paquetes/','1'),
(5,'es',0,'Usar&#x20;Telegram&#x20;con&#x20;Pandora&#x20;FMS','Perfecto&#x20;para&#x20;recibir&#x20;alertas&#x20;con&#x20;gr&aacute;ficas&#x20;empotradas&#x20;y&#x20;personalizar&#x20;as&iacute;&#x20;la&#x20;recepci&oacute;n&#x20;de&#x20;avisos&#x20;de&#x20;manera&#x20;individual&#x20;o&#x20;en&#x20;un&#x20;canal&#x20;com&uacute;n&#x20;con&#x20;mas&#x20;personas.&#x20;','https://pandorafms.com/library/telegram-bot-cli/','1'),
(6,'es',0,'Monitorizar&#x20;JMX&#x20;&#40;Tomcat,&#x20;Websphere,&#x20;Weblogic,&#x20;Jboss,&#x20;Apache&#x20;Kafka,&#x20;Jetty,&#x20;GlassFish&hellip;&#41;','Existe&#x20;un&#x20;plugin&#x20;Enterprise&#x20;que&#x20;sirve&#x20;para&#x20;monitorizar&#x20;cualquier&#x20;tecnolog&iacute;a&#x20;JMX.&#x20;Se&#x20;puede&#x20;usar&#x20;de&#x20;manera&#x20;local&#x20;&#40;como&#x20;plugin&#x20;local&#41;&#x20;o&#x20;de&#x20;manera&#x20;remota&#x20;con&#x20;el&#x20;plugin&#x20;server.','https://pandorafms.com/library/jmx-monitoring/','1'),
(7,'es',0,'¿Sabes&#x20;que&#x20;cada&#x20;usuario&#x20;puede&#x20;tener&#x20;su&#x20;propia&#x20;Zona&#x20;Horaria?','Se&#x20;puede&#x20;establecer&#x20;zonas&#x20;horarias&#x20;diferentes&#x20;para&#x20;cada&#x20;usuario,&#x20;de&#x20;manera&#x20;que&#x20;interprete&#x20;los&#x20;datos&#x20;teniendo&#x20;en&#x20;cuenta&#x20;la&#x20;diferencia&#x20;horaria.&#x20;Pandora&#x20;FMS&#x20;tambi&eacute;n&#x20;puede&#x20;tener&#x20;servidores&#x20;y&#x20;agentes&#x20;en&#x20;diferentes&#x20;zonas&#x20;horarias.&#x20;¡Por&#x20;todo&#x20;el&#x20;mundo!','','1'),
(8,'es',0,'Paradas&#x20;planificadas','Se&#x20;puede&#x20;definir,&#x20;a&#x20;nivel&#x20;de&#x20;agente&#x20;y&#x20;a&#x20;nivel&#x20;de&#x20;m&oacute;dulo,&#x20;per&iacute;odos&#x20;en&#x20;los&#x20;cuales&#x20;se&#x20;ignoren&#x20;las&#x20;alertas&#x20;y/o&#x20;los&#x20;datos&#x20;recogidos.&#x20;Es&#x20;perfecto&#x20;para&#x20;planificar&#x20;paradas&#x20;de&#x20;servicio&#x20;o&#x20;desconexi&oacute;n&#x20;de&#x20;los&#x20;sistemas&#x20;monitorizados.&#x20;Tambi&eacute;n&#x20;afecta&#x20;a&#x20;los&#x20;informes&#x20;SLA,&#x20;evitando&#x20;que&#x20;se&#x20;tengan&#x20;en&#x20;cuenta&#x20;esos&#x20;intervalos&#x20;de&#x20;tiempo.&#x20;&#x20;&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/11_managing_and_administration#paradas_de_servicio_planificadas','1'),
(9,'es',0,'Personalizar&#x20;los&#x20;emails&#x20;de&#x20;alerta&#x20;','¿Sab&iacute;as&#x20;que&#x20;se&#x20;pueden&#x20;personalizar&#x20;los&#x20;mails&#x20;de&#x20;alertas&#x20;de&#x20;Pandora?&#x20;Solo&#x20;tienes&#x20;que&#x20;editar&#x20;el&#x20;c&oacute;digo&#x20;HTML&#x20;por&#x20;defecto&#x20;de&#x20;las&#x20;acciones&#x20;de&#x20;alerta&#x20;de&#x20;tipo&#x20;email.&#x20;&#x20;','https://pandorafms.com/manual/en/documentation/04_using/01_alerts#editing_an_action','1'),
(10,'es',0,'Usando&#x20;iconos&#x20;personalizados&#x20;en&#x20;consolas&#x20;visuales&#x20;','Gracias&#x20;a&#x20;los&#x20;iconos&#x20;personalizados&#x20;se&#x20;pueden&#x20;crear&#x20;vistas&#x20;muy&#x20;personalizadas,&#x20;como&#x20;la&#x20;de&#x20;la&#x20;imagen,&#x20;que&#x20;representa&#x20;racks&#x20;con&#x20;los&#x20;tipos&#x20;de&#x20;servidores&#x20;en&#x20;el&#x20;orden&#x20;que&#x20;est&aacute;n&#x20;colocados&#x20;dentro&#x20;del&#x20;rack.&#x20;Perfecto&#x20;para&#x20;que&#x20;un&#x20;t&eacute;cnico&#x20;sepa&#x20;exactamente&#x20;qu&eacute;&#x20;m&aacute;quina&#x20;esta&#x20;fallando.&#x20;M&aacute;s&#x20;visual&#x20;no&#x20;puede&#x20;ser,&#x20;de&#x20;ahi&#x20;el&#x20;nombre.&#x20;&#x20;','https://pandorafms.com/manual/start?id=es/documentation/04_using/05_data_presentation_visual_maps','1'),
(11,'es',0,'Consolas&#x20;visuales:&#x20;mapas&#x20;de&#x20;calor&#x20;','La&#x20;consola&#x20;permite&#x20;integrar&#x20;en&#x20;un&#x20;fondo&#x20;personalizado&#x20;una&#x20;serie&#x20;de&#x20;datos,&#x20;que&#x20;en&#x20;funci&oacute;n&#x20;de&#x20;su&#x20;valor&#x20;se&#x20;representen&#x20;con&#x20;unos&#x20;colores&#x20;u&#x20;otros,&#x20;en&#x20;tiempo&#x20;real.&#x20;Las&#x20;aplicaciones&#x20;son&#x20;infinitas,&#x20;solo&#x20;depende&#x20;de&#x20;tu&#x20;imaginaci&oacute;n.&#x20;&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/05_data_presentation_visual_maps#mapa_de_calor_o_nube_de_color','1'),
(12,'es',0,'Auditor&iacute;a&#x20;interna&#x20;de&#x20;la&#x20;consola&#x20;','La&#x20;consola&#x20;registra&#x20;todas&#x20;las&#x20;actividades&#x20;relevantes&#x20;de&#x20;cada&#x20;usuario&#x20;conectado&#x20;a&#x20;la&#x20;consola.&#x20;Esto&#x20;incluye&#x20;la&#x20;aplicaci&oacute;n&#x20;de&#x20;configuraciones,&#x20;validaciones&#x20;de&#x20;eventos&#x20;y&#x20;alertas,&#x20;conexi&oacute;n&#x20;y&#x20;desconexi&oacute;n&#x20;y&#x20;cientos&#x20;de&#x20;otras&#x20;operaciones.&#x20;La&#x20;seguridad&#x20;en&#x20;Pandora&#x20;FMS&#x20;ha&#x20;sido&#x20;siempre&#x20;una&#x20;de&#x20;las&#x20;caracter&iacute;sticas&#x20;del&#x20;dise&ntilde;o&#x20;de&#x20;su&#x20;arquitectura.&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/11_managing_and_administration#log_de_auditoria','1'),
(13,'es',0,'Sistema&#x20;de&#x20;provisi&oacute;n&#x20;autom&aacute;tica&#x20;de&#x20;agentes&#x20;','El&#x20;sistema&#x20;de&#x20;autoprovisi&oacute;n&#x20;de&#x20;agentes,&#x20;permite&#x20;que&#x20;un&#x20;agente&#x20;reci&eacute;n&#x20;ingresado&#x20;en&#x20;el&#x20;sistema&#x20;aplique&#x20;autom&aacute;ticamente&#x20;cambios&#x20;en&#x20;su&#x20;configuraci&oacute;n&#x20;&#40;como&#x20;moverlo&#x20;de&#x20;grupo,&#x20;asignarle&#x20;ciertos&#x20;valores&#x20;en&#x20;campos&#x20;personalizados&#41;&#x20;y&#x20;por&#x20;supuesto&#x20;aplicarle&#x20;determinadas&#x20;politicas&#x20;de&#x20;monitorizaci&oacute;n.&#x20;Es&#x20;una&#x20;de&#x20;las&#x20;funcionalidades&#x20;m&aacute;s&#x20;potentes,&#x20;orientadas&#x20;a&#x20;gestionar&#x20;parques&#x20;de&#x20;sistemas&#x20;muy&#x20;extensos.&#x20;&#x20;','https://pandorafms.com/manual/start?id=es/documentation/02_installation/05_configuration_agents#configuracion_automatica_de_agentes','1'),
(14,'es',0,'Modo&#x20;oscuro&#x20;','¿Sabes&#x20;que&#x20;existe&#x20;un&#x20;modo&#x20;oscuro&#x20;en&#x20;Pandora&#x20;FMS?&#x20;Un&#x20;administrador&#x20;lo&#x20;puede&#x20;activar&#x20;a&#x20;nivel&#x20;global&#x20;desde&#x20;las&#x20;opciones&#x20;de&#x20;configuraci&oacute;n&#x20;visuales&#x20;o&#x20;cualquier&#x20;usuario&#x20;a&#x20;nivel&#x20;individual,&#x20;en&#x20;las&#x20;opciones&#x20;de&#x20;usuario.&#x20;','','1'),
(15,'es',0,'Google&#x20;Sheet&#x20;','¿Sabes&#x20;que&#x20;se&#x20;puede&#x20;coger&#x20;el&#x20;valor&#x20;de&#x20;una&#x20;celda&#x20;de&#x20;una&#x20;hoja&#x20;de&#x20;c&aacute;lculo&#x20;de&#x20;Google&#x20;Sheet?,&#x20;utilizamos&#x20;la&#x20;API&#x20;para&#x20;pedir&#x20;el&#x20;dato&#x20;a&#x20;trav&eacute;s&#x20;de&#x20;un&#x20;plugin&#x20;remoto.&#x20;Es&#x20;perfecto&#x20;para&#x20;construir&#x20;cuadros&#x20;de&#x20;mando&#x20;de&#x20;negocio,&#x20;obtener&#x20;alertas&#x20;en&#x20;tiempo&#x20;real&#x20;y&#x20;crear&#x20;tus&#x20;propios&#x20;informes&#x20;a&#x20;medida.&#x20;&#x20;','https://pandorafms.com/library/google-sheets-plugin/','1'),
(16,'es',0,'Tablas&#x20;de&#x20;ARP','¿Sabes&#x20;que&#x20;existe&#x20;un&#x20;m&oacute;dulo&#x20;de&#x20;inventario&#x20;para&#x20;sacar&#x20;las&#x20;tablas&#x20;ARP&#x20;de&#x20;tus&#x20;servidores&#x20;windows?&#x20;Es&#x20;f&aacute;cil&#x20;de&#x20;instalar&#x20;y&#x20;puede&#x20;darte&#x20;informaci&oacute;n&#x20;muy&#x20;detallada&#x20;de&#x20;tus&#x20;equipos.','https://pandorafms.com/library/arp-table-windows-local/','1'),
(17,'es',0,'Enlaces&#x20;de&#x20;red&#x20;en&#x20;la&#x20;consola&#x20;visual&#x20;','Existe&#x20;un&#x20;elemento&#x20;de&#x20;consola&#x20;visual&#x20;llamado&#x20;&ldquo;Network&#x20;link&rdquo;&#x20;que&#x20;permite&#x20;mostrar&#x20;visualmente&#x20;la&#x20;uni&oacute;n&#x20;de&#x20;dos&#x20;interfaces&#x20;de&#x20;red,&#x20;su&#x20;estado&#x20;y&#x20;el&#x20;tr&aacute;fico&#x20;de&#x20;subida/bajada,&#x20;de&#x20;una&#x20;manera&#x20;muy&#x20;visual.&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/05_data_presentation_visual_maps#enlace_de_red','1'),
(18,'es',0,'¿Conoces&#x20;los&#x20;informes&#x20;de&#x20;disponibilidad?&#x20;','Son&#x20;muy&#x20;&uacute;tiles&#x20;ya&#x20;que&#x20;te&#x20;dicen&#x20;el&#x20;tiempo&#x20;&#40;%&#41;&#x20;que&#x20;un&#x20;chequeo&#x20;ha&#x20;estado&#x20;en&#x20;diferentes&#x20;estados&#x20;a&#x20;lo&#x20;largo&#x20;de&#x20;un&#x20;lapso&#x20;de&#x20;tiempo,&#x20;por&#x20;ejemplo,&#x20;una&#x20;semana.&#x20;Ofrece&#x20;datos&#x20;crudos&#x20;completos&#x20;de&#x20;lo&#x20;que&#x20;se&#x20;ha&#x20;hecho&#x20;con&#x20;el&#x20;detalle&#x20;suficiente&#x20;para&#x20;convencer&#x20;a&#x20;un&#x20;proveedor&#x20;o&#x20;un&#x20;cliente.&#x20;&#x20;','','1'),
(19,'es',0,'Gr&aacute;ficas&#x20;de&#x20;disponibilidad&#x20;','Parecidos&#x20;a&#x20;los&#x20;informes&#x20;de&#x20;disponibilidad,&#x20;pero&#x20;mucho&#x20;mas&#x20;visuales,&#x20;ofrecen&#x20;el&#x20;detalle&#x20;de&#x20;estado&#x20;de&#x20;un&#x20;monitor&#x20;a&#x20;lo&#x20;largo&#x20;del&#x20;tiempo.&#x20;Se&#x20;pueden&#x20;agrupar&#x20;con&#x20;otro&#x20;m&oacute;dulo&#x20;para&#x20;ofrecer&#x20;datos&#x20;finales&#x20;teniendo&#x20;en&#x20;cuenta&#x20;la&#x20;alta&#x20;disponibilidad&#x20;de&#x20;un&#x20;servicio.&#x20;Son&#x20;perfectos&#x20;para&#x20;su&#x20;uso&#x20;en&#x20;informes&#x20;a&#x20;proveedores&#x20;y/o&#x20;clientes.&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/08_data_presentation_reports#grafico_de_disponibilidad','1'),
(20,'es',0,'Zoom&#x20;en&#x20;gr&aacute;ficas&#x20;de&#x20;datos&#x20;','¿Sabes&#x20;que&#x20;Pandora&#x20;FMS&#x20;permite&#x20;hacer&#x20;zoom&#x20;en&#x20;una&#x20;parte&#x20;de&#x20;la&#x20;gr&aacute;fica.&#x20;Con&#x20;eso&#x20;ampliar&aacute;s&#x20;la&#x20;informaci&oacute;n&#x20;de&#x20;la&#x20;gr&aacute;fica.&#x20;Si&#x20;est&aacute;s&#x20;viendo&#x20;una&#x20;gr&aacute;fica&#x20;de&#x20;un&#x20;mes&#x20;y&#x20;ampl&iacute;as,&#x20;podr&aacute;s&#x20;ver&#x20;los&#x20;datos&#x20;de&#x20;ese&#x20;intervalo.&#x20;Si&#x20;utilizas&#x20;una&#x20;gr&aacute;fica&#x20;con&#x20;datos&#x20;de&#x20;resoluci&oacute;n&#x20;completa&#x20;&#40;los&#x20;llamamos&#x20;gr&aacute;ficas&#x20;TIP&#41;&#x20;podr&aacute;s&#x20;ver&#x20;el&#x20;detalle&#x20;de&#x20;cada&#x20;dato,&#x20;aunque&#x20;tu&#x20;gr&aacute;fica&#x20;tenga&#x20;miles&#x20;de&#x20;muestras.&#x20;&#x20;','','1'),
(21,'es',0,'Gr&aacute;ficas&#x20;de&#x20;resoluci&oacute;n&#x20;completa&#x20;','Pandora&#x20;FMS&#x20;y&#x20;otras&#x20;herramientas&#x20;cuando&#x20;tienen&#x20;que&#x20;mostrar&#x20;una&#x20;gr&aacute;fica&#x20;obtienen&#x20;los&#x20;datos&#x20;de&#x20;la&#x20;fuente&#x20;de&#x20;datos&#x20;y&#x20;luego&#x20;&ldquo;simplifican&rdquo;&#x20;la&#x20;gr&aacute;fica,&#x20;ya&#x20;que&#x20;si&#x20;la&#x20;serie&#x20;de&#x20;datos&#x20;tiene&#x20;10,000&#x20;elementos&#x20;y&#x20;la&#x20;gr&aacute;fica&#x20;solo&#x20;tiene&#x20;300&#x20;pixeles&#x20;de&#x20;ancho&#x20;no&#x20;pueden&#x20;caber&#x20;todos,&#x20;asi&#x20;que&#x20;se&#x20;&ldquo;simplifican&rdquo;&#x20;esos&#x20;10,000&#x20;puntos&#x20;en&#x20;solo&#x20;300.&#x20;&#x20;&#x20;Sin&#x20;embargo&#x20;al&#x20;simplificar&#x20;se&#x20;pierde&#x20;&ldquo;detalle&rdquo;&#x20;en&#x20;la&#x20;gr&aacute;fica,&#x20;y&#x20;por&#x20;supuesto&#x20;no&#x20;podemos&#x20;&ldquo;hacer&#x20;zoom&rdquo;.&#x20;Las&#x20;gr&aacute;ficas&#x20;de&#x20;Pandora&#x20;FMS&#x20;permiten&#x20;mostrar&#x20;y&#x20;usar&#x20;todos&#x20;los&#x20;datos&#x20;en&#x20;una&#x20;gr&aacute;fica,&#x20;que&#x20;llamamos&#x20;&ldquo;TIP&rdquo;&#x20;que&#x20;muestra&#x20;todos&#x20;los&#x20;puntos&#x20;superpuestos&#x20;y&#x20;adem&aacute;s&#x20;permite&#x20;que&#x20;al&#x20;hacer&#x20;zoom&#x20;no&#x20;se&#x20;pierda&#x20;resoluci&oacute;n.&#x20;&#x20;&#x20;','','1'),
(22,'es',0,'Pol&iacute;tica&#x20;de&#x20;contrase&ntilde;as','La&#x20;consola&#x20;de&#x20;Pandora&#x20;FMS&#x20;tiene&#x20;un&#x20;sistema&#x20;de&#x20;gesti&oacute;n&#x20;de&#x20;pol&iacute;tica&#x20;de&#x20;credenciales,&#x20;para&#x20;reforzar&#x20;la&#x20;seguridad&#x20;local&#x20;&#40;adem&aacute;s&#x20;de&#x20;permitir&#x20;la&#x20;autenticaci&oacute;n&#x20;externa&#x20;contra&#x20;un&#x20;LDAP,&#x20;Active&#x20;Directory&#x20;o&#x20;SAML&#41;.&#x20;A&#x20;trav&eacute;s&#x20;de&#x20;este&#x20;sistema&#x20;podemos&#x20;forzar&#x20;cambios&#x20;de&#x20;password&#x20;cada&#x20;X&#x20;d&iacute;as,&#x20;guardar&#x20;un&#x20;hist&oacute;rico&#x20;de&#x20;passwords&#x20;usadas&#x20;o&#x20;evitar&#x20;el&#x20;uso&#x20;de&#x20;ciertas&#x20;contrase&ntilde;as&#x20;entre&#x20;otras&#x20;acciones.&#x20;&#x20;','https://pandorafms.com/manual/es/documentation/04_using/12_console_setup?s%5B%5D%3Dcontrase%25C3%25B1as#password_policy','1'),
(23,'es',0,'Autenticaci&oacute;n&#x20;de&#x20;doble&#x20;factor&#x20;','Es&#x20;posible&#x20;activar&#x20;&#40;y&#x20;forzar&#x20;su&#x20;uso&#x20;a&#x20;todos&#x20;los&#x20;usuarios&#41;&#x20;un&#x20;sistema&#x20;de&#x20;doble&#x20;autenticaci&oacute;n&#x20;&#40;usando&#x20;Google&#x20;Auth&#41;&#x20;para&#x20;que&#x20;cualquier&#x20;usuario&#x20;se&#x20;autentique&#x20;adem&aacute;s&#x20;de&#x20;con&#x20;una&#x20;contrase&ntilde;a,&#x20;con&#x20;un&#x20;sistema&#x20;de&#x20;token&#x20;de&#x20;un&#x20;solo&#x20;uso,&#x20;dando&#x20;al&#x20;sistema&#x20;mucha&#x20;m&aacute;s&#x20;seguridad.&#x20;&#x20;','https://pandorafms.com/manual/en/documentation/04_using/12_console_setup?s%5B%5D%3Dgoogle%26s%5B%5D%3Dauth#authentication','1');

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

ALTER TABLE `tusuario` ADD COLUMN `show_tips_startup` TINYINT UNSIGNED NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS `tfavmenu_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `id_element` TEXT,
  `url` TEXT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `section` VARCHAR(255) NOT NULL,
PRIMARY KEY (`id`));

ALTER TABLE `tnetflow_filter` ADD COLUMN `netflow_monitoring` TINYINT UNSIGNED NOT NULL default 0;
ALTER TABLE `tnetflow_filter` ADD COLUMN `traffic_max` INTEGER NOT NULL default 0;
ALTER TABLE `tnetflow_filter` ADD COLUMN `traffic_critical` float(20,2) NOT NULL default 0;
ALTER TABLE `tnetflow_filter` ADD COLUMN `traffic_warning` float(20,2) NOT NULL default 0;
ALTER TABLE `tnetflow_filter` ADD COLUMN `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tnetflow_filter` ADD COLUMN `netflow_monitoring_interval` INT UNSIGNED NOT NULL DEFAULT 300;
INSERT INTO `tconfig` (`token`, `value`) VALUES ('legacy_database_ha', 1);

COMMIT;
