START TRANSACTION;

ALTER TABLE `tmetaconsole_agent` ADD INDEX `id_tagente_idx` (`id_tagente`);

DELETE FROM `ttipo_modulo` WHERE `nombre` LIKE 'log4x';

CREATE TABLE IF NOT EXISTS `tcredential_store` (
    `identifier` varchar(100) NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL DEFAULT 0,
    `product` enum('CUSTOM', 'AWS', 'AZURE', 'GOOGLE') default 'CUSTOM',
    `username` text,
    `password` text,
    `extra_1` text,
    `extra_2` text,
    PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `tcredential_store` (`identifier`, `id_group`, `product`, `username`, `password`) VALUES ("imported_aws_account", 0, "AWS", (SELECT `value` FROM `tconfig` WHERE `token` = "aws_access_key_id" LIMIT 1), (SELECT `value` FROM `tconfig` WHERE `token` = "aws_secret_access_key" LIMIT 1));
DELETE FROM `tcredential_store` WHERE `username` IS NULL AND `password` IS NULL;

UPDATE `tagente` ta INNER JOIN `tagente` taa on ta.`id_parent` = taa.`id_agente` AND taa.`nombre` IN ("us-east-1", "us-east-2", "us-west-1", "us-west-2", "ca-central-1", "eu-central-1", "eu-west-1", "eu-west-2", "eu-west-3", "ap-northeast-1", "ap-northeast-2", "ap-southeast-1", "ap-southeast-2", "ap-south-1", "sa-east-1") SET ta.nombre = md5(concat((SELECT `username` FROM `tcredential_store` WHERE `identifier` = "imported_aws_account" LIMIT 1), ta.`nombre`));

UPDATE `tagente` SET `nombre` = md5(concat((SELECT `username` FROM `tcredential_store` WHERE `identifier` = "imported_aws_account" LIMIT 1), `nombre`)) WHERE `nombre` IN ("Aws", "us-east-1", "us-east-2", "us-west-1", "us-west-2", "ca-central-1", "eu-central-1", "eu-west-1", "eu-west-2", "eu-west-3", "ap-northeast-1", "ap-northeast-2", "ap-southeast-1", "ap-southeast-2", "ap-south-1", "sa-east-1");

UPDATE `trecon_task` SET `auth_strings` = "imported_aws_account" WHERE `type` IN (2,6,7);

COMMIT;
