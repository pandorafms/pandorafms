ALTER TABLE tusuario ADD `section` TEXT NOT NULL;
ALTER TABLE tusuario ADD `data_section` TEXT NOT NULL;
UPDATE tconfig set `value`= "4.1" WHERE `token`="db_scheme_version";
UPDATE tconfig set `value`= "PD130615" WHERE `token`="db_scheme_build";

