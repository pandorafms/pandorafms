-- ---------------------------------------------------------------------
-- Table "talert_templates"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_templates" ADD COLUMN "field1_recovery" text NULL default '';

-- ---------------------------------------------------------------------
-- Table "talert_actions"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_actions" ADD COLUMN "field1_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field2_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field3_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field4_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field5_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field6_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field7_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field8_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field9_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field10_recovery" text NULL default '';

-- ---------------------------------------------------------------------
-- Table "tconfig"
-- ---------------------------------------------------------------------
INSERT INTO "tconfig" ("token", "value") VALUES
('graph_color4', '#FF66CC'),
('graph_color5', '#CC0000'),
('graph_color6', '#0033FF'),
('graph_color7', '#99FF99'),
('graph_color8', '#330066'),
('graph_color9', '#66FFFF'),
('graph_color10', '#6666FF');

UPDATE "tconfig" SET "value"='#FFFF00' WHERE "token"='graph_color2';
UPDATE "tconfig" SET "value"='#FF6600' WHERE "token"='graph_color3';

-- ---------------------------------------------------------------------
-- Table "tconfig_os"
-- ---------------------------------------------------------------------
INSERT INTO "tconfig_os" VALUES (17, 'Router', 'Generic router', 'so_router.png');
INSERT INTO "tconfig_os" VALUES (18, 'Switch', 'Generic switch', 'so_switch.png');
INSERT INTO "tconfig_os" VALUES (19, 'Satellite', 'Satellite agent', 'satellite.png');

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
/* 2014/05/21 */
ALTER TABLE "tagente_modulo" ADD COLUMN "min_ff_event_normal" INTEGER default 0;
ALTER TABLE "tagente_modulo" ADD COLUMN "min_ff_event_warning" INTEGER default 0;
ALTER TABLE "tagente_modulo" ADD COLUMN "min_ff_event_critical" INTEGER default 0;
ALTER TABLE "tagente_modulo" ADD COLUMN "each_ff" SMALLINT default 0;
/* 2014/05/31 */
ALTER TABLE "tagente_modulo" ADD COLUMN "ff_timeout" INTEGER unsigned default 0;

/* 2014/03/18 */
-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE "tmodule_relationship" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"module_a" INTEGER NOT NULL REFERENCES tagente_modulo("id_agente_modulo")
		ON DELETE CASCADE,
	"module_b" INTEGER NOT NULL REFERENCES tagente_modulo("id_agente_modulo")
		ON DELETE CASCADE,
	"disable_update" SMALLINT NOT NULL default 0
);

-- ---------------------------------------------------------------------
-- Table "talert_snmp"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_snmp" ADD COLUMN "id_group" INTEGER NOT NULL default 0;

/* 2014/03/19 */
-- ---------------------------------------------------------------------
-- Table "talert_snmp"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f11_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f12_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f13_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f14_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f15_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f16_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f17_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f18_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f19_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f20_" text DEFAULT '';

ALTER TABLE "tnetwork_map" ADD COLUMN "l2_network" SMALLINT NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE "tlayout_data" ADD COLUMN "id_group" INTEGER NOT NULL default 0;
ALTER TABLE "tlayout_data" ADD COLUMN "id_custom_graph" INTEGER NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table talert_snmp_action
-- ---------------------------------------------------------------------
CREATE TABLE  "talert_snmp_action" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_alert_snmp" INTEGER default 0,
	"alert_type" INTEGER default 0,
	"al_field1" TEXT default '',
	"al_field2" TEXT default '',
	"al_field3" TEXT default '',
	"al_field4" TEXT default '',
	"al_field5" TEXT default '',
	"al_field6" TEXT default '',
	"al_field7" TEXT default '',
	"al_field8" TEXT default '',
	"al_field9" TEXT default '',
	"al_field10" TEXT default ''
);

-- ---------------------------------------------------------------------
-- Table treport
-- ---------------------------------------------------------------------
ALTER TABLE "treport" ADD COLUMN "non_interactive" SMALLINT DEFAULT 0;

/* 2014/04/10 */
ALTER TABLE "treport_content" ADD COLUMN "name" varchar(150) NULL;

/* 2014/04/11 */
-- ---------------------------------------------------------------------
-- Table `trecon_script` and `trecon_task`
-- ---------------------------------------------------------------------
ALTER TABLE "trecon_script" ADD COLUMN "macros" TEXT default '';
ALTER TABLE "trecon_task" ADD COLUMN "macros" TEXT default '';

