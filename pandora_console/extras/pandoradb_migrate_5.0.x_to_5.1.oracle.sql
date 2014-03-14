-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE talert_templates ADD (field1_recovery CLOB default '' NULL);

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
ALTER TABLE talert_actions ADD (field1_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field2_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field3_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field4_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field5_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field6_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field7_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field8_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field9_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field10_recovery CLOB default '' NULL);

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO tconfig (token, value) VALUES ('graph_color4', '#FF66CC');
INSERT INTO tconfig (token, value) VALUES ('graph_color5', '#CC0000');
INSERT INTO tconfig (token, value) VALUES ('graph_color6', '#0033FF');
INSERT INTO tconfig (token, value) VALUES ('graph_color7', '#99FF99');
INSERT INTO tconfig (token, value) VALUES ('graph_color8', '#330066');
INSERT INTO tconfig (token, value) VALUES ('graph_color9', '#66FFFF');
INSERT INTO tconfig (token, value) VALUES ('graph_color10', '#6666FF');

UPDATE tconfig SET value='#FFFF00' WHERE token='graph_color2';
UPDATE tconfig SET value='#FF6600' WHERE token='graph_color3';
