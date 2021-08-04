CREATE PROCEDURE updateSnmpAlerts()
BEGIN
  DECLARE tokenId INT DEFAULT 0;
  DECLARE procedureRun INT DEFAULT 0;
  DECLARE done BOOLEAN DEFAULT FALSE;
  DECLARE a, b INT DEFAULT 0;
  DECLARE alertsCur CURSOR FOR SELECT id, id_alert_command FROM talert_actions;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  SELECT id_config, value INTO tokenId, procedureRun FROM tconfig WHERE token = 'update_snmp_alerts_procedure_already_run' LIMIT 1;

  IF procedureRun < 1 THEN
    SET done = FALSE;

    OPEN alertsCur;

    read_loop: LOOP
      FETCH alertsCur INTO a, b;
      IF done THEN
        LEAVE read_loop;
      END IF;

      UPDATE talert_snmp SET id_alert = b WHERE id_alert = a;
      UPDATE talert_snmp_action SET alert_type = b WHERE alert_type = a;
    END LOOP;

    CLOSE alertsCur;

    IF tokenId < 1 THEN
      INSERT INTO tconfig (id_config, token, value) VALUES ('', 'update_snmp_alerts_procedure_already_run', '1');
    ELSE
      UPDATE tconfig SET value = 1 WHERE token = 'update_snmp_alerts_procedure_already_run';
    END IF;
  END IF;
END