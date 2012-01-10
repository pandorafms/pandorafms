-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE "tusuario" ADD COLUMN "disabled" INTEGER NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------
CREATE TABLE "tnetflow_filter" (
	"id_sg" SERIAL NOT NULL PRIMARY KEY,
  	"id_name" varchar(60) NOT NULL default '',
  	"group" varchar(60),
  	"ip_dst" varchar(100),
	"ip_src" varchar(100),
  	"dst_port" varchar(100),
	"src_port" varchar(100),
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
  	"group" varchar(60)
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
	"show_graph" varchar(60)
);

