START TRANSACTION;

UPDATE `tconfig` set value = 'Lato-Regular.ttf' WHERE token LIKE 'custom_report_front_font';
UPDATE `tconfig` set value = 'Lato-Regular.ttf' WHERE token LIKE 'fontpath';
UPDATE `tlanguage` SET `name` = 'Deutsch' WHERE `id_language` = 'de';

COMMIT;