/* 2014/05/05 */
-- ---------------------------------------------------------------------
-- Table tlink
-- ---------------------------------------------------------------------
UPDATE "tlink" SET "link"='http://wiki.pandorafms.com/?title=Pandora' WHERE "name"='Pandora FMS Manual';

/* 2014/05/07 */
-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO "tconfig" ("token", "value") VALUES 
('custom_report_front', 0),
('custom_report_front_font', 'FreeSans.ttf'),
('custom_report_front_logo', 'images/pandora_logo_white.jpg'),
('custom_report_front_header', ''),
('custom_report_front_footer', '');

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ---------------------------------------------------------------------
DELETE FROM "tnetwork_profile" WHERE "id_np"=1;
DELETE FROM "tnetwork_profile" WHERE "id_np"=4;
DELETE FROM "tnetwork_profile" WHERE "id_np"=5;
DELETE FROM "tnetwork_profile" WHERE "id_np"=6;

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ---------------------------------------------------------------------
DELETE FROM "tnetwork_profile_component" WHERE "id_np"=1;
DELETE FROM "tnetwork_profile_component" WHERE "id_np"=4;
DELETE FROM "tnetwork_profile_component" WHERE "id_np"=5;
DELETE FROM "tnetwork_profile_component" WHERE "id_np"=6;
DELETE FROM "tnetwork_profile_component" WHERE "id_nc"=24 AND "id_np"=3;

/* 2014/05/25 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE "tnetwork_component" ADD COLUMN "min_ff_event_normal" INTEGER default 0;
ALTER TABLE "tnetwork_component" ADD COLUMN "min_ff_event_warning" INTEGER default 0;
ALTER TABLE "tnetwork_component" ADD COLUMN "min_ff_event_critical" INTEGER default 0;
ALTER TABLE "tnetwork_component" ADD COLUMN "each_ff" SMALLINT default 0;

/* 2014/05/30 */
-- ---------------------------------------------------------------------
-- Table `tnews`
-- ---------------------------------------------------------------------
ALTER TABLE "tnews" ADD COLUMN "id_group" INTEGER NOT NULL default 0;
ALTER TABLE "tnews" ADD COLUMN "modal" SMALLINT DEFAULT 0;
ALTER TABLE "tnews" ADD COLUMN "expire" SMALLINT DEFAULT 0;
ALTER TABLE "tnews" ADD COLUMN "expire_timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00';

/* 2014/05/31 */
-- ---------------------------------------------------------------------
-- Table `tagente_estado`
-- ---------------------------------------------------------------------
ALTER TABLE "tagente_estado" ADD COLUMN "ff_start_utimestamp" BIGINT default 0;

/* 2014/06/24 */
-- ---------------------------------------------------------------------
-- Table trecon_script
-- ---------------------------------------------------------------------
DELETE FROM "trecon_script" WHERE "id_recon_script"=1;

/* 2014/08/07 */
-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE "tagente_modulo" MODIFY COLUMN "post_process" NUMERIC(18,15) default 0;

/* 2014/08/18 */
-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
INSERT INTO "talert_commands" ("name", "command", "description", "internal", "fields_descriptions", "fields_values") VALUES ('Integria&#x20;IMS&#x20;Ticket','Internal&#x20;type','This&#x20;alert&#x20;create&#x20;a&#x20;ticket&#x20;into&#x20;your&#x20;Integria&#x20;IMS.',1,'[\"Integria&#x20;IMS&#x20;API&#x20;path\",\"Integria&#x20;IMS&#x20;API&#x20;pass\",\"Integria&#x20;IMS&#x20;user\",\"Ticket&#x20;title\",\"Ticket&#x20;group&#x20;ID\",\"Ticket&#x20;priority\",\"Ticket&#x20;description\"]','[\"\",\"\",\"\",\"\",\"\",\"10,Maintenance;0,Informative;1,Low;2,Medium;3,Serious;4,Very&#x20;Serious\",\"\"]');

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
INSERT INTO "talert_actions" ("name", "id_alert_command", "field1", "field2", "field3", "field4", "field5", "field6", "field7", "field8", "field9", "field10", "id_group", "action_threshold") VALUES ('Create&#x20;a&#x20;ticket&#x20;in&#x20;Integria&#x20;IMS',13,'http://localhost/integria/include/api.php','1234','admin','_agent_:&#x20;_alert_name_','1','3','_alert_description_','','','',0,0);