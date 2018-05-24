START TRANSACTION;

INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_docs_logo', 'default_docs.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_support_logo', 'default_support.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_logo_white_bg_preview', 'pandora_logo_head_white_bg.png');

UPDATE talert_actions SET name='Monitoring&#x20;Event' WHERE name='Pandora&#x20;FMS&#x20;Event';

COMMIT;