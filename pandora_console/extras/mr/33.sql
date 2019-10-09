START TRANSACTION;

INSERT INTO `ttipo_modulo` VALUES
(34,'remote_cmd', 10, 'Remote CMD command, numeric data', 'mod_remote_cmd.png'),
(35,'remote_cmd_proc', 10, 'Remote CMD command, boolean data', 'mod_remote_cmd_proc.png'),
(36,'remote_cmd_string', 10, 'Remote CMD command, alphanumeric data', 'mod_remote_cmd_string.png'),
(37,'remote_cmd_inc', 10, 'Remote CMD command, incremental data', 'mod_remote_cmd_inc.png');

COMMIT;