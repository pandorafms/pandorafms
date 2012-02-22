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

ALTER TABLE "tincidencia" ADD COLUMN "id_agent" INTEGER(10) NULL DEFAULT 0;

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
        "date" DATE NOT NULL default '0000-00-00',
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
-- Table `tgraph_template`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS "tgraph_template" (
  "id_graph_template" SERIAL NOT NULL PRIMARY KEY,
  "id_user" TEXT NOT NULL default '',
  "name" TEXT NOT NULL default '',
  "description" TEXT NOT NULL default '',
  "period" INTEGER NOT NULL default 0,
  "width" SMALLINT NOT NULL default 0,
  "height" SMALLINT NOT NULL default 0,
  "private" SMALLINT NOT NULL default 0,
  "events" SMALLINT NOT NULL default 0,
  "stacked" SMALLINT NOT NULL default 0,
  "id_group" INTEGER NOT NULL default 0
 );
 
 -- -----------------------------------------------------
-- Table `tgraph_source_template`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS "tgraph_source_template" (
  "id_gs_template" SERIAL NOT NULL PRIMARY KEY,
  "id_template" INTEGER NOT NULL default 0,
  "agent" TEXT NOT NULL default '',
  "module" TEXT NOT NULL default '',
  "period" INTEGER NOT NULL default 0,
  "weight" DOUBLE PRECISION default 2.0,
  "exact_match" SMALLINT NOT NULL default 0
 );
 
-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
 ALTER TABLE treport_content_item ADD FOREIGN KEY("id_report_content") REFERENCES treport_content("id_rc") ON UPDATE CASCADE ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table `treport`
-- -----------------------------------------------------
ALTER TABLE "treport" ADD COLUMN "id_template" INTEGER NOT NULL default 0;
