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

