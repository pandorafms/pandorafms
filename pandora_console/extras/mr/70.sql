START TRANSACTION;

-- Watch out! The following field migration must be done before altering the corresponding table.
UPDATE `tevent_filter`
SET `search` = `regex`,
    `regex` = '1'
WHERE `regex` IS NOT NULL AND `regex` != '';

-- Watch out! The following alter command must be done after the previous update of this table.
ALTER TABLE `tevent_filter` MODIFY COLUMN `regex` TINYINT unsigned NOT NULL DEFAULT 0;

COMMIT;