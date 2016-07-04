
CREATE TABLE IF NOT EXISTS `#__neno_content_element_table_filters` (
	`id`                   INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`table_id`             INT(11)      NOT NULL,
	`field_id`             INT(11)      NOT NULL,
	`comparaison_operator` VARCHAR(45)  NOT NULL,
	`filter_value`         VARCHAR(255) NOT NULL,
	UNIQUE KEY `table_id` (`table_id`, `field_id`)
);

INSERT IGNORE INTO `#__neno_machine_translation_apis` VALUES (4, 'Bing', 'machine');

