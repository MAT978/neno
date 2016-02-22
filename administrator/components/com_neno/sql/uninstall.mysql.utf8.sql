UPDATE `#__extensions`
SET `enabled` = 0
WHERE `type` = 'plugin' AND `folder` = 'system' AND element = 'neno';

DELETE FROM `#__content_types` WHERE (type_alias LIKE 'com_neno.%');

DROP TABLE IF EXISTS `#__neno_installation_messages`;
DROP TABLE IF EXISTS `#__neno_content_element_groups_x_extensions`;
DROP TABLE IF EXISTS `#__neno_content_element_fields_x_translations`;
DROP TABLE IF EXISTS `#__neno_machine_translation_api_language_pairs`;
DROP TABLE IF EXISTS `#__neno_jobs_x_translations`;
DROP TABLE IF EXISTS `#__neno_content_element_translation_x_translation_methods`;
DROP TABLE IF EXISTS `#__neno_content_element_translations`;
DROP TABLE IF EXISTS `#__neno_content_element_language_strings`;
DROP TABLE IF EXISTS `#__neno_content_element_fields`;
DROP TABLE IF EXISTS `#__neno_content_element_language_files`;
DROP TABLE IF EXISTS `#__neno_content_element_tables`;
DROP TABLE IF EXISTS `#__neno_content_element_groups_x_translation_methods`;
DROP TABLE IF EXISTS `#__neno_content_element_groups`;
DROP TABLE IF EXISTS `#__neno_tasks`;
DROP TABLE IF EXISTS `#__neno_jobs`;
DROP TABLE IF EXISTS `#__neno_content_language_defaults`;
DROP TABLE IF EXISTS `#__neno_translation_methods`;
DROP TABLE IF EXISTS `#__neno_settings`;
DROP TABLE IF EXISTS `#__neno_machine_translation_apis`;
DROP TABLE IF EXISTS `#__neno_language_external_translators_comments`;
DROP TABLE IF EXISTS `#__neno_content_element_table_filters`;
DROP TABLE IF EXISTS `#__neno_translation_states`;
DROP TABLE IF EXISTS `#__neno_language_pairs_pricing`;