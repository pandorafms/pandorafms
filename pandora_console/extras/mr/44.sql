START TRANSACTION;

ALTER TABLE `talert_templates`
ADD COLUMN `field16` TEXT NOT NULL AFTER `field15`
,ADD COLUMN `field17` TEXT NOT NULL AFTER `field16`
,ADD COLUMN `field18` TEXT NOT NULL AFTER `field17`
,ADD COLUMN `field19` TEXT NOT NULL AFTER `field18`
,ADD COLUMN `field20` TEXT NOT NULL AFTER `field19`
,ADD COLUMN `field16_recovery` TEXT NOT NULL AFTER `field15_recovery`
,ADD COLUMN `field17_recovery` TEXT NOT NULL AFTER `field16_recovery`
,ADD COLUMN `field18_recovery` TEXT NOT NULL AFTER `field17_recovery`
,ADD COLUMN `field19_recovery` TEXT NOT NULL AFTER `field18_recovery`
,ADD COLUMN `field20_recovery` TEXT NOT NULL AFTER `field19_recovery`;

UPDATE `trecon_script` SET `description`='Specific&#x20;Pandora&#x20;FMS&#x20;Intel&#x20;DCM&#x20;Discovery&#x20;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2011&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x20;./ipmi-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;&lt;custom_field3&gt;&#x20;&lt;custom_field4&gt;&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;Network&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;Username&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;Password&#x0d;&#x0a;*&#x20;custom_field4&#x20;=&#x20;Additional&#x20;parameters&#x20;i.e.:&#x20;-D&#x20;LAN_2_0' WHERE `name`='IPMI&#x20;Recon';

COMMIT;
