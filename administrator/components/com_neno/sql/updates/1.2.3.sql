ALTER TABLE `#__neno_content_element_table_filters` DROP INDEX table_id;
ALTER TABLE `#__neno_content_element_language_files` ADD `translate` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `#__neno_content_element_language_strings` ADD `translate` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `#__neno_content_element_translations` ADD `checked_out` INT NOT NULL;
ALTER TABLE `#__neno_content_element_translations` ADD `checked_out_time` DATETIME NOT NULL;