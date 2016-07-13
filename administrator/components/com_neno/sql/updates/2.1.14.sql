CREATE TABLE IF NOT EXISTS `#__neno_log_entries` (
  `id`         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `time_added` DATETIME     NOT NULL,
  `action`     VARCHAR(50)  NOT NULL,
  `message`    VARCHAR(400) NOT NULL,
  `level`      TINYINT(1)   NOT NULL,
  `trigger`    INT          NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

ALTER TABLE `#__neno_log_entries` ADD INDEX( `level`);
ALTER TABLE `#__neno_log_entries` ADD INDEX( `trigger`);
ALTER TABLE `#__neno_log_entries` ADD INDEX (`level`, `trigger`);
ALTER TABLE `#__neno_log_entries` CHANGE `message` `message` VARCHAR(400) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `#__neno_log_entries` ADD INDEX( `message`);