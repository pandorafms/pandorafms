-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

alter table tusuario add (disabled NUMBER(10,0) default 0 NOT NULL);
alter table tusuario add (shortcut NUMBER(5, 0) DEFAULT 0);
	
-- -----------------------------------------------------
-- Table "tnetflow_filter"
-- -----------------------------------------------------

CREATE TABLE tnetflow_filter (
id_sg NUMBER(10, 0) NOT NULL PRIMARY KEY,
id_name VARCHAR2(600) NOT NULL,
id_group NUMBER(10, 0),
ip_dst CLOB NOT NULL,
ip_src CLOB NOT NULL,
dst_port CLOB NOT NULL,
src_port CLOB NOT NULL,
advanced_filter CLOB NOT NULL,
filter_args CLOB NOT NULL,
aggregate VARCHAR2(60),
output VARCHAR2(60)
);

CREATE SEQUENCE tnetflow_filter_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_filter_inc BEFORE INSERT ON tnetflow_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_filter_s.nextval INTO :NEW.ID_SG FROM dual; END tnetflow_filter_inc;;

-- -----------------------------------------------------
-- Table "tnetflow_report"
-- -----------------------------------------------------

CREATE TABLE tnetflow_report (
id_report NUMBER(10, 0) NOT NULL PRIMARY KEY,
id_name VARCHAR2(100) NOT NULL,
description CLOB default '',
id_group NUMBER(10, 0)
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
show_graph VARCHAR2(60),
"order" NUMBER(11,0) default 0 NOT NULL
);

CREATE SEQUENCE tnetflow_report_content_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_content_inc BEFORE INSERT ON tnetflow_report_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_content_s.nextval INTO :NEW.ID_RC FROM dual; END tnetflow_report_content_inc;;

-- -----------------------------------------------------
-- Table `tincidencia`
-- -----------------------------------------------------

alter table tincidencia add (id_agent NUMBER(10,0) default 0 NULL);

-- -----------------------------------------------------
-- Table `tagente`
-- -----------------------------------------------------
alter table tagente add (url_address CLOB default '' NULL);

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------

CREATE TABLE talert_special_days (
id NUMBER(10,0) NOT NULL PRIMARY KEY,
date DATE default '0000-00-00' NOT NULL,
same_day VARCHAR2(20) default 'sunday',
description CLOB,
CONSTRAINT talert_special_days_same_day_cons CHECK (same_day IN ('monday','tuesday','wednesday','thursday','friday','saturday','sunday'))
);

CREATE SEQUENCE talert_special_days_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_special_days_inc BEFORE INSERT ON talert_special_days REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_special_days_s.nextval INTO :NEW.ID FROM dual; END talert_special_days_inc;;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------

alter table talert_templates add (special_day NUMBER(5,0) default 0);

-- -----------------------------------------------------
-- Table `tplanned_downtime_agents`
-- -----------------------------------------------------

DELETE FROM tplanned_downtime_agents
WHERE id_downtime NOT IN (SELECT id FROM tplanned_downtime);

alter table tplanned_downtime_agents
add constraint tplanned_downtimes_foreign_key
foreign key (id_downtime)
references tplanned_downtime (id);

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

alter table tevento add (source VARCHAR2(100) default '' NOT NULL);
alter table tevento add (id_extra VARCHAR2(100) default '' NOT NULL);

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------

ALTER TABLE talert_snmp add (_snmp_f1_ CLOB default ''); 
ALTER TABLE talert_snmp add (_snmp_f2_ CLOB default ''); 
ALTER TABLE talert_snmp add (_snmp_f3_ CLOB default ''); 
ALTER TABLE talert_snmp add (_snmp_f4_ CLOB default ''); 
ALTER TABLE talert_snmp add (_snmp_f5_ CLOB default ''); 
ALTER TABLE talert_snmp add (_snmp_f6_ CLOB default '');
ALTER TABLE talert_snmp add (trap_type NUMBER(10, 0) DEFAULT -1 NOT NULL);
ALTER TABLE talert_snmp add (single_value VARCHAR2(255) DEFAULT '');

-- -----------------------------------------------------
-- Table `tevent_filter`
-- -----------------------------------------------------
CREATE TABLE tevent_filter (
  id_filter NUMBER(10, 0) NOT NULL PRIMARY KEY,
  id_group_filter NUMBER(10, 0) default 0 NOT NULL, 
  id_name VARCHAR2(600) NOT NULL,
  id_group NUMBER(10, 0) default 0 NOT NULL,
  event_type CLOB default '' NOT NULL,
  severity NUMBER(10, 0) default -1 NOT NULL,
  status NUMBER(10, 0) default -1 NOT NULL,
  search CLOB default '',
  text_agent CLOB default '', 
  pagination NUMBER(10, 0) default 25 NOT NULL,
  event_view_hr NUMBER(10, 0) default 8 NOT NULL,
  id_user_ack CLOB,
  group_rep NUMBER(10, 0) default 0 NOT NULL,
  tag VARCHAR2(600) default '' NOT NULL,
  filter_only_alert NUMBER(10, 0) default -1 NOT NULL
);

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
ALTER TABLE tconfig MODIFY value TEXT NOT NULL;
INSERT INTO tconfig (token, value) VALUES ('event_fields', 'evento,id_agente,estado,timestamp');
