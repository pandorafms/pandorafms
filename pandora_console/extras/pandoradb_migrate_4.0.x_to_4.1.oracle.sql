-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

alter table tusuario add (disabled NUMBER(10,0) default 0 NOT NULL);

-- -----------------------------------------------------
-- Table "tnetflow_report"
-- -----------------------------------------------------

CREATE TABLE tnetflow_report (
id_report NUMBER(10, 0) NOT NULL PRIMARY KEY,
id_name VARCHAR2(100) NOT NULL,
description CLOB default '',
"group" VARCHAR2(50)
);

CREATE SEQUENCE tnetflow_report_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_inc BEFORE INSERT ON tnetflow_report REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_s.nextval INTO :NEW.ID_REPORT FROM dual; END tnetflow_report_inc;;

-- -----------------------------------------------------
-- Table "tnetflow_report_content"
-- -----------------------------------------------------

CREATE TABLE tnetflow_report_content (
id_rc NUMBER(10, 0) NOT NULL PRIMARY KEY,
id_report NUMBER(10, 0) NOT NULL REFERENCES tnetflow_report(id_report) ON DELETE CASCADE,
id_filter NUMBER(10,0) NOT NULL REFERENCES tnetflow_filter(id_sg) ON DELETE CASCADE,
"date" NUMBER(20, 0) default 0 NOT NULL,
period NUMBER(11, 0) default 0 NOT NULL,
max NUMBER(11, 0) default 0 NOT NULL,
show_graph VARCHAR2(60)
);

CREATE SEQUENCE tnetflow_report_content_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_content_inc BEFORE INSERT ON tnetflow_report_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_content_s.nextval INTO :NEW.ID_RC FROM dual; END tnetflow_report_content_inc;;
