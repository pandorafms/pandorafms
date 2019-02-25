START TRANSACTION;

UPDATE `twidget` SET `unique_name`='example2' WHERE `class_name` LIKE 'WelcomeWidget';

COMMIT;
