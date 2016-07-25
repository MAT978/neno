---
--- Change any machine translation method to manual
---
UPDATE `#__neno_content_element_groups_x_translation_methods`
SET `translation_method_id` = 1
WHERE (`translation_method_id` = 2);

UPDATE `#__neno_content_element_translation_x_translation_methods`
SET `translation_method_id` = 1
WHERE (`translation_method_id` = 2);

---
--- Remove machine translation method
---
DELETE FROM `#__neno_translation_methods` WHERE (`id` = 2);

---
--- Change
---

UPDATE `#__menu` SET `link` = 'index.php?option=com_neno&view=professionaltranslations' WHERE `link` = 'index.php?option=com_neno&view=externaltranslations';
ALTER TABLE `#__neno_jobs` CHANGE `translation_credits` `funds_needed` DECIMAL(10,2) NOT NULL;