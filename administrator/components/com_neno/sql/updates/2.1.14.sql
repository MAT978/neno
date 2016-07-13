CREATE TABLE IF NOT EXISTS `#__neno_log_entries` (
  `id`         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `time_added` DATETIME     NOT NULL,
  `action`     VARCHAR(50)  NOT NULL,
  `message`    VARCHAR(400) NOT NULL,
  `level`      TINYINT(1)   NOT NULL,
  `trigger`    INT          NOT NULL,
  INDEX( `level`),
  INDEX( `trigger`),
  INDEX (`level`, `trigger`),
  INDEX( `message`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;