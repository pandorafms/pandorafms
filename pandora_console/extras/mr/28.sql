START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

ALTER TABLE `treport_content_template` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';
INSERT INTO `tnews` (`id_news`, `author`, `subject`, `text`, `timestamp`) VALUES (1,'admin','Welcome&#x20;to&#x20;Pandora&#x20;FMS&#x20;Console', '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;',NOW());



ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_pass` VARCHAR(45);


COMMIT;
