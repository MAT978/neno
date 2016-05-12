<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.Neno
 *
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 *
 */
defined('JPATH_BASE') or die;

/**
 * System plugin for Neno
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 *
 * @since       1.0
 */
class PlgSystemNeno extends JPlugin
{
	/**
	 * @var array
	 */
	protected static $recordsApprovedToSave = array();

	/**
	 * Method to register a custom database driver
	 *
	 * @return void
	 */
	public function onAfterInitialise()
	{
		$nenoLoader = JPATH_LIBRARIES . '/neno/loader.php';

		if (file_exists($nenoLoader))
		{
			JLoader::register('NenoLoader', $nenoLoader);

			// Register the Class prefix in the autoloader
			NenoLoader::init();

			// Load custom driver.
			JFactory::$database = NULL;
			JFactory::$database = NenoFactory::getDbo();
		}
	}

	/**
	 * Event triggered before uninstall an extension
	 *
	 * @param   int $extensionId Extension ID
	 *
	 * @return void
	 */
	public function onExtensionBeforeUninstall($extensionId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Check if the extension is Neno
		$query
		  ->select('*')
		  ->from('#__extensions')
		  ->where('extension_id = ' . $db->quote($extensionId));

		$db->setQuery($query);
		$extensionData = $db->loadObject();

		if (empty($extensionData) || strpos($extensionData->element, 'neno') === false)
		{
			$query
			  ->select('group_id')
			  ->from('#__neno_content_element_groups_x_extensions')
			  ->where('extension_id = ' . (int) $extensionId);

			$db->setQuery($query);
			$groupId = $db->loadResult();

			if (!empty($groupId))
			{
				/* @var $group NenoContentElementGroup */
				$group = NenoContentElementGroup::load($groupId);

				if (!empty($group))
				{
					$group->remove();
				}
			}
		}
	}

	/**
	 * Event triggered after install an extension
	 *
	 * @param   JInstaller $installer   Installer instance
	 * @param   int        $extensionId Extension Id
	 *
	 * @return void
	 */
	public function onExtensionAfterInstall($installer, $extensionId)
	{
		$this->discoverExtension($extensionId);
	}

	/**
	 * Discover extension
	 *
	 * @param integer $extensionId Extension Id
	 *
	 * @return void
	 */
	protected function discoverExtension($extensionId)
	{
		$db         = JFactory::getDbo();
		$query      = $db->getQuery(true);
		$extensions = $db->quote(NenoHelper::whichExtensionsShouldBeTranslated());

		$query
		  ->select('*')
		  ->from('#__extensions')
		  ->where(
			array(
			  'extension_id = ' . (int) $extensionId,
			  'type IN (' . implode(',', $extensions) . ')',
			)
		  );

		$db->setQuery($query);
		$extensionData = $db->loadAssoc();

		if (!empty($extensionData) && strpos($extensionData['element'], 'neno') === false)
		{
			NenoHelper::discoverExtension($extensionData);
		}
	}

	/**
	 * Event triggered after update an extension
	 *
	 * @param   JInstaller $installer   Installer instance
	 * @param   int        $extensionId Extension Id
	 *
	 * @return void
	 */
	public function onExtensionAfterUpdate($installer, $extensionId)
	{
		$this->discoverExtension($extensionId);
	}

	/**
	 * This event is executed before Joomla render the page
	 *
	 * @return void
	 */
	public function onBeforeRender()
	{
		$document = JFactory::getDocument();
		$document->addScript(JUri::root() . '/media/neno/js/common.js?v=' . NenoHelperBackend::getNenoVersion());

		if (NenoSettings::get('schedule_task_option', 'ajax') == 'ajax' && NenoSettings::get('installation_completed') == 1)
		{
			$document->addScript(JUri::root() . '/media/neno/js/ajax_module.js');
		}
	}

