--
-- Table structure for table `#__neno_language_pairs_pricing`
--
CREATE TABLE IF NOT EXISTS `#__neno_language_pairs_pricing` (
  `id`               INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `target_language`  VARCHAR(6)     NOT NULL,
  `translation_type` VARCHAR(25)    NOT NULL,
  `price_per_word`   DECIMAL(6, 4) NOT NULL,
  `time_updated`     DATETIME       NOT NULL,
  UNIQUE (`target_language`, `translation_type`)
);