START TRANSACTION;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

DELETE FROM `twelcome_tip` WHERE `title` = 'Automatic&#x20;agent&#x20;provision&#x20;system';

INSERT INTO `twelcome_tip` (`id_lang`,`id_profile`,`title`,`text`,`url`,`enable`) VALUES ('en_GB',0,'Automatic&#x20;agent&#x20;provision&#x20;system','The&#x20;agent&#x20;self-provisioning&#x20;system&#x20;allows&#x20;an&#x20;agent&#x20;recently&#x20;entered&#x20;into&#x20;the&#x20;system&#x20;to&#x20;automatically&#x20;apply&#x20;changes&#x20;to&#x20;their&#x20;configuration&#x20;&#40;such&#x20;as&#x20;moving&#x20;them&#x20;from&#x20;group,&#x20;assigning&#x20;them&#x20;certain&#x20;values&#x20;in&#x20;custom&#x20;fields&#41;&#x20;and&#x20;of&#x20;course&#x20;applying&#x20;certain&#x20;monitoring&#x20;policies.&#x20;It&#x20;is&#x20;one&#x20;of&#x20;the&#x20;most&#x20;powerful&#x20;functionalities,&#x20;aimed&#x20;at&#x20;managing&#x20;very&#x20;large&#x20;system&#x20;parks.','https://pandorafms.com/manual/start?id=en/documentation/02_installation/05_configuration_agents#conf',1);

COMMIT;