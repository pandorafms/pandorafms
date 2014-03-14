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
