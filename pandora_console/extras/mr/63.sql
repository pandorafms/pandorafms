START TRANSACTION;

INSERT INTO `pandora`.`treport_custom_sql` (`id`, `name`, `sql`) VALUES ('5', 'Agent&#x20;safe&#x20;mode&#x20;not&#x20;enable', 'select&#x20;nombre&#x20;from&#x20;tagente&#x20;where&#x20;safe_mode_module&#x20;=&#x20;0');

COMMIT;
