--
-- Table structure for table `#__neno_content_issues`
--
CREATE TABLE IF NOT EXISTS `#__neno_content_issues` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `discovered`  DATETIME      NOT NULL,
  `error_code`  VARCHAR(45)   NOT NULL,
  `item_id`     VARCHAR(11)   DEFAULT NULL,
  `table_name`  VARCHAR(255)  DEFAULT NULL,
  `lang`        VARCHAR(8)    DEFAULT NULL,
  `info`        TEXT          NOT NULL,
  `fixed`       DATETIME      NOT NULL DEFAULT '0000-00-00 00:00:00',
  `fixed_by`    INT(11)       NOT NULL DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
