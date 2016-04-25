--
-- Table structure for table `#__neno_translation_states`
--
CREATE TABLE IF NOT EXISTS `#__neno_translation_states` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `state` TINYINT UNSIGNED NULL,
  `name` VARCHAR(45) NULL,
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`));

--
-- Values for translation states
--
INSERT IGNORE INTO `#__neno_translation_states` (`id`, `state`, `name`, `description`) VALUES
(NULL, 1, 'Translated', 'The item has been translated'),
(NULL, 2, 'Waiting', 'The item is in queue for being traslated'),
(NULL, 3, 'Changed', 'The original source has changed'),
(NULL, 4, 'Not Translated', 'The item is not translated yet');

--
-- Values for content history
--
INSERT IGNORE INTO `#__content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`)
VALUES
(NULL, 'Translations', 'com_neno.translation',
'{"special":	{"dbtable":"#__neno_content_element_translations", "key":"id", "type":"Translation", "prefix":"NenoContentElementTable"}}','','','',
'{"formFile":"administrator\\/components\\/com_neno\\/models\\/forms\\/translation.xml",
"hideFields": ["id","content_type","content_id","language","checked_out","checked_out_time","params","language"],
"displayLookup":[{"sourceColumn":"state","targetTable":"#__neno_translation_states", "targetColumn":"id","displayColumn":"name"}]}');

--
-- Neno settings for content history
--
INSERT IGNORE INTO `#__neno_settings` (`setting_key`, `setting_value`, `read_only`) VALUES ('save_history', '1', 0);