	/**
	 * This method will be executed once the content is save
	 *
	 * @param   string $context Save context
	 * @param   JTable $content JTable class of the content
	 * @param   bool   $isNew   If the record is new or not
	 *
	 * @return void
	 */
	public function onContentAfterSave($context, $content, $isNew)
	{
		//  If the user has create a new menu item, let's create it.
		if ($context == 'com_menus.item' && $isNew)
		{
			NenoHelper::createMenuStructure();
		}
		elseif ($content instanceof JTable) // We only can process a record if the content is a JTable instance.
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db        = JFactory::getDbo();
			$tableName = $content->getTableName();

			/* @var $table NenoContentElementTable */
			$table = NenoContentElementTable::load(array('table_name' => $tableName), false, true);

			if (!empty($table))
			{
				// If the record has changed the state to 'Trashed'
				if (isset($content->state) && $content->state == -2)
				{
					$primaryKeys = $content->getPrimaryKey();
					$this->trashTranslations($table, array($content->{$primaryKeys[0]}));
				}
				else
				{
					// If this change has been approved, let's process it
					if (in_array(md5($content->getPrimaryKey()), static::$recordsApprovedToSave[$context]))
					{
						$fields = $table->getFields(false, true, false, true);

						/* @var $field NenoContentElementField */
						foreach ($fields as $field)
						{
							if ($field->isTranslatable())
							{
								$primaryKeyData = array();

								foreach ($content->getPrimaryKey() as $primaryKeyName => $primaryKeyValue)
								{
									$primaryKeyData[$primaryKeyName] = $primaryKeyValue;
								}

								$field->persistTranslations($primaryKeyData);
							}
						}
					}

					// Only do that if the translation is new.
					if ($isNew)
					{
						$languages       = NenoHelper::getLanguages(false);
						$defaultLanguage = NenoSettings::get('source_language');

						foreach ($languages as $language)
						{
							if ($language->lang_code != $defaultLanguage)
							{
								$shadowTable = $db->generateShadowTableName($tableName, $language->lang_code);
								$properties  = $content->getProperties();
								$query       = 'REPLACE INTO ' . $db->quoteName($shadowTable) . ' (' . implode(',', $db->quoteName(array_keys($properties))) . ') VALUES(' . implode(',', $db->quote($properties)) . ')';
								$db->setQuery($query);
								$db->execute();
							}
						}
					}
				}
			}
		}
	}

	/**
	 * This method will be executed once the content is save
	 *
	 * @param   string $context Save context
	 * @param   JTable $content JTable class of the content
	 * @param   bool   $isNew   If the record is new or not
	 *
	 * @return void
	 */
	public function onContentBeforeSave($context, $content, $isNew)
	{
		if ($content instanceof JTable && $context !== 'com_menus.item' && !$isNew) // We only can process a record if the content is a JTable instance.
		{
			$tableName = $content->getTableName();

			/* @var $table NenoContentElementTable */
			$table = NenoContentElementTable::load(array('table_name' => $tableName), false, true);

			if (!empty($table))
			{
				$tableCloned = clone $content;
				$tableCloned->load($content->getPrimaryKey());
				$oldValue      = $tableCloned->getProperties();
				$newValue      = $content->getProperties();
				$fields        = $table->getFields(false, true, false, true);
				$approved      = false;
				$fieldsChanged = array();

				/* @var $field NenoContentElementField */
				foreach ($fields as $field)
				{
					if ($oldValue[$field->getFieldName()] != $newValue[$field->getFieldName()])
					{
						if ($field->isTranslatable())
						{
							$approved = true;
						}
						else
						{
							$fieldsChanged[] = $field->getFieldName();
						}
					}
				}

				// If the record has changed, let's mark it as approved
				if ($approved)
				{
					if (!isset(static::$recordsApprovedToSave[$context]))
					{
						static::$recordsApprovedToSave[$context] = array();
					}

					static::$recordsApprovedToSave[$context][] = md5($content->getPrimaryKey());
				}

				// Propagate changes for non translate fields
				if (!empty($fieldsChanged))
				{
					/* @var $db NenoDatabaseDriverMysqlx */
					$db              = JFactory::getDbo();
					$languages       = NenoHelper::getLanguages(false);
					$defaultLanguage = NenoSettings::get('source_language');

					foreach ($languages as $language)
					{
						if ($language->lang_code != $defaultLanguage)
						{
							$shadowTable = $db->generateShadowTableName($tableName, $language->lang_code);
							$query       = $db->getQuery(true);

							$query->update($db->quoteName($shadowTable));

							foreach ($fieldsChanged as $fieldChanged)
							{
								$query->set($db->quoteName($fieldChanged) . ' = ' . $db->quote($content->{$fieldChanged}));
							}

							$primaryKeys = $content->getPrimaryKey();

							foreach ($primaryKeys as $primaryKeyName => $primaryKeyValue)
							{
								$query->where($db->quoteName($primaryKeyName) . ' = ' . $db->quote($primaryKeyValue));
							}
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}
	}

	/**
	 * Trash translations when the user click on the trash button
	 *
	 * @param NenoContentElementTable $table Table where the element was trashed
	 * @param mixed                   $pk    Primary key value
	 *
	 * @return void
	 */
	protected function trashTranslations(NenoContentElementTable $table, $pk)
	{
		$db          = JFactory::getDbo();
		$primaryKeys = $table->getPrimaryKeys();
		$query       = $db->getQuery(true);

		$query
		  ->select('tr.id')
		  ->from('#__neno_content_element_translations AS tr');

		/* @var $primaryKey NenoContentElementField */
		foreach ($primaryKeys as $key => $primaryKey)
		{
			$alias = 'ft' . $key;
			$query
			  ->where(
				"exists(SELECT 1 FROM #__neno_content_element_fields_x_translations AS $alias WHERE $alias.translation_id = tr.id AND $alias.field_id = " . $primaryKey->getId() . " AND $alias.value = " . $db->quote($pk) . ")"
			  );
		}

		$db->setQuery($query);
		$translationIds = $db->loadColumn();

		foreach ($translationIds as $translationId)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationId);

			$translation->remove();
		}
	}

	/**
	 * Event thrown when one or several categories change their state
	 *
	 * @param string  $context Component context
	 * @param array   $pks     Primary key values of the element changed
	 * @param integer $value   New state value
	 *
	 * @return void
	 */
	public function onCategoryChangeState($context, $pks, $value)
	{
		if ($value == -2)
		{
			/* @var $table NenoContentElementTable */
			$table = NenoContentElementTable::load(array('table_name' => '#__categories'), false);

			foreach ($pks as $pk)
			{
				$this->trashTranslations($table, $pk);
			}
		}
	}

	/**
	 * Event thrown when some content change its state
	 *
	 * @param string  $context Component context
	 * @param array   $pks     Primary key values of the element changed
	 * @param integer $value   New state value
	 *
	 * @return void
	 */
	public function onContentChangeState($context, $pks, $value)
	{
		if ($value == -2)
		{
			$tableName = NenoHelperBackend::getTableNameBasedOnComponentContext($context);

			if ($tableName !== false)
			{
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load(array('table_name' => $tableName), false);

				foreach ($pks as $pk)
				{
					$this->trashTranslations($table, $pk);
				}
			}
		}
	}

	/**
	 * This event discover/sync tables
	 *
	 * @param string $tableName Table name
	 *
	 * @return void
	 */
	public function onDatabaseStructure($tableName)
	{
		$db = JFactory::getDbo();

		// Unify table name
		$tableName = str_replace($db->getPrefix(), '#__', $tableName);

		/* @var $table NenoContentElementTable */
		$table = NenoContentElementTable::load(array('table_name' => $tableName));

		if (empty($table))
		{
			$otherGroup = NenoContentElementGroup::load(array('other_group' => 1));
			$table      = NenoHelper::createTableInstance($tableName, $otherGroup);
			$table->persist();
		}
		else
		{
			$table->sync();
		}
	}
}
