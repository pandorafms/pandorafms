CREATE TABLE tupdate_settings ( key VARCHAR2(255) default '' PRIMARY KEY, value VARCHAR2(255) default '')
/INSERT INTO tupdate_settings VALUES ('current_update', '0')
/INSERT INTO tupdate_settings VALUES ('customer_key', 'PANDORA-FREE')
/INSERT INTO tupdate_settings VALUES ('keygen_path', '/usr/share/pandora/util/keygen') 
/INSERT INTO tupdate_settings VALUES ('update_server_host', 'www.artica.es')
/INSERT INTO tupdate_settings VALUES ('update_server_port', '80')
/INSERT INTO tupdate_settings VALUES ('update_server_path', '/pandoraupdate4/server.php')
/INSERT INTO tupdate_settings VALUES ('updating_binary_path', 'Path where the updated binary files will be stored')
/INSERT INTO tupdate_settings VALUES ('updating_code_path', 'Path where the updated code is stored')
/INSERT INTO tupdate_settings VALUES ('dbname', '')
/INSERT INTO tupdate_settings VALUES ('dbhost', '')
/INSERT INTO tupdate_settings VALUES ('dbpass', '')
/INSERT INTO tupdate_settings VALUES ('dbuser', '')
/INSERT INTO tupdate_settings VALUES ('dbport', '')
/INSERT INTO tupdate_settings VALUES ('proxy', '')
/INSERT INTO tupdate_settings VALUES ('proxy_port', '')
/INSERT INTO tupdate_settings VALUES ('proxy_user', '')
/INSERT INTO tupdate_settings VALUES ('proxy_pass', '')
CREATE TABLE tupdate_package( id NUMBER(10, 0) NOT NULL PRIMARY KEY, timestamp  TIMESTAMP default NULL, description VARCHAR2(255) default '')
CREATE SEQUENCE tupdate_package_s INCREMENT BY 1 START WITH 1
CREATE OR REPLACE TRIGGER tupdate_package_inc BEFORE INSERT ON tupdate_package REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_package_s.nextval INTO :NEW.ID FROM dual; END;
CREATE TABLE tupdate ( id NUMBER(10, 0) NOT NULL PRIMARY KEY, type VARCHAR2(15), id_update_package NUMBER(10, 0) default 0 REFERENCES tupdate_package(id) ON DELETE CASCADE, filename VARCHAR2(250) default '', checksum VARCHAR2(250) default '', previous_checksum VARCHAR2(250) default '', svn_version NUMBER(10, 0) default 0, data CLOB default '', data_rollback CLOB default '', description CLOB default '', db_table_name VARCHAR2(140) default '', db_field_name VARCHAR2(140) default '', db_field_value VARCHAR2(1024) default '', CONSTRAINT tupdate_type_cons CHECK (type IN ('code', 'db_data', 'db_schema', 'binary')))
CREATE OR REPLACE TRIGGER tupdate_update AFTER UPDATE OF ID ON tupdate_package FOR EACH ROW BEGIN UPDATE tupdate SET ID_UPDATE_PACKAGE = :NEW.ID WHERE ID_UPDATE_PACKAGE = :OLD.ID; END;
CREATE SEQUENCE tupdate_s INCREMENT BY 1 START WITH 1
CREATE OR REPLACE TRIGGER tupdate_inc BEFORE INSERT ON tupdate REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_s.nextval INTO :NEW.ID FROM dual; END;
CREATE TABLE tupdate_journal ( id NUMBER(10, 0) NOT NULL PRIMARY KEY, id_update NUMBER(10, 0) default 0 REFERENCES tupdate(id) ON DELETE CASCADE)
CREATE SEQUENCE tupdate_journal_s INCREMENT BY 1 START WITH 1
CREATE OR REPLACE TRIGGER tupdate_journal_inc BEFORE INSERT ON tupdate_journal REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_journal_s.nextval INTO :NEW.ID FROM dual; END;
CREATE OR REPLACE TRIGGER tupdate_journal_update AFTER UPDATE OF ID ON tupdate FOR EACH ROW BEGIN UPDATE tupdate_journal SET ID = :NEW.ID WHERE ID = :OLD.ID; END;
