CREATE TABLE IF NOT EXISTS `#__neno_backlink_metadata` (
  `id`              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `table_name`      VARCHAR(255) NOT NULL,
  `column_name`     VARCHAR(255) NOT NULL,
  `where_statement` TEXT         NOT NULL,
  `language`        VARCHAR(7)   NOT NULL,
  INDEX (`language`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;