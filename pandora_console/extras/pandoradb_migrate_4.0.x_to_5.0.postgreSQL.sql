-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE "tusuario" ADD COLUMN "disabled" INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "tusuario" ADD COLUMN "shortcut" SMALLINT DEFAULT 0;

-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------
CREATE TABLE "tnetflow_filter" (
	"id_sg" SERIAL NOT NULL PRIMARY KEY,
  	"id_name" varchar(600) NOT NULL default '',
  	"id_group" INTEGER,
  	"ip_dst" TEXT NOT NULL,
	"ip_src" TEXT NOT NULL,
  	"dst_port" TEXT NOT NULL,
	"src_port" TEXT NOT NULL,
	"advanced_filter" TEXT NOT NULL,
	"filter_args" TEXT NOT NULL,
	"aggregate" varchar(60),
 	"output" varchar(60)
);

-- -----------------------------------------------------
-- Table `tnetflow_report`
-- -----------------------------------------------------
CREATE TABLE "tnetflow_report" (
 	"id_report" SERIAL NOT NULL PRIMARY KEY,
 	"id_name" varchar(150) NOT NULL default '',
	"description" TEXT,
  	"id_group" INTEGER
);

-- -----------------------------------------------------
-- Table `tnetflow_report_content`
-- -----------------------------------------------------
CREATE TABLE "tnetflow_report_content" (
   	"id_rc" SERIAL NOT NULL PRIMARY KEY,
	"id_report" INTEGER NOT NULL default 0 REFERENCES tnetflow_report("id_report") ON DELETE CASCADE,
    "id_filter" INTEGER NOT NULL default 0 REFERENCES tnetflow_filter("id_sg") ON DELETE CASCADE,
	"date" BIGINT NOT NULL default 0,
	"period" INTEGER NOT NULL default 0,
	"max" INTEGER NOT NULL default 0,
	"show_graph" varchar(60),
	"order" INTEGER NOT NULL default 0
);

-- -----------------------------------------------------
-- Table `tincidencia`
-- -----------------------------------------------------

ALTER TABLE "tincidencia" ADD COLUMN "id_agent" INTEGER NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `tagente`
-- -----------------------------------------------------

ALTER TABLE "tagente" ADD COLUMN "url_address" text NULL default '';

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------

