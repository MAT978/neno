ALTER TABLE `#__neno_log_entries` ADD INDEX( `level`);
ALTER TABLE `#__neno_log_entries` ADD INDEX( `trigger`);
ALTER TABLE `#__neno_log_entries` ADD INDEX (`level`, `trigger`);
ALTER TABLE `#__neno_log_entries` CHANGE `message` `message` VARCHAR(400) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `#__neno_log_entries` ADD INDEX( `message`);