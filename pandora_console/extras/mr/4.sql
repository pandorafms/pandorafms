alter table tusuario add autorefresh_white_list text not null default '';
ALTER TABLE tserver_export MODIFY name varchar(600) BINARY NOT NULL default '';