CREATE TYPE type_talert_special_days_same_day AS ENUM ('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
CREATE TABLE "talert_special_days" (
        "id" SERIAL NOT NULL PRIMARY KEY,
        "date" DATE NOT NULL default '0001-01-01',
        "same_day" type_talert_special_days_same_day NOT NULL default 'sunday',
        "description" TEXT
);

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------

ALTER TABLE "talert_templates" ADD COLUMN "special_day" SMALLINT default 0;

-- -----------------------------------------------------
-- Table `tplanned_downtime_agents`
-- -----------------------------------------------------
DELETE FROM "tplanned_downtime_agents"
WHERE "id_downtime" NOT IN (SELECT "id" FROM "tplanned_downtime");

ALTER TABLE "tplanned_downtime_agents"
ADD CONSTRAINT downtime_foreign
FOREIGN KEY("id_downtime")
REFERENCES "tplanned_downtime"("id");

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

ALTER TABLE "tevento" ADD COLUMN "source" text NULL default '';
ALTER TABLE "tevento" ADD COLUMN "id_extra" text NULL default '';

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------

ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f1_" text DEFAULT ''; 
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f2_" text DEFAULT ''; 
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f3_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f4_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f5_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f6_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "trap_type" INTEGER NOT NULL DEFAULT '-1';
ALTER TABLE "talert_snmp" ADD COLUMN "single_value" varchar(255) DEFAULT '';

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------
ALTER TABLE "tagente_modulo" ADD COLUMN "module_ff_interval" INTEGER NOT NULL default 0;

-- -----------------------------------------------------
-- Table `tevent_filter`
-- -----------------------------------------------------
CREATE TABLE "tevent_filter" (
  "id_filter"  SERIAL NOT NULL PRIMARY KEY,
  "id_group_filter" INTEGER NOT NULL default 0,
  "id_name" varchar(600) NOT NULL,
  "id_group" INTEGER NOT NULL default 0,
  "event_type" TEXT NOT NULL default '',
  "severity" INTEGER NOT NULL default -1,
  "status" INTEGER NOT NULL default -1,
  "search" TEXT default '',
  "text_agent" TEXT default '', 
  "pagination" INTEGER NOT NULL default 25,
  "event_view_hr" INTEGER NOT NULL default 8,
  "id_user_ack" TEXT,
  "group_rep" INTEGER NOT NULL default 0,
  "tag" varchar(600) NOT NULL default '',
  "filter_only_alert" INTEGER NOT NULL default -1
);

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
ALTER TABLE "tconfig" ALTER COLUMN "value" TYPE TEXT;

INSERT INTO tconfig ("token", "value") SELECT 'list_ACL_IPs_for_API', array_to_string(ARRAY(SELECT value FROM tconfig WHERE token LIKE 'list_ACL_IPs_for_API%'), ';') AS "value";
INSERT INTO "tconfig" ("token", "value") VALUES ('event_fields', 'evento,id_agente,estado,timestamp');

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
 ALTER TABLE treport_content_item ADD FOREIGN KEY("id_report_content") REFERENCES treport_content("id_rc") ON UPDATE CASCADE ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table `treport`
-- -----------------------------------------------------
ALTER TABLE "treport" ADD COLUMN "id_template" INTEGER NOT NULL default 0;

-- -----------------------------------------------------
-- Table `tgraph`
-- -----------------------------------------------------
ALTER TABLE "tgraph" ADD COLUMN "id_graph_template" INTEGER NOT NULL default 0;

-- -----------------------------------------------------
-- Table `ttipo_modulo`
-- -----------------------------------------------------
UPDATE "ttipo_modulo" SET "descripcion"='Generic data' WHERE "id_tipo"=1;
UPDATE "ttipo_modulo" SET "descripcion"='Generic data incremental' WHERE "id_tipo"=4;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------
ALTER TABLE "tusuario" ADD COLUMN "disabled" INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "tusuario" ADD COLUMN "shortcut" SMALLINT DEFAULT 0;
ALTER TABLE "tusuario" ADD COLUMN "shortcut_data" text default '';

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------
ALTER TABLE "tusuario" ADD COLUMN "section" varchar(255) NOT NULL DEFAULT '';
INSERT INTO "tusuario" ("section") VALUES ("Default");
ALTER TABLE "tusuario" ADD COLUMN "data_section" varchar(255) NOT NULL DEFAULT '';

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
ALTER TABLE "treport_content_item" ADD COLUMN "operation" text default '';

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
ALTER TABLE "tmensajes" ALTER COLUMN "mensaje" TYPE TEXT;

-- -----------------------------------------------------
-- Table `talert_compound`
-- -----------------------------------------------------

ALTER TABLE "talert_compound" ADD COLUMN "special_day" SMALLINT default 0;

-- -----------------------------------------------------
-- Table `ttimezone`
-- -----------------------------------------------------

CREATE TABLE "ttimezone" (
  "id_tz" INTEGER NOT NULL PRIMARY KEY,
  "zone" varchar(60) NOT NULL,
  "timezone" varchar(60) NOT NULL
);

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------

ALTER TABLE "tnetwork_component" ADD COLUMN "unit" text default '';

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE "tusuario" ADD COLUMN "force_change_pass" SMALLINT NOT NULL default 0;
ALTER TABLE "tusuario" ADD COLUMN "last_pass_change" BIGINT NOT NULL default 0;
ALTER TABLE "tusuario" ADD COLUMN "last_failed_login" BIGINT NOT NULL default 0;
ALTER TABLE "tusuario" ADD COLUMN "failed_attempt" INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "tusuario" ADD COLUMN "login_blocked" SMALLINT NOT NULL default 0;

-- -----------------------------------------------------
-- Table `talert_commands`
-- -----------------------------------------------------

INSERT INTO "talert_commands" ("name", "command", "description", "internal") VALUES ('Validate Event','Internal type','This alert validate the events matched with a module given the agent name (_field1_) and module name (_field2_)', 1);

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------

INSERT INTO "tconfig" ("token", "value") VALUES
('enable_pass_policy', 0),
('pass_size', 4),
('pass_needs_numbers', 0),
('pass_needs_symbols', 0),
('pass_expire', 0),
('first_login', 0),
('mins_fail_pass', 5),
('number_attempts', 5),
('enable_pass_policy_admin', 0),
('enable_pass_history', 0),
('compare_pass', 3),
('meta_style', 'meta_pandora');

-- -----------------------------------------------------
-- Table `tpassword_history`
-- -----------------------------------------------------
CREATE TABLE "tpassword_history" (
  "id_pass"  INTEGER NOT NULL PRIMARY KEY,
  "id_user" varchar(60) NOT NULL,
  "password" varchar(45) default NULL,
  "date_begin" BIGINT NOT NULL default 0,
  "date_end" BIGINT NOT NULL default 0,
);

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
UPDATE TABLE tconfig SET "value"='comparation'
WHERE "token"='prominent_time';

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------

CREATE TYPE type_tnetwork_component_wizard_level AS ENUM ('basic','advanced','custom','nowizard');
ALTER TABLE "tnetwork_component" ADD COLUMN "wizard_level" type_tnetwork_component_wizard_level default 'nowizard';
ALTER TABLE "tnetwork_component" ADD COLUMN "only_metaconsole" INTEGER default '0';
ALTER TABLE "tnetwork_component" ADD COLUMN "macros" TEXT default '';

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------

CREATE TYPE type_tagente_modulo_wizard_level AS ENUM ('basic','advanced','custom','nowizard');
ALTER TABLE "tagente_modulo" ADD COLUMN "wizard_level" type_tagente_modulo_wizard_level default 'nowizard';
ALTER TABLE "tagente_modulo" ADD COLUMN "macros" TEXT default '';

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

CREATE TYPE type_tusuario_metaconsole_access AS ENUM ('basic','advanced','custom','all','only_console');
ALTER TABLE "tusuario" ADD COLUMN "metaconsole_access" type_tusuario_metaconsole_access default 'only_console';
