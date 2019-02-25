START TRANSACTION;

UPDATE `twidget` SET `unique_name`='example' WHERE `class_name` LIKE 'WelcomeWidget';

COMMIT;
