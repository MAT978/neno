INSERT IGNORE INTO `#__neno_settings` (`setting_key`,`setting_value`,`read_only`) VALUES ('only_prefix', 1, 0);


UPDATE `#__neno_settings`
SET `setting_value` = 'https://www.neno-translate.com/'
WHERE `setting_key` = 'server_url';

UPDATE `#__neno_settings`
SET `setting_value` = 'http://api.neno-translate.com/en/api/'
WHERE `setting_key` = 'api_server_url';
