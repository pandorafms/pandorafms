
CREATE PROCEDURE migrateRanges()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE i INT;
    DECLARE r TEXT;
    DECLARE cur1 CURSOR FOR SELECT `id` FROM `talert_templates`;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    UPDATE `talert_templates` SET `schedule` = '{"monday":_1_,"tuesday":_2_,"wednesday":_3_,"thursday":_4_,"friday":_5_,"saturday":_6_,"sunday":_7_}';
    UPDATE `talert_templates` SET `time_from` = "00:00:00", `time_to` = "00:00:00" WHERE `time_from` = `time_to`;

    OPEN cur1;
    read_loop: LOOP
        FETCH cur1 INTO i;

        IF done THEN
            LEAVE read_loop;
        END IF;

        SELECT concat('[{"start":"', `time_from`, '","end":"', `time_to`, '"}]') into r FROM `talert_templates` WHERE `id` = i;

        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_1_", r) WHERE `monday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_2_", r) WHERE `tuesday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_3_", r) WHERE `wednesday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_4_", r) WHERE `thursday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_5_", r) WHERE `friday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_6_", r) WHERE `saturday` > 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_7_", r) WHERE `sunday` > 0 AND `id` = i;

        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_1_", '""') WHERE `monday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_2_", '""') WHERE `tuesday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_3_", '""') WHERE `wednesday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_4_", '""') WHERE `thursday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_5_", '""') WHERE `friday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_6_", '""') WHERE `saturday` = 0 AND `id` = i;
        UPDATE `talert_templates` SET `schedule` = REPLACE(`schedule`, "_7_", '""') WHERE `sunday` = 0 AND `id` = i;
    END LOOP;
    CLOSE cur1;
END ;