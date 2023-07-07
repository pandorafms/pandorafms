START TRANSACTION;

ALTER TABLE `tlayout`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

ALTER TABLE `tlayout_template`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

DELETE FROM tconfig WHERE token = 'refr';

ALTER TABLE `tusuario`  ADD COLUMN `session_max_time_expire` INT NOT NULL DEFAULT 0 AFTER `auth_token_secret`;

CREATE TABLE IF NOT EXISTS `tevent_comment` (
  `id` serial PRIMARY KEY,
  `id_event` BIGINT UNSIGNED NOT NULL,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  `comment` TEXT,
  `id_user` VARCHAR(255) DEFAULT NULL,
  `action` TEXT,
  FOREIGN KEY (`id_event`) REFERENCES `tevento`(`id_evento`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

INSERT INTO `tevent_comment` (`id_event`, `utimestamp`, `comment`, `id_user`, `action`)
SELECT * FROM (
  SELECT tevento.id_evento AS `id_event`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].utimestamp'))) AS `utimestamp`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].comment'))) AS `comment`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].id_user'))) AS  `id_user`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].action'))) AS `action`
  FROM tevento
  INNER JOIN (SELECT 0 num UNION ALL SELECT 1 UNION ALL SELECT 2) n
    ON n.num < JSON_LENGTH(tevento.user_comment)
  WHERE tevento.user_comment != ""
) t order by utimestamp DESC;

ALTER TABLE tevento DROP COLUMN user_comment;

COMMIT;
