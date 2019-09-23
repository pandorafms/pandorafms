START TRANSACTION;

UPDATE `tlayout_data` SET `height` = 70 , `width` = 70 WHERE `height` = 0 && `width` = 0 && image NOT LIKE '%dot%' && ((`type` IN (0,5)) ||
(`type` = 10 && `image` IS NOT NULL && `image` != '' && `image` != 'none') ||
(`type` = 11 && `image` IS NOT NULL && `image` != '' && `image` != 'none' && `show_statistics` = 0));

INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_enabled', 0);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_user', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_hostname', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_api_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_req_timeout', 5);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_group', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_criticity', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_creator', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_owner', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_type', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_status', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_title', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_content', '');

COMMIT;