<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Neno helper.
 *
 * @since  1.0
 */
class NenoHelper
{
	/**
	 * @var array
	 */
	protected static $menuModuleReplicated = array();
	/**
	 * @var array
	 */
	protected static $menuItemsCreated = array();
	/**
	 * @var array
	 */
	protected static $menuAssociations = array();
	/**
	 * @var array
	 */
	protected static $modulesDuplicated = array();

	/**
	 * Set the working language on the currently logged in user
	 *
	 * @param   string $lang 'en-GB' or 'de-DE'
	 *
	 * @return boolean
	 */
	public static function setWorkingLanguage($lang)
	{
		$userId = JFactory::getUser()->id;

		$db = JFactory::getDbo();

		/* @var $query NenoDatabaseQueryMysqlx */
		$query = $db->getQuery(true);

		$query
		  ->replace('#__user_profiles')
		  ->set(
			array(
			  'profile_value = ' . $db->quote($lang),
			  'profile_key = ' . $db->quote('neno_working_language'),
			  'user_id = ' . intval($userId)
			)
		  );
		$db->setQuery($query);

		$db->execute();

		JFactory::getApplication()
		  ->setUserState('com_neno.working_language', $lang);

		return true;
	}

	/**
	 * Transform an array of stdClass to
	 *
	 * @param   array $objectList List of objects
	 *
	 * @return array
	 */
	public static function convertStdClassArrayToObjectArray(array $objectList)
	{
		$jObjectList = array();

		foreach ($objectList as $object)
		{
			$jObjectList[] = new JObject($object);
		}

		return $jObjectList;
	}

	/**
	 * Transform an array of neno Objects to
	 *
	 * @param   array $objectList List of objects
	 *
	 * @return array
	 */
	public static function convertNenoObjectListToJobjectList(array $objectList)
	{
		$jObjectList = array();

		/* @var $object NenoObject */
		foreach ($objectList as $object)
		{
			$jObjectList[] = $object->prepareDataForView();
		}

		return $jObjectList;
	}

	/**
	 * Check if a string ends with a particular string
	 *
	 * @param   string $string String to be checked
	 * @param   string $suffix Suffix of the string
	 *
	 * @return bool
	 */
	public static function endsWith($string, $suffix)
	{
		return $suffix === "" || mb_strpos($string, $suffix, mb_strlen($string) - mb_strlen($suffix)) !== false;
	}

	/**
	 * Get the standard pattern
	 *
	 * @param   string $componentName Component name
	 *
	 * @return string
	 */
	public static function getTableNamePatternBasedOnComponentName($componentName)
	{
		$prefix = JFactory::getDbo()->getPrefix();

		return $prefix . str_replace(array('com_'), '', strtolower($componentName));
	}

	/**
	 * Convert an array of objects to an simple array. If property is not specified, the property selected will be the first one.
	 *
	 * @param   array       $objectList   Object list
	 * @param   string|null $propertyName Property name
	 *
	 * @return array
	 */
	public static function convertOnePropertyObjectListToArray($objectList, $propertyName = NULL)
	{
		$arrayResult = array();

		if (!empty($objectList))
		{
			// If a property wasn't passed as argument, we will get the first one.
			if ($propertyName === NULL)
			{
				$properties   = array_keys((array) $objectList[0]);
				$propertyName = $properties[0];
			}

			foreach ($objectList as $object)
			{
				$arrayResult[] = $object->{$propertyName};
			}
		}

		return $arrayResult;
	}

	/**
	 * Convert an array of objects to an simple array. If property is not specified, the property selected will be the first one.
	 *
	 * @param   array       $objectList   Object list
	 * @param   string|null $propertyName Property name
	 *
	 * @return array
	 */
	public static function convertOnePropertyArrayToSingleArray($objectList, $propertyName = NULL)
	{
		$arrayResult = array();

		if (!empty($objectList))
		{
			// If a property wasn't passed as argument, we will get the first one.
			if ($propertyName === NULL)
			{
				$properties   = array_keys($objectList[0]);
				$propertyName = $properties[0];
			}

			foreach ($objectList as $object)
			{
				$arrayResult[] = $object[$propertyName];
			}
		}

		return $arrayResult;
	}

	/**
	 * Trash translations when the user click on the trash button
	 *
	 * @param NenoContentElementTable $table Table where the element was trashed
	 * @param mixed                   $pk    Primary key value
	 *
	 * @return void
	 */
	public static function trashTranslations(NenoContentElementTable $table, $pk)
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
	 * Convert a camelcase property name to a underscore case database column name
	 *
	 * @param   string $propertyName Property name
	 *
	 * @return string
	 */
	public static function convertPropertyNameToDatabaseColumnName($propertyName)
	{
		return implode('_', self::splitCamelCaseString($propertyName));
	}

	/**
	 * Split a camel case string
	 *
	 * @param   string $string Camel case string
	 *
	 * @return array
	 */
	public static function splitCamelCaseString($string)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
		$ret = $matches[0];

		foreach ($ret as &$match)
		{
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}

		return $ret;
	}

	/**
	 * Convert an array fetched from the database to an array that the indexes match with a Class property names
	 *
	 * @param   array $databaseArray Database assoc array: [property_name] = value
	 *
	 * @return array
	 */
	public static function convertDatabaseArrayToClassArray(array $databaseArray)
	{
		$objectData = array();

		foreach ($databaseArray as $fieldName => $fieldValue)
		{
			$objectData[self::convertDatabaseColumnNameToPropertyName($fieldName)] = $fieldValue;
		}

		return $objectData;
	}

	/**
	 * Convert a underscore case column name to a camelcase property name
	 *
	 * @param   string $columnName Database column name
	 *
	 * @return string
	 */
	public static function convertDatabaseColumnNameToPropertyName($columnName)
	{
		$nameParts = explode('_', $columnName);
		$firstWord = array_shift($nameParts);

		// If there are word left, let's capitalize them.
		if (!empty($nameParts))
		{
			$nameParts = array_merge(array($firstWord), array_map('ucfirst', $nameParts));
		}
		else
		{
			$nameParts = array($firstWord);
		}

		return implode('', $nameParts);
	}

	/**
	 * Discover extension
	 *
	 * @param   array $extension Extension data
	 *
	 * @return bool
	 */
	public static function discoverExtension(array $extension)
	{
		$group         = self::createGroupInstanceBasedOnExtensionId($extension);
		$extensionName = self::getExtensionName($extension);
		$languageFiles = self::getLanguageFiles($extensionName);
		$tables        = self::getComponentTables($group, $extensionName);
		$group->setAssignedTranslationMethods(self::getTranslationMethodsForLanguages());

		// If the group contains tables and/or language strings, let's save it
		if (!empty($tables) || !empty($languageFiles))
		{
			$group
			  ->setLanguageFiles($languageFiles)
			  ->setTables($tables)
			  ->persist();
		}
		else
		{
			$group->persist();
		}

		return true;
	}

	/**
	 * Create an instance of NenoContentElementGroup
	 *
	 * @param array $extension Extension Data
	 *
	 * @return array|mixed|NenoContentElementGroup
	 */
	public static function createGroupInstanceBasedOnExtensionId(array $extension)
	{
		// Check if this extension has been discovered already
		$groupId = self::isExtensionAlreadyDiscovered($extension['extension_id']);

		if ($groupId !== false)
		{
			$group = NenoContentElementGroup::load($groupId);
		}
		else
		{
			$group = new NenoContentElementGroup(array('group_name' => $extension['name']));
		}

		$group->addExtension($extension['extension_id']);

		return $group;
	}

	/**
	 * Check if an extensions has been discovered yet
	 *
	 * @param   int $extensionId Extension Id
	 *
	 * @return bool|int False if the extension wasn't discovered before, group ID otherwise
	 */
	public static function isExtensionAlreadyDiscovered($extensionId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('group_id')
		  ->from('#__neno_content_element_groups_x_extensions')
		  ->where('extension_id = ' . (int) $extensionId);

		$db->setQuery($query);
		$groupId = $db->loadResult();

		if (!empty($groupId))
		{
			return $groupId;
		}

		return false;
	}

	/**
	 * Get the name of an extension based on its ID
	 *
	 * @param   array $extensionData Extension ID
	 *
	 * @return string
	 */
	public static function getExtensionName(array $extensionData)
	{
		$extensionName = preg_replace('/(com|tpl|plg|mod)_/', '', $extensionData['element']);

		switch ($extensionData['type'])
		{
			case 'component':
				$extensionName = 'com_' . $extensionName;
				break;
			case 'plugin':
				$extensionName = 'plg_' . $extensionData['folder'] . '_' . $extensionName;
				break;
			case 'module':
				$extensionName = 'mod_' . $extensionName;
				break;
			case 'template':
				$extensionName = 'tpl_' . $extensionName;
				break;
		}

		return $extensionName;
	}

	/**
	 * Check if a string starts with a particular string
	 *
	 * @param   string $string String to be checked
	 * @param   string $prefix Prefix of the string
	 *
	 * @return bool
	 */
	public static function startsWith($string, $prefix)
	{
		return $prefix === "" || strrpos($string, $prefix, -mb_strlen($string)) !== false;
	}

	/**
	 * Get all the language strings related to a extension (group).
	 *
	 * @param   string $extensionName Extension name
	 *
	 * @return array
	 */
	public static function getLanguageFiles($extensionName)
	{
		jimport('joomla.filesystem.folder');
		$defaultLanguage     = NenoSettings::get('source_language');
		$languageFilePattern = preg_quote($defaultLanguage) . '\.' . $extensionName . '\.(((\w)*\.)^sys)?ini';
		$languageFilesPath   = JFolder::files(JPATH_ROOT . "/language/$defaultLanguage/", $languageFilePattern);

		// Getting the template to check if there are files in the template
		$template = self::getFrontendTemplate();

		// If there is a template, let's try to get those files
		if (!empty($template))
		{
			$templateLanguageFilesPath = JPATH_ROOT . "/templates/$template/language/$defaultLanguage/";
			$overwriteFiles            = false;

			if (file_exists($templateLanguageFilesPath))
			{
				$overwriteFiles = JFolder::files(JPATH_ROOT . "/templates/$template/language/$defaultLanguage/", $languageFilePattern);
			}

			if ($overwriteFiles !== false)
			{
				$languageFilesPath = array_merge($languageFilesPath, $overwriteFiles);
			}
		}

		$languageFiles = array();

		foreach ($languageFilesPath as $languageFilePath)
		{
			// Only save the language strings if it's not a Joomla core components
			if (!self::isJoomlaCoreLanguageFile($languageFilePath))
			{
				// Checking if the file is already discovered
				if (self::isLanguageFileAlreadyDiscovered($languageFilePath))
				{
					$languageFile = NenoContentElementLanguageFile::load(
					  array(
						'filename' => $languageFilePath
					  )
					);
				}
				else
				{
					$languageFile = new NenoContentElementLanguageFile(
					  array(
						'filename'  => $languageFilePath,
						'extension' => $extensionName
					  )
					);

					$languageFile->loadStringsFromFile();
				}

				if (!empty($languageFile))
				{
					$languageFiles[] = $languageFile;
				}
			}
		}

		return $languageFiles;
	}

	/**
	 * Get front-end template
	 *
	 * @return string|null
	 */
	public static function getFrontendTemplate()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
		  ->select('template')
		  ->from('#__template_styles')
		  ->where(
			array(
			  'home = 1',
			  'client_id = 0'
			)
		  )
		  ->group('template');

		$db->setQuery($query);
		$template = $db->loadResult();

		return $template;
	}

	/**
	 * Checks if a file is a Joomla Core language file
	 *
	 * @param   string $languageFileName Language file name
	 *
	 * @return bool
	 */
	public static function isJoomlaCoreLanguageFile($languageFileName)
	{
		$fileParts = explode('.', $languageFileName);

		$result = self::removeCoreLanguageFilesFromArray(array($languageFileName), $fileParts[0]);

		return empty($result);
	}

	/**
	 * Takes an array of language files and filters out known language files shipped with Joomla
	 *
	 * @param   array  $files    Files to translate
	 * @param   string $language Language tag
	 *
	 * @return array
	 */
	public static function removeCoreLanguageFilesFromArray($files, $language)
	{
		// Get all the language files from Joomla core extensions based on a particular language
		$coreFiles  = self::getJoomlaCoreLanguageFiles($language);
		$validFiles = array();

		// Filter
		foreach ($files as $file)
		{
			// If the file wasn't found, let's add it as a valid translatable file
			if (!in_array($file, $coreFiles))
			{
				$validFiles[] = $file;
			}
		}

		return $validFiles;
	}

	/**
	 * Get the language files for all the Joomla Core extensions
	 *
	 * @param   string $language JISO language string
	 *
	 * @return array
	 */
	private static function getJoomlaCoreLanguageFiles($language)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db         = JFactory::getDbo();
		$query      = $db->getQuery(true);
		$extensions = self::whichExtensionsShouldBeTranslated();

		$query
		  ->select(
			'CONCAT(' . $db->quote($language . '.') .
			',IF(type = \'plugin\' OR type = \'template\',
				IF(type = \'plugin\', CONCAT(\'plg_\',folder,\'_\'), IF(type = \'template\', \'tpl_\',\'\')),\'\'),element,\'.ini\') as extension_name'
		  )
		  ->from('#__extensions')
		  ->where(
			array(
			  'extension_id < 10000',
			  'type IN (' . implode(',', $db->quote($extensions)) . ')'
			)
		  );

		$db->setQuery($query);
		$joomlaCoreLanguageFiles = array_merge($db->loadArray(), array($language . '.ini'));

		return $joomlaCoreLanguageFiles;
	}

	/**
	 * Return an array of extensions types allowed to be translate
	 *
	 * @return array
	 */
	public static function whichExtensionsShouldBeTranslated()
	{
		return array(
		  'component',
		  'module',
		  'plugin',
		  'template'
		);
	}

	/**
	 * Check if a language file has been discovered already
	 *
	 * @param   string $languageFileName Language file name
	 *
	 * @return bool
	 */
	public static function isLanguageFileAlreadyDiscovered($languageFileName)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('1')
		  ->from(NenoContentElementLanguageFile::getDbTable())
		  ->where('filename = ' . $db->quote($languageFileName));

		$db->setQuery($query);
		$result = $db->loadResult();

		return $result == 1;
	}

	/**
	 * Get all the tables of the component that matches with the Joomla naming convention.
	 *
	 * @param   NenoContentElementGroup $group             Component name
	 * @param   string                  $tablePattern      Table Pattern
	 * @param   bool                    $includeDiscovered Included tables that have been discovered already
	 *
	 * @return array
	 */
	public static function getComponentTables(NenoContentElementGroup $group, $tablePattern = NULL, $includeDiscovered = true)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db     = JFactory::getDbo();
		$tables = $group->isOtherGroup() ? NenoHelperBackend::getTablesNotDiscovered() : $db->getComponentTables($tablePattern === NULL ? $group->getGroupName() : $tablePattern);

		$result      = array();
		$tablesCount = count($tables);

		for ($i = 0; $i < $tablesCount; $i++)
		{
			// Get Table name
			$tableName     = self::unifyTableName($tables[$i]);
			$table         = NULL;
			$tablesIgnored = self::getDoNotTranslateTables();

			if (!in_array($tableName, $tablesIgnored))
			{
				if (!self::isTableAlreadyDiscovered($tableName))
				{
					$table = self::createTableInstance($tableName, $group);
				}
				elseif ($includeDiscovered)
				{
					$table = NenoContentElementTable::load(array(
					  'table_name' => $tableName,
					  'group_id'   => $group->getId()
					));
				}
			}

			if (!empty($table))
			{
				$result[] = $table;
			}
		}

		// If there's no results, let's get the tables that already belongs to this group
		if (empty($result))
		{
			$result = NenoContentElementTable::load(array('group_id' => $group->getId()));
		}

		return $result;
	}

	/**
	 * Create Table instance
	 *
	 * @param string                  $tableName Table name
	 * @param NenoContentElementGroup $group     Group
	 *
	 * @return NenoContentElementTable
	 */
	public static function createTableInstance($tableName, NenoContentElementGroup $group)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();
		// Create an array with the table information
		$tableData = array(
		  'tableName'  => $tableName,
		  'primaryKey' => $db->getPrimaryKey($tableName),
		  'translate'  => !$group->isOtherGroup(),
		  'group'      => $group
		);

		// Create ContentElement object
		$table = new NenoContentElementTable($tableData);

		// Get all the columns a table contains
		$fields = $db->getTableColumns($table->getTableName());
		$table  = NenoHelperBackend::createFieldInstances($fields, $table);

		return $table;
	}

	/**
	 * Converts a table name to the Joomla table naming convention: #__table_name
	 *
	 * @param   string $tableName Table name
	 *
	 * @return mixed
	 */
	public static function unifyTableName($tableName)
	{
		$prefix         = JFactory::getDbo()->getPrefix();
		$patternOptions = array($prefix);

		$languages = self::getLanguages(false);

		foreach ($languages as $language)
		{
			$patternOptions[] = '#___' . self::cleanLanguageTag($language->lang_code) . '_';
		}

		$patternOptions[] = '#__';

		return '#__' . preg_replace('/^(' . implode('|', $patternOptions) . ')/', '', $tableName);
	}

	/**
	 * Get all the tables that should be ignored
	 *
	 * @return array
	 */
	public static function getDoNotTranslateTables()
	{
		return array(
		  '#__contentitem_tag_map',
		  '#__content_frontpage',
		  '#__content_rating',
		  '#__content_types',
		  '#__finder_links',
		  '#__finder_links_terms0',
		  '#__finder_links_terms1',
		  '#__finder_links_terms2',
		  '#__finder_links_terms3',
		  '#__finder_links_terms4',
		  '#__finder_links_terms5',
		  '#__finder_links_terms6',
		  '#__finder_links_terms7',
		  '#__finder_links_terms8',
		  '#__finder_links_terms9',
		  '#__finder_links_termsa',
		  '#__finder_links_termsb',
		  '#__finder_links_termsc',
		  '#__finder_links_termsd',
		  '#__finder_links_termse',
		  '#__finder_links_termsf',
		  '#__finder_taxonomy',
		  '#__finder_taxonomy_map',
		  '#__finder_types',
		  '#__messages',
		  '#__messages_cfg',
		  '#__modules_menu',
		  '#__modules',
		  '#__postinstall_messages',
		  '#__redirect_links',
		  '#__users',
		  '#__banner_clients',
		  '#__banner_tracks',
		  '#__extensions',
		  '#__overrider',
		  '#__template_styles',
		  '#__ucm_history',
		  '#__usergroups',
		  '#__user_keys',
		  '#__user_notes',
		  '#__user_profiles',
		  '#__user_usergroup_map',
		  '#__viewlevels',
		  '#__menu',
		  '#__menu_types',
		  '#__languages',
		);
	}

	/**
	 * Check if a table has been already discovered.
	 *
	 * @param   string $tableName Table name
	 *
	 * @return bool
	 */
	public static function isTableAlreadyDiscovered($tableName)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('1')
		  ->from(NenoContentElementTable::getDbTable())
		  ->where('table_name LIKE ' . $db->quote(self::unifyTableName($tableName)));

		$db->setQuery($query);
		$result = $db->loadResult();

		return $result == 1;
	}

	/**
	 * Get translation method for languages
	 *
	 * @return array
	 */
	public static function getTranslationMethodsForLanguages()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'DISTINCT lang',
			  'translation_method_id',
			  'ordering'
			)
		  )
		  ->from('#__neno_content_language_defaults')
		  ->where('lang <> \'\'');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Set setup state
	 *
	 * @param   string $message Message
	 * @param   int    $level   Level
	 * @param   string $type    Message type
	 *
	 * @return void
	 */
	public static function setSetupState($message, $level = 1, $type = 'info')
	{
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$percent = NenoSettings::get('current_percent');

		if ($level == 1 && $type == 'info')
		{
			$percent = $percent + NenoSettings::get('percent_per_extension');
			NenoSettings::set('current_percent', $percent);
		}
		elseif ($level == '3.1' && $type == 'info')
		{
			$level   = 3;
			$percent = $percent + NenoSettings::get('percent_per_content_element');
			NenoSettings::set('current_percent', $percent);
		}

		$query
		  ->select('1')
		  ->from('#__neno_installation_messages')
		  ->where('message = ' . $db->quote($message));

		$db->setQuery($query);
		$result = $db->loadResult();

		if (empty($result))
		{
			$query
			  ->clear()
			  ->insert('#__neno_installation_messages')
			  ->columns(
				array(
				  'message',
				  'type',
				  'percent',
				  'level'
				)
			  )
			  ->values($db->quote($message) . ',' . $db->quote($type) . ',' . (int) $percent . ',' . (int) $level);

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Concatenate a string to an array of strings
	 *
	 * @param   string $string  String to concatenate
	 * @param   array  &$array  Array of strings
	 * @param   bool   $prepend True if the string will be at beginning, false if it will be at the end.
	 *
	 * @return void
	 */
	public static function concatenateStringToStringArray($string, &$array, $prepend = true)
	{
		$arrayCount = count($array);
		for ($i = 0; $i < $arrayCount; $i++)
		{
			if ($prepend)
			{
				$array[$i] = $string . $array[$i];
			}
			else
			{
				$array[$i] = $array[$i] . $string;
			}
		}
	}

	/**
	 * Get the name of the file using its path
	 *
	 * @param   string $filePath File path including the file name
	 *
	 * @return string
	 */
	public static function getFileName($filePath)
	{
		jimport('joomla.filesystem.file');
		$pathParts = explode('/', $filePath);

		return JFile::stripExt($pathParts[count($pathParts) - 1]);
	}

	/**
	 * Output HTML code for translation progress bar
	 *
	 * @param   stdClass $wordCount   Strings translated, queued to be translated, out of sync, not translated & total
	 * @param   bool     $enabled     Render as enabled
	 * @param   bool     $showPercent Show percent flag
	 *
	 * @return string
	 */
	public static function renderWordCountProgressBar($wordCount, $enabled = true, $showPercent = false)
	{
		$displayData                     = new stdClass;
		$displayData->enabled            = $enabled;
		$displayData->wordCount          = $wordCount;
		$displayData->widthTranslated    = ($wordCount->total) ? (100 * $wordCount->translated / $wordCount->total) : (0);
		$displayData->widthQueued        = ($wordCount->total) ? (100 * $wordCount->queued / $wordCount->total) : (0);
		$displayData->widthChanged       = ($wordCount->total) ? (100 * $wordCount->changed / $wordCount->total) : (0);
		$displayData->widthNotTranslated = ($wordCount->total) ? (100 * $wordCount->untranslated / $wordCount->total) : (0);
		$displayData->showPercent        = $showPercent;

		// If total is 0 (there is no content to translate) then mark everything as translated
		if ($wordCount->total == 0)
		{
			$displayData->widthTranslated = 100;
		}

		return JLayoutHelper::render('wordcountprogressbar', $displayData, JPATH_NENO_LAYOUTS);
	}

	/**
	 * Return all groups.
	 *
	 * @param   bool $loadExtraData       Load Extra data flag
	 * @param   bool $avoidDoNotTranslate Don't return fields/keys marked as Don't translate
	 *
	 * @return  array
	 */
	public static function getGroups($loadExtraData = true, $avoidDoNotTranslate = false)
	{
		$cacheId   = NenoCache::getCacheId(__FUNCTION__, array(1));
		$cacheData = NenoCache::getCacheData($cacheId);

		if ($cacheData === NULL)
		{
			// Create a new query object.
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$subquery1   = $db->getQuery(true);
			$arrayWhere1 = array('t.group_id = g.id');

			if ($avoidDoNotTranslate)
			{
				$arrayWhere1[] = 't.translate = 1';
			}

			$subquery1
			  ->select('1')
			  ->from(' #__neno_content_element_tables AS t')
			  ->where($arrayWhere1);

			$subquery2 = $db->getQuery(true);
			$subquery2
			  ->select('1')
			  ->from('#__neno_content_element_language_files AS lf')
			  ->where('lf.group_id = g.id');

			$query
			  ->select('g.id')
			  ->from('`#__neno_content_element_groups` AS g')
			  ->where(
				array(
				  'EXISTS (' . (string) $subquery1 . ')',
				  'EXISTS (' . (string) $subquery2 . ')',
				  '(NOT EXISTS (' . (string) $subquery1 . ') AND NOT EXISTS (' . (string) $subquery2 . ') AND NOT EXISTS(SELECT 1 FROM #__neno_content_element_groups_x_extensions AS ge WHERE g.id = ge.group_id))'
				), 'OR')
			  ->order(
				array(
				  'IFNULL((SELECT DISTINCT 1 FROM #__neno_content_element_groups_x_translation_methods AS gtm WHERE gtm.group_id = g.id) ,0)',
				  'group_name'
				)
			  );

			$db->setQuery($query);
			$groups = $db->loadObjectList();

			$countGroups = count($groups);

			for ($i = 0; $i < $countGroups; $i++)
			{
				$group              = NenoContentElementGroup::getGroup($groups[$i]->id, $loadExtraData);
				$translationMethods = $group->getAssignedTranslationMethods();

				if ($avoidDoNotTranslate && empty($translationMethods))
				{
					unset ($groups[$i]);
					continue;
				}

				$groups[$i] = $group;
			}

			NenoCache::setCacheData($cacheId, $groups);
			$cacheData = $groups;
		}

		return $cacheData;
	}

	/**
	 * Return all translation methods used on any string.
	 *
	 * @param   string $type What the data is for
	 *
	 * @return  array
	 */
	public static function getTranslationMethods($type = 'list')
	{
		// Create a new query object.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'id',
			  'name_constant'
			)
		  )
		  ->from('`#__neno_translation_methods`');

		$db->setQuery($query);
		$methods            = $db->loadObjectList('id');
		$translationMethods = array();

		if ($type != 'list')
		{
			$translationMethods = $methods;
		}
		else
		{
			foreach ($methods as $id => $methodData)
			{
				$translationMethods[$id] = JText::_($methodData->name_constant);
			}
		}

		return $translationMethods;
	}

	/**
	 * Convert HTML code into text with HTML entities
	 *
	 * @param   string $string   HTML code
	 * @param   int    $truncate Maximum length of the output text
	 *
	 * @return string
	 */
	public static function html2text($string, $truncate = NULL)
	{
		$string = htmlspecialchars($string);
		$ending = '';

		if ($truncate !== NULL)
		{
			$parts      = preg_split('/([\s\n\r]+)/', $string, NULL, PREG_SPLIT_DELIM_CAPTURE);
			$partsCount = count($parts);
			$length     = 0;
			$lastPart   = 0;

			for (; $lastPart < $partsCount; ++$lastPart)
			{
				$length += mb_strlen($parts[$lastPart]);

				if ($length - 3 > $truncate)
				{
					$ending = '...';
					break;
				}
			}

			if (count($parts) == 1)
			{
				$string = substr($string, 0, $truncate) . $ending;
			}
			else
			{
				$string = implode(array_slice($parts, 0, $lastPart)) . $ending;
			}
		}

		return $string;
	}

	/**
	 * Load Original from a translation
	 *
	 * @param   int $translationId   Translation Id
	 * @param   int $translationType Translation Type (DB Content or Language String)
	 *
	 * @return string|null
	 */
	public static function getTranslationOriginalText($translationId, $translationType)
	{
		$cacheId    = NenoCache::getCacheId(__FUNCTION__, func_get_args());
		$cachedData = NenoCache::getCacheData($cacheId);

		if ($cachedData === NULL)
		{
			/* @var $db NenoDatabaseDriverMysqlX */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$translationElementId = self::getContentIdByTranslationId($translationId);

			// If the translation comes from database content, let's load it
			if ($translationType == NenoContentElementTranslation::DB_STRING)
			{
				list($fieldName, $tableName) = self::getOriginalText($translationElementId);
				$whereValues = self::getWhereValuesForTranslation($translationId);
				$string      = self::getFieldContentFromTranslationData($fieldName, $tableName, $whereValues);
			}
			else
			{
				$query
				  ->clear()
				  ->select('string')
				  ->from(NenoContentElementLanguageString::getDbTable())
				  ->where('id = ' . $translationElementId);

				$db->setQuery($query);
				$string = $db->loadResult();
			}

			$string = $string === NULL ? '' : $string;

			NenoCache::setCacheData($cacheId, $string);
			$cachedData = $string;
		}

		return $cachedData;
	}

	public static function getContentIdByTranslationId($translationId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			/* @var $db NenoDatabaseDriverMysqlX */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->select('content_id')
			  ->from('#__neno_content_element_translations')
			  ->where('id = ' . $translationId);

			$db->setQuery($query);
			$translationElementId = (int) $db->loadResult();

			NenoCache::setCacheData($cacheId, $translationElementId);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getOriginalText($translationElementId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->clear()
			  ->select(
				array(
				  'f.field_name',
				  't.table_name'
				)
			  )
			  ->from('`#__neno_content_element_fields` AS f')
			  ->innerJoin('`#__neno_content_element_tables` AS t ON f.table_id = t.id')
			  ->where('f.id = ' . $translationElementId);

			$db->setQuery($query);
			$row = $db->loadRow();
			NenoCache::setCacheData($cacheId, $row);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getWhereValuesForTranslation($translationId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->clear()
			  ->select(
				array(
				  'f.field_name',
				  'ft.value',
				)
			  )
			  ->from('`#__neno_content_element_fields_x_translations` AS ft')
			  ->innerJoin('`#__neno_content_element_fields` AS f ON f.id = ft.field_id')
			  ->where('ft.translation_id = ' . $translationId);

			$db->setQuery($query);
			$whereValues = $db->loadAssocList('field_name');

			NenoCache::setCacheData($cacheId, $whereValues);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getFieldContentFromTranslationData($fieldName, $tableName, $whereValues)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->clear()
			  ->select($db->quoteName($fieldName))
			  ->from($tableName);

			foreach ($whereValues as $whereField => $where)
			{
				$query->where($db->quoteName($whereField) . ' = ' . $db->quote($where['value']));
			}

			$db->setQuery($query);
			$string = $db->loadResult();

			NenoCache::setCacheData($cacheId, $string);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Convert translation method name to id
	 *
	 * @param   string $translationMethodName Translation method name
	 *
	 * @return int
	 */
	public static function convertTranslationMethodNameToId($translationMethodName)
	{
		$id = 0;

		switch ($translationMethodName)
		{
			case 'manual':
				$id = 1;
				break;
			case 'machine':
				$id = 2;
				break;
			case 'pro':
				$id = 3;
				break;
		}

		return $id;
	}

	/**
	 * Convert translation method id to name
	 *
	 * @param   string $translationId Translation method ID
	 *
	 * @return int
	 */
	public static function convertTranslationMethodIdToName($translationId)
	{
		$name = 0;

		switch ($translationId)
		{
			case 1:
				$name = 'manual';
				break;
			case 2:
				$name = 'machine';
				break;
			case 3:
				$name = 'professional';
				break;
		}

		return $name;
	}

	/**
	 * Load translation methods
	 *
	 * @return array
	 */
	public static function loadTranslationMethods()
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, array());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->select('*')
			  ->from('#__neno_translation_methods');

			$db->setQuery($query);
			$rows = $db->loadObjectList('id');

			NenoCache::setCacheData($cacheId, $rows);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Create the menu structure for a particular language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return void
	 */
	public static function createMenuStructureForLanguage($languageTag)
	{
		$db             = JFactory::getDbo();
		$query          = $db->getQuery(true);
		$sourceLanguage = NenoSettings::get('source_language');

		// Get menutypes for source language
		$query
		  ->select('DISTINCT mt.*')
		  ->from('#__menu AS m')
		  ->innerJoin('#__menu_types AS mt ON mt.menutype = m.menutype')
		  ->where('m.language = ' . $db->quote($sourceLanguage));

		$db->setQuery($query);
		$menuTypes = $db->loadObjectList();

		foreach ($menuTypes as $menuType)
		{
			// Create each menu type
			$newMenuType = self::createMenu($languageTag, $menuType, $sourceLanguage);

			// For each menu items, create its copy in the new language
			$query
			  ->clear()
			  ->select('m.*')
			  ->from('#__menu AS m')
			  ->where('m.menutype = ' . $db->quote($menuType->menutype));

			$db->setQuery($query);
			$menuItems = $db->loadObjectList();

			foreach ($menuItems as $menuItem)
			{
				$newMenuItem = clone $menuItem;
				unset($newMenuItem->id);
				$newMenuItem->menutype = $newMenuType->menutype;
				$newMenuItem->alias    = JFilterOutput::stringURLSafe($newMenuItem->alias . '-' . $languageTag);
				$newMenuItem->language = $languageTag;

				// If the menu item has been inserted properly, let's execute some actions
				if ($db->insertObject('#__menu', $newMenuItem, 'id'))
				{
					// Assign all the modules to this item
					$queryString = 'INSERT INTO #__modules_menu (moduleid,menuid) SELECT moduleid,' . $db->quote($newMenuItem->id) . ' FROM  #__modules_menu WHERE menuid = ' . $db->quote($menuItem->id);
					$db->setQuery($queryString);
					$db->execute();

					// Add this menu to the association
					$queryString = 'INSERT INTO #__associations (`id`,`context`,`key`) SELECT ' . $db->quote($newMenuItem->id) . ', ' . $db->quote('com_menus.item') . ', `key` FROM #__associations WHERE id =' . $db->quote($menuItem->id) . ' AND context = ' . $db->quote('com_menus.item');
					$db->setQuery($queryString);
					$db->execute();
				}
			}
		}
	}

	/**
	 * Create a new menu
	 *
	 * @param   string   $language        Language
	 * @param   stdClass $defaultMenuType Default language menu type
	 * @param   string   $defaultLanguage Default language
	 *
	 * @return stdClass
	 */
	protected static function createMenu($language, stdClass $defaultMenuType, $defaultLanguage)
	{
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);
		$menuType = $defaultMenuType->menutype . '-' . strtolower($language);
		if (!isset(self::$menuModuleReplicated[$language]))
		{
			self::$menuModuleReplicated[$language] = array();
		}

		$query
		  ->select('*')
		  ->from('#__menu_types')
		  ->where('menutype = ' . $db->quote($menuType));

		$db->setQuery($query);
		$item = $db->loadObject();

		if (empty($item))
		{
			$query
			  ->clear()
			  ->insert('#__menu_types')
			  ->columns(
				array(
				  'menutype',
				  'title'
				)
			  )
			  ->values($db->quote($menuType) . ', ' . $db->quote($defaultMenuType->title . '(' . $language . ')'));

			$db->setQuery($query);
			$db->execute();

			$query
			  ->select('*')
			  ->from('#__menu_types')
			  ->where('menutype = ' . $db->quote($menuType));
			$db->setQuery($query);
			$item = $db->loadObject();

			// Create menu modules

			$query
			  ->clear()
			  ->select('*')
			  ->from('#__modules')
			  ->where(
				array(
				  'module = ' . $db->quote('mod_menu'),
				  'client_id = 0',
				  'params LIKE ' . $db->quote('%' . $defaultMenuType->menutype . '%'),
				  'language = ' . $db->quote($defaultLanguage)
				)
			  );

			$db->setQuery($query);
			$modules = $db->loadObjectList();

			if (!empty($modules))
			{
				foreach ($modules as $module)
				{
					if (!in_array($module->id, self::$menuModuleReplicated[$language]))
					{
						self::$menuModuleReplicated[] = $module->id;
						$previousId                   = $module->id;
						$module->params               = json_decode($module->params, true);

						$module->id                 = 0;
						$module->params['menutype'] = $item->menutype;
						$module->params             = json_encode($module->params);
						$module->language           = $language;
						$module->title              = $module->title . ' (' . $language . ')';

						$db->insertObject('#__modules', $module, 'id');

						// Assigning items
						$query = 'INSERT INTO #__modules_menu (menuid,moduleid) SELECT menuid,' . $db->quote($module->id) . ' FROM  #__modules_menu WHERE moduleid = ' . $db->quote($previousId);
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		return $item;
	}

	/**
	 * Init menu structure process creation
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected static function initMenuStructureCreation()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$defaultLanguage = NenoSettings::get('source_language');

		// Delete all the menus trashed
		$query
		  ->delete('#__menu')
		  ->where('published = -2');

		$db->setQuery($query);
		$db->execute();

		// Delete all the associations left
		$query
		  ->clear()
		  ->delete('a USING #__associations AS a')
		  ->where(
			array(
			  'context = ' . $db->quote('com_menus.item'),
			  'NOT EXISTS (SELECT 1 FROM #__menu AS m WHERE a.id = m.id)'
			)
		  );

		$db->setQuery($query);
		$db->execute();

		// Delete associations with just one item
		$query
		  ->clear()
		  ->select('DISTINCT id')
		  ->from('#__associations')
		  ->group('`key`')
		  ->having('COUNT(*) = 1');

		$db->setQuery($query);
		$ids = $db->loadArray();

		$query
		  ->clear()
		  ->delete('#__associations')
		  ->where(
			array(
			  'context = ' . $db->quote('com_menus.item'),
			  'id IN (' . implode(', ', $db->quote($ids)) . ')'
			)
		  );

		$db->setQuery($query);
		$db->execute();

		// Set to source language all the modules that manage content
		$query
		  ->clear()
		  ->update('#__modules')
		  ->set('language = ' . $db->quote($defaultLanguage))
		  ->where(
			array(
			  'published = 1',
			  'module IN  ( ' . $db->quote(self::getModuleTypesNeedToBeDuplicated()) . ')',
			  'client_id = 0',
			  'language  = ' . $db->quote('*')
			)
		  );
		$db->setQuery($query);
		$db->execute();

		// Set all the menus items from '*' to default language
		$query
		  ->clear()
		  ->update('#__menu AS m')
		  ->set('language = ' . $db->quote($defaultLanguage))
		  ->where(
			array(
			  'client_id = 0',
			  'level <> 0',
			  'language = ' . $db->quote('*')
			)
		  );

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Replicate module for a particular language
	 *
	 * @param integer $moduleId    Module ID
	 * @param string  $languageTag Language tag
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public static function replicateModule($moduleId, $languageTag)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$defaultLanguage = NenoSettings::get('source_language');

		// Get all the modules with the language as default
		$query
		  ->select('m.*')
		  ->from('#__modules AS m')
		  ->where('m.id = ' . $db->quote($moduleId));

		$db->setQuery($query);
		$sourceModule     = $db->loadObject();
		$module           = clone $sourceModule;
		$module->id       = 0;
		$module->title    = $sourceModule->title . ' (' . $languageTag . ')';
		$module->language = $languageTag;
		$module           = self::setParamsForModule($module);

		// If the module has been inserted correctly, let's assign it
		if ($db->insertObject('#__modules', $module, 'id'))
		{
			$insert      = false;
			$insertQuery = $db->getQuery(true);
			$insertQuery
			  ->clear()
			  ->insert('#__modules_menu')
			  ->columns(
				array(
				  'moduleid',
				  'menuid'
				)
			  );

			// Check if the previous module is assigned to all
			$query
			  ->clear()
			  ->select('1')
			  ->from('#__modules_menu')
			  ->where(
				array(
				  'moduleid = ' . $sourceModule->id,
				  'menuid = 0'
				)
			  );

			$db->setQuery($query);
			$result = $db->loadResult();

			if ($result == 1)
			{
				$insertQuery->values($module->id . ', 0');
				$insert = true;
			}
			else
			{
				// Check if the module has assigned selected
				$query
				  ->clear()
				  ->select('DISTINCT m2.id')
				  ->from('#__modules_menu AS mm')
				  ->innerJoin('#__menu AS m1 ON mm.menuid = m1.id')
				  ->innerJoin('#__associations AS a1 ON a1.id = m1.id')
				  ->innerJoin('#__associations AS a2 ON a1.key = a2.key')
				  ->innerJoin('#__menu AS m2 ON a2.id = m2.id')
				  ->where(
					array(
					  'a1.context = ' . $db->quote('com_menus.item'),
					  'a2.context = ' . $db->quote('com_menus.item'),
					  'a1.id <> a2.id',
					  'm1.client_id = 0',
					  'm1.level <> 0',
					  'm1.published <> -2',
					  'm2.client_id = 0',
					  'm2.level <> 0',
					  'm2.published <> -2',
					  'mm.moduleid = ' . $sourceModule->id,
					  'm1.language = ' . $db->quote($defaultLanguage),
					  'm2.language = ' . $db->quote($languageTag)
					)
				  );

				$db->setQuery($query);
				$menuIds = $db->loadArray();

				if (!empty($menuIds))
				{
					$insert = true;

					foreach ($menuIds as $menuId)
					{
						$insertQuery->values($module->id . ',' . $menuId);
					}
				}
			}

			if ($insert)
			{
				$db->setQuery($insertQuery);
				$db->execute();
			}
		}
	}

	/**
	 * Depends of the module type, it will set special params for this module
	 *
	 * @param stdClass $module
	 *
	 * @return stdClass
	 */
	protected static function setParamsForModule($module)
	{
		$moduleSourceParams = json_decode($module->params);
		switch ($module->module)
		{
			case 'mod_menu':
				$menusRelated                 = NenoHelper::getMenusRelated($moduleSourceParams->menutype);
				$moduleSourceParams->menutype = $menusRelated[$module->language];
				break;
		}

		$module->params = json_encode($moduleSourceParams);

		return $module;
	}

	/**
	 * Fixing language level issue
	 *
	 * @return void
	 */
	protected static function fixLanguagesLevel()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$defaultLanguage = NenoSettings::get('source_language');
		$languages       = self::getTargetLanguages();

		foreach ($languages as $language)
		{
			if ($language->lang_code !== $defaultLanguage)
			{
				$query
				  ->clear()
				  ->select('m1.*')
				  ->from('#__menu AS m1')
				  ->where(
					array(
					  'client_id = 0',
					  'level <> 0',
					  'level <> 1',
					  'EXISTS (SELECT 1 FROM #__menu AS m2 WHERE m2.id = m1.parent_id AND m2.language <> m1.language)'
					)
				  );

				$db->setQuery($query);
				$menuItemsThatNeedsToBeFixed = $db->loadObjectList();

				foreach ($menuItemsThatNeedsToBeFixed as $menuItem)
				{
					$query
					  ->clear()
					  ->select('m2.id')
					  ->from('#__menu AS m1')
					  ->innerJoin('#__associations AS a1 ON a1.id = m1.id')
					  ->innerJoin('#__associations AS a2 ON a1.key = a2.key')
					  ->innerJoin('#__menu AS m2 ON a2.id = m2.id')
					  ->where(
						array(
						  'a1.context = ' . $db->quote('com_menus.item'),
						  'a2.context = ' . $db->quote('com_menus.item'),
						  'a1.id <> a2.id',
						  'm1.id = ' . $menuItem->parent_id,
						  'm2.language = ' . $db->quote($menuItem->language)
						)
					  );

					$db->setQuery($query);
					$parentId = (int) $db->loadResult();

					if (!empty($parentId))
					{
						$menuItem->parent_id = $parentId;
						$db->updateObject('#__menu', $menuItem, 'id');
					}
				}
			}
		}
	}

	/**
	 * Get Menu associations per menu type
	 *
	 * @return array
	 */
	protected static function getMenuAssociations()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db               = JFactory::getDbo();
		$query            = $db->getQuery(true);
		$menuAssociations = array();

		$query
		  ->clear()
		  ->select('DISTINCT m1.menutype AS m1')
		  ->from('#__associations a1')
		  ->innerJoin('#__menu AS m1 ON a1.id = m1.id')
		  ->innerJoin('#__associations AS a2 ON a1.key = a2.key')
		  ->innerJoin('#__menu AS m2 ON a2.id = m2.id')
		  ->where(
			array(
			  'a1.context = ' . $db->quote('com_menus.item'),
			  'a2.context = ' . $db->quote('com_menus.item'),
			  'a1.id <> a2.id',
			  'm1.client_id = 0',
			  'm1.level <> 0',
			  'm1.published <> -2',
			  'm2.client_id = 0',
			  'm2.level <> 0',
			  'm2.published <> -2',
			)
		  );

		$db->setQuery($query);
		$menuTypes = $db->loadArray();

		foreach ($menuTypes as $menuType)
		{
			$query
			  ->clear()
			  ->select(
				array(
				  'DISTINCT m2.menutype',
				  'm2.language'
				)
			  )
			  ->from('#__associations a1')
			  ->innerJoin('#__menu AS m1 ON a1.id = m1.id')
			  ->innerJoin('#__associations AS a2 ON a1.key = a2.key')
			  ->innerJoin('#__menu AS m2 ON a2.id = m2.id')
			  ->where(
				array(
				  'a1.context = ' . $db->quote('com_menus.item'),
				  'a2.context = ' . $db->quote('com_menus.item'),
				  'a1.id <> a2.id',
				  'm1.client_id = 0',
				  'm1.level <> 0',
				  'm1.published <> -2',
				  'm2.client_id = 0',
				  'm2.level <> 0',
				  'm2.published <> -2',
				  'm1.menutype = ' . $db->quote($menuType)
				)
			  );

			$db->setQuery($query);
			$menuAssociations[$menuType] = $db->loadAssocList('language');
		}

		return $menuAssociations;
	}

	/**
	 * Creates a new menu type item per language
	 *
	 * @param stdClass $menuItem Menu item to duplicate
	 *
	 * @throws Exception
	 */
	protected static function duplicateMenuItem($menuItem)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$languages       = self::getTargetLanguages(false);
		$defaultLanguage = NenoSettings::get('source_language');

		if (!isset(self::$menuAssociations[$menuItem->menutype]))
		{
			self::$menuAssociations[$menuItem->menutype] = array();
		}

		$associations = array();

		foreach ($languages as $language)
		{
			if ($language->lang_code !== $menuItem->language)
			{
				self::$menuItemsCreated[$language->lang_code] = array();

				// If there's no menu associated
				if (empty(self::$menuAssociations[$menuItem->menutype][$language->lang_code]))
				{
					if (!isset(self::$menuAssociations[$menuItem->menutype][$language->lang_code]))
					{
						self::$menuAssociations[$menuItem->menutype][$language->lang_code] = array();
					}

					$newMenuType           = new stdClass;
					$newMenuType->menutype = $menuItem->menutype;
					$newMenuType->title    = $menuItem->menutype;
					$newMenuType           = self::createMenu($language->lang_code, $newMenuType, $defaultLanguage);

					// If the menu has been inserted properly, let's save into the data structure
					if (!empty($newMenuType))
					{
						self::$menuAssociations[$menuItem->menutype][$language->lang_code]['menutype'] = $newMenuType->menutype;
						self::$menuAssociations[$menuItem->menutype][$language->lang_code]['language'] = $language->lang_code;
					}
				}

				$newMenuItem = clone $menuItem;
				unset($newMenuItem->id);
				$newMenuItem->menutype = self::$menuAssociations[$menuItem->menutype][$language->lang_code]['menutype'];
				$newMenuItem->alias    = JFilterOutput::stringURLSafe($newMenuItem->alias . '-' . $language->lang_code);
				$newMenuItem->language = $language->lang_code;

				// If the menu item has been inserted properly, let's execute some actions
				if ($db->insertObject('#__menu', $newMenuItem, 'id'))
				{
					self::$menuItemsCreated[$language->lang_code][] = $newMenuItem->id;

					// Assign all the modules to this item
					$query = 'INSERT INTO #__modules_menu (moduleid,menuid) SELECT moduleid,' . $db->quote($newMenuItem->id) . ' FROM  #__modules_menu WHERE menuid = ' . $db->quote($menuItem->id);
					$db->setQuery($query);
					$db->execute();
					$associations[] = $newMenuItem->id;
				}
			}
		}

		self::duplicateModulesForMenuItem($menuItem);

		self::createAssociations($associations, $menuItem);
	}

	/**
	 * Duplicate all the modules assigned to a menu item
	 *
	 * @param stdClass $menuItem Menu item object
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected static function duplicateModulesForMenuItem($menuItem)
	{
		$modules = self::getModulesForMenuItemForAllLanguages($menuItem);

		if (!empty($modules))
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->insert('#__modules_menu')
			  ->columns(
				array(
				  'moduleid',
				  'menuid'
				)
			  );

			foreach (self::$menuItemsCreated as $language => $newMenuItems)
			{
				foreach ($modules as $module)
				{
					$query = self::createModuleInstance($language, $module, $newMenuItems, $query);
				}
			}

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Create a module instance and assign it to the menu items
	 *
	 * @param string         $language     Module language
	 * @param stdClass       $module       Module data
	 * @param array          $newMenuItems Menu items for the module to be assigned
	 * @param JDatabaseQuery $query        Database query where the data will be saved
	 *
	 * @return JDatabaseQuery
	 */
	protected static function createModuleInstance($language, $module, $newMenuItems, $query)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db         = JFactory::getDbo();
		$previousId = $module->id;

		if (!isset(self::$modulesDuplicated[$previousId . $language]) && $module->language != $language)
		{
			unset($module->id);
			$module->language = $language;
			$module->title    = $module->title . '(' . $language . ')';

			if ($db->insertObject('#__modules', $module, 'id'))
			{
				self::$modulesDuplicated[$previousId . $language] = $module->id;
			}
		}

		foreach ($newMenuItems as $newMenuItem)
		{
			$query->values(self::$modulesDuplicated[$previousId . $language] . ',' . $newMenuItem->id);
		}

		return $query;
	}

	/**
	 * Get all the modules marked as '*' for a menu item
	 *
	 * @param stdClass $menuItem
	 *
	 * @return array
	 */
	protected static function getModulesForMenuItemForAllLanguages($menuItem)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get all the modules assigned to this menu item using a different language from *
		$query
		  ->clear()
		  ->select('m.*')
		  ->from('#__modules AS m')
		  ->innerJoin('#__modules_menu AS mm ON m.id = mm.moduleid')
		  ->where(
			array(
			  'mm.menuid = ' . (int) $menuItem->id,
			  'm.language <> ' . $db->quote('*')
			)
		  );

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Create all needed associations for a particular menu item
	 *
	 * @param array    $associations associations to create
	 * @param stdClass $menuItem     Menu item
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected static function createAssociations($associations, $menuItem)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db          = JFactory::getDbo();
		$query       = $db->getQuery(true);
		$insert      = false;
		$insertQuery = $db->getQuery(true);
		$insertQuery
		  ->insert('#__associations')
		  ->columns(
			array(
			  'id',
			  $db->quoteName('context'),
			  $db->quoteName('key')
			)
		  );

		$query
		  ->clear()
		  ->select($db->quoteName('key', 'associationKey'))
		  ->from('#__associations')
		  ->where(
			array(
			  'id IN (' . implode(',', array_merge($associations, array($menuItem->id))) . ')',
			  'context = ' . $db->quote('com_menus.item')
			)
		  );

		$db->setQuery($query);
		$associationKey = $db->loadResult();

		if (empty($associationKey))
		{
			if (!in_array($menuItem->id, $associations))
			{
				$associations[] = $menuItem->id;
			}

			$associations   = array_unique($associations);
			$associationKey = md5(json_encode($associations));
		}
		else
		{
			$query
			  ->clear()
			  ->select('id')
			  ->from('#__associations')
			  ->where($db->quoteName('key') . ' = ' . $db->quote($associationKey));

			$db->setQuery($query);
			$alreadyInserted = $db->loadArray();
			$associations    = array_diff($associations, $alreadyInserted);
		}

		foreach ($associations as $association)
		{
			$insertQuery->values($association . ',' . $db->quote('com_menus.item') . ',' . $db->quote($associationKey));
			$insert = true;
		}

		if ($insert)
		{
			$db->setQuery($insertQuery);
			$db->execute();
		}
	}

	/**
	 * Create menu structure
	 *
	 * @param   string  $event  Event which triggered the method
	 *
	 * @return  mixed   True if no event, a menu list if event == fixMenus
	 */
	public static function createMenuStructure($event = '')
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		self::initMenuStructureCreation();

		$query
		  ->clear()
		  ->select(
			array(
			  'm.*'
			)
		  )
		  ->from('#__menu_types AS mt')
		  ->leftJoin('#__menu AS m ON mt.menutype = m.menutype')
		  ->where(
			array(
			  'NOT EXISTS(SELECT 1 FROM #__associations AS a WHERE a.id = m.id AND a.`context` = ' . $db->quote('com_menus.item') . ')',
			  'client_id = 0',
			  'level <> 0',
			  'published <> -2'
			)
		  )
		  ->order('level');

		$db->setQuery($query);
		$nonAssociatedMenuItems = $db->loadObjectList();
		self::$menuAssociations = self::getMenuAssociations();

		foreach ($nonAssociatedMenuItems as $key => $menuItem)
		{
			self::duplicateMenuItem($menuItem);
		}

		// Fixing levels issue
		self::fixLanguagesLevel();

		// Once we finish restructuring menus, let's rebuild them
		$menuTable = new JTableMenu($db);
		$menuTable->rebuild();

		return ($event == 'fixMenus') ? $nonAssociatedMenuItems : true;
	}

	/**
	 * Get an array indexed by language code of the target languages
	 *
	 * @param   boolean $published Weather or not only the published language should be loaded
	 *
	 * @return array objectList
	 */
	public static function getTargetLanguages($published = true)
	{
		// Load all published languages
		$languages       = self::getLanguages($published);
		$defaultLanguage = NenoSettings::get('source_language');

		// Create a simple array
		$arr = array();

		if (!empty($languages))
		{
			foreach ($languages as $lang)
			{
				// Do not include the default language
				if ($lang->lang_code !== $defaultLanguage)
				{
					$arr[$lang->lang_code] = $lang;
				}
			}
		}

		return $arr;
	}

	/**
	 * Load all published languages on the site
	 *
	 * @param   boolean $published     Weather or not only the published language should be loaded
	 * @param   boolean $includeSource Include source language
	 *
	 * @return array objectList
	 */
	public static function getLanguages($published = true, $includeSource = true)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('*')
		  ->from('#__languages')
		  ->where('lang_code IN(' . implode(',', $db->quote(array_keys(JFactory::getLanguage()
			  ->getKnownLanguages()))) . ')')
		  ->order('ordering');

		if ($published)
		{
			$query->where('published = 1');
		}

		$db->setQuery($query);
		$languages      = $db->loadObjectList('lang_code');
		$sourceLanguage = NenoSettings::get('source_language');

		if (!empty($languages))
		{
			foreach ($languages as $key => $language)
			{
				if (!$includeSource && $key == $sourceLanguage)
				{
					unset($languages[$key]);
				}
				else
				{
					$languages[$key]->isInstalled = self::isCompletelyInstall($language->lang_code);
				}

			}
		}

		return $languages;
	}

	/**
	 * Check if the language is completely installed
	 *
	 * @param   string $language Language tag
	 *
	 * @return bool
	 */
	public static function isCompletelyInstall($language)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->select('1')
			  ->from('#__neno_tasks')
			  ->where(
				array(
				  'task_data LIKE ' . $db->quote('%' . $language . '%'),
				  'task = ' . $db->quote('language')
				)
			  );
			$db->setQuery($query);

			NenoCache::setCacheData($cacheId, $db->loadResult() != 1);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Check if a particular language has errors
	 *
	 * @param   array $language Language data
	 *
	 * @return array
	 */
	public static function getLanguageErrors(array $language)
	{
		$errors = array();

		if (self::isLanguageFileOutOfDate($language['lang_code']))
		{
			$errors[] = JLayoutHelper::render(
			  'fixitbutton',
			  array(
				'message'  => JText::sprintf('COM_NENO_ERRORS_LANGUAGE_OUT_OF_DATE', $language['title']),
				'language' => $language['lang_code'],
				'issue'    => 'language_file_out_of_date'
			  ),
			  JPATH_NENO_LAYOUTS
			);
		}

		if (NenoSettings::get('installation_completed'))
		{
			if (!self::hasContentCreated($language['lang_code']))
			{
				$errors[] = JLayoutHelper::render(
					'fixitbutton',
					array(
						'message'  => JText::sprintf('COM_NENO_ERRORS_LANGUAGE_DOES_NOT_CONTENT_ROW', $language['title']),
						'language' => $language['lang_code'],
						'issue'    => 'content_missing'
					),
					JPATH_NENO_LAYOUTS
				);
			}
		}

		if (NenoSettings::get('installation_completed'))
		{
			$issuesCounter = NenoHelperIssue::getIssuesNumber('#__content', $language['lang_code']);

			if ($issuesCounter !== 0)
			{
				$errors[] = JLayoutHelper::render(
					'fixitbutton',
					array(
						'message'  => JText::sprintf('COM_NENO_ERRORS_CONTENT_FOUND_IN_JOOMLA_TABLES', $language['title']),
						'language' => $language['lang_code'],
						'issue'    => 'content_out_of_neno'
					),
					JPATH_NENO_LAYOUTS
				);
			}
		}

		return $errors;
	}

	/**
	 * Check if a language file is out of date
	 *
	 * @param   string $languageTag Language Tag
	 *
	 * @return bool
	 */
	public static function isLanguageFileOutOfDate($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'u.version',
			  'e.manifest_cache'
			)
		  )
		  ->from('#__extensions AS e')
		  ->innerJoin('#__updates AS u ON u.element = e.element')
		  ->where('e.element = ' . $db->quote('pkg_' . $languageTag));

		$db->setQuery($query);
		$extensionData = $db->loadAssoc();

		if (!empty($extensionData))
		{
			$manifestCacheData = json_decode($extensionData['manifest_cache'], true);

			return version_compare($extensionData['version'], $manifestCacheData['version']) == 1;
		}

		return false;
	}

	/**
	 * Check if the language has a row created into the languages table.
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool
	 */
	public static function hasContentCreated($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('1')
		  ->from('#__languages')
		  ->where('lang_code = ' . $db->quote($languageTag));

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Checks if there are content in other languages
	 *
	 * @param   string $language Language to filter the content
	 *
	 * @return int
	 */
	public static function contentCountInOtherLanguages($language = NULL)
	{
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$defaultLanguage = NenoSettings::get('source_language');
		$content         = 0;

		if ($language !== $defaultLanguage)
		{
			$joomlaTablesUsingLanguageField = array(
			  '#__banners',
			  '#__categories',
			  '#__contact_details',
			  '#__content',
			  '#__finder_links',
			  '#__finder_terms',
			  '#__finder_terms_common',
			  '#__finder_tokens',
			  '#__finder_tokens_aggregate',
			  '#__newsfeeds',
			  '#__tags'
			);

			$unionQueries = array();
			$query->select('COUNT(*) AS counter');

			if ($language === NULL)
			{
				$query->where('language <> ' . $db->quote($defaultLanguage));
			}
			else
			{
				$query->where('language = ' . $db->quote($language));
			}

			foreach ($joomlaTablesUsingLanguageField as $joomlaTableUsingLanguageField)
			{
				$query
				  ->clear('from')
				  ->from($joomlaTableUsingLanguageField);
				$unionQueries[] = (string) $query;
			}

			$query
			  ->clear()
			  ->select('SUM(a.counter)')
			  ->from('((' . implode(') UNION (', $unionQueries) . ')) AS a');

			$db->setQuery($query);

			$content = (int) $db->loadResult();
		}

		return $content;
	}

	/**
	 * Deleting language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool True on success
	 */
	public static function deleteLanguage($languageTag)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Delete all the translations
		$query
		  ->delete('#__neno_content_element_translations')
		  ->where('language = ' . $db->quote($languageTag));
		$db->setQuery($query);
		$db->execute();

		// Delete module
		$query
		  ->clear()
		  ->delete('#__modules')
		  ->where(
			array(
			  'language = ' . $db->quote($languageTag),
			  'module = ' . $db->quote('mod_menu')
			)
		  );
		$db->setQuery($query);
		$db->execute();

		// Delete menu items
		$query
		  ->clear()
		  ->delete('#__menu')
		  ->where(
			array(
			  'language = ' . $db->quote($languageTag),
			  'client_id = 0'
			)
		  );
		$db->setQuery($query);
		$db->execute();

		// Delete menu type
		$query
		  ->clear()
		  ->delete('#__menu_types')
		  ->where('menutype NOT IN (SELECT menutype FROM #__menu)');
		$db->setQuery($query);
		$db->execute();

		// Delete associations
		$query
		  ->clear()
		  ->delete('#__associations')
		  ->where(
			array(
			  'id NOT IN (SELECT id FROM #__menu )',
			  'context = ' . $db->quote('com_menus.item')
			)
		  );
		$db->setQuery($query);
		$db->execute();

		// Delete content
		$query
		  ->clear()
		  ->delete('#__languages')
		  ->where('lang_code = ' . $db->quote($languageTag));
		$db->setQuery($query);
		$db->execute();

		// Drop all the shadow tables
		$shadowTables = preg_grep('/' . preg_quote($db->getPrefix() . '_' . self::cleanLanguageTag($languageTag)) . '/', $db->getNenoTableList());

		foreach ($shadowTables as $shadowTable)
		{
			$db->dropTable($shadowTable);
		}

		// Delete extension(s)
		$installer = JInstaller::getInstance();

		$query
		  ->clear()
		  ->select(
			array(
			  'extension_id',
			  'type'
			)
		  )
		  ->from('#__extensions')
		  ->where('element LIKE ' . $db->quote('%' . $languageTag));

		$db->setQuery($query);
		$extensions = $db->loadAssocList();

		foreach ($extensions as $extension)
		{
			$installer->uninstall($extension['type'], $extension['extension_id']);
		}

		return true;
	}

	/**
	 * Fix language issue
	 *
	 * @param   string $language Language
	 * @param   string $issue    Issue
	 *
	 * @return bool
	 */
	public static function fixLanguageIssues($language, $issue)
	{
		$result = false;
		switch ($issue)
		{
			case 'content_missing':
				if (!self::hasContentCreated($language))
				{
					$result = self::createContentRow($language);
				}
				else
				{
					$result = true;
				}

				break;
			case 'language_file_out_of_date':

				// Delete update for this language
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query
				  ->delete('#__updates')
				  ->where('element LIKE ' . $db->quote('%' . $language));
				$db->setQuery($query);
				$db->execute();

				// Find update for this language
				$languages = self::findLanguages();

				foreach ($languages as $updateLanguage)
				{
					if ($updateLanguage['iso'] == $language)
					{
						$result = self::installLanguage($updateLanguage['update_id']);
						break;
					}
				}
				break;
		}

		return $result;
	}

	/**
	 * Create content row
	 *
	 * @param   string $jiso           Joomla ISO
	 * @param   mixed  $languageName   Language name
	 * @param   bool   $publishContent Publish content
	 *
	 * @return bool
	 */
	public static function createContentRow($jiso, $languageName = NULL, $publishContent = true)
	{
		JLoader::register('LanguagesModelLanguage', JPATH_ADMINISTRATOR . '/components/com_languages/models/language.php');
		/* @var $languageModel LanguagesModelLanguage */
		$languageModel = JModelLegacy::getInstance('Language', 'LanguagesModel');
		$icon          = self::getLanguageSupportedIcon($jiso);

		if (!is_string($languageName))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$languageDescFile = JPATH_BASE . '/language/' . $jiso . '/' . $jiso . '.xml';

			if (file_exists($languageDescFile))
			{
				$xml          = simplexml_load_file($languageDescFile);
				$languageName = (string) $xml->name;
			}
			else
			{
				$query
				  ->select('name')
				  ->from('#__extensions')
				  ->where('element = ' . $db->quote($jiso));
				$db->setQuery($query);
				$languageName = $db->loadResult();
			}

			if (empty($languageName))
			{
				$query
				  ->clear('where')
				  ->where('element = ' . $db->quote('pkg_' . $jiso));

				$db->setQuery($query);
				$languageName = $db->loadResult();
			}
		}

		// Create content
		$data = array(
		  'lang_code'    => $jiso,
		  'title'        => $languageName,
		  'title_native' => $languageName,
		  'sef'          => self::getSef($jiso),
		  'image'        => ($icon !== false) ? $icon : '',
		  'published'    => $publishContent
		);

		return $languageModel->save($data);
	}

	/**
	 * Get language JISO
	 *
	 * @param   string $jiso Joomla ISO
	 *
	 * @return string|bool
	 */
	protected static function getLanguageSupportedIcon($jiso)
	{
		$iconName = strtolower(str_replace('-', '_', $jiso));
		$iconPath = JPATH_ROOT . '/media/mod_languages/images/' . $iconName . '.gif';

		if (!file_exists($iconPath))
		{
			$iconName = explode('_', strtolower(str_replace('-', '_', $jiso)));
			$iconPath = JPATH_ROOT . '/media/mod_languages/images/' . $iconName[0] . '.gif';

			if (!file_exists($iconPath))
			{
				return false;
			}
			else
			{
				$iconName = $iconName[0];
			}
		}

		return $iconName;
	}

	/**
	 * Get SEF prefix for a particular language
	 *
	 * @param   string $jiso Joomla ISO
	 *
	 * @return string
	 */
	public static function getSef($jiso)
	{
		$jisoParts = explode('-', $jiso);
		$sef       = $jisoParts[0];
		$db        = JFactory::getDbo();
		$query     = $db->getQuery(true);
		$query
		  ->select('1')
		  ->from('#__languages')
		  ->where('sef = ' . $db->quote($sef));

		$db->setQuery($query);
		$exists = $db->loadResult() == 1;

		if ($exists)
		{
			$sef = strtolower(str_replace('-', '_', $jiso));
		}

		return $sef;
	}

	/**
	 * Get a list of languages
	 *
	 * @param   bool $allSupported All the languages supported by Joomla
	 *
	 * @return array
	 */
	public static function findLanguages($allSupported = false)
	{
		$enGbExtensionId = self::getEnGbExtensionId();
		NenoLog::log('en-GB Extension ID ' . $enGbExtensionId);
		$languagesFound = array();
		$db             = JFactory::getDbo();
		$query          = $db->getQuery(true);

		if (!empty($enGbExtensionId))
		{
			// Let's enable it if it's disabled
			$query
			  ->select('a.update_site_id')
			  ->from('#__update_sites AS a')
			  ->innerJoin('#__update_sites_extensions AS b ON a.update_site_id = b.update_site_id')
			  ->where('b.extension_id = ' . (int) $enGbExtensionId);
			$db->setQuery($query);
			$updateId = $db->loadResult();

			if (!empty($updateId))
			{
				$query
				  ->clear()
				  ->update('#__update_sites')
				  ->set('enabled = 1')
				  ->where('update_site_id = ' . (int) $updateId);
				$db->setQuery($query);
				$db->execute();
			}

			// Find updates for languages
			$updater = JUpdater::getInstance();
			$updater->findUpdates($enGbExtensionId);
			$updateSiteId = self::getLanguagesUpdateSite($enGbExtensionId);
			NenoLog::log('UpdateSiteID: ' . $updateSiteId);
			$updates = self::getUpdates($updateSiteId);
			NenoLog::log('Updates: ' . json_encode($updateSiteId));
			$languagesFound = $updates;
		}

		if ($allSupported)
		{
			$query
			  ->clear()
			  ->select(
				array(
				  'DISTINCT element AS iso',
				  'name'
				)
			  )
			  ->from('#__extensions')
			  ->where('type = ' . $db->quote('language'))
			  ->group('element');
			$db->setQuery($query);
			$languagesFound = array_merge($db->loadAssocList(), $languagesFound);
		}

		return $languagesFound;
	}

	/**
	 * Get the extension_id of the en-GB package
	 *
	 * @return int
	 */
	protected static function getEnGbExtensionId()
	{
		$db       = JFactory::getDbo();
		$extQuery = $db->getQuery(true);
		$extType  = 'language';
		$extElem  = 'en-GB';

		$extQuery
		  ->select($db->quoteName('extension_id'))
		  ->from($db->quoteName('#__extensions'))
		  ->where($db->quoteName('type') . ' = ' . $db->quote($extType))
		  ->where($db->quoteName('element') . ' = ' . $db->quote($extElem))
		  ->where($db->quoteName('client_id') . ' = 0');

		$db->setQuery($extQuery);

		return (int) $db->loadResult();
	}

	/**
	 * Get update site for languages
	 *
	 * @param   int $enGbExtensionId Extension Id of en-GB package
	 *
	 * @return int
	 */
	protected static function getLanguagesUpdateSite($enGbExtensionId)
	{
		$db        = JFactory::getDbo();
		$siteQuery = $db->getQuery(true);

		$siteQuery
		  ->select($db->quoteName('update_site_id'))
		  ->from($db->quoteName('#__update_sites_extensions'))
		  ->where($db->quoteName('extension_id') . ' = ' . $enGbExtensionId);

		$db->setQuery($siteQuery);

		return (int) $db->loadResult();
	}

	/**
	 * Get updates from a particular update site
	 *
	 * @param   int $updateSiteId Update Site Id
	 *
	 * @return array
	 */
	protected static function getUpdates($updateSiteId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'DISTINCT REPLACE(element, \'pkg_\', \'\') AS iso',
			  'u.*'
			)
		  )
		  ->from('#__updates AS u')
		  ->where(
			array('u.update_site_id = ' . (int) $updateSiteId),
			'REPLACE(element, \'pkg_\', \'\') NOT IN(' . implode(',', $db->quote(array_keys(JFactory::getLanguage()
			  ->getKnownLanguages()))) . ')'
		  )
		  ->order('name')
		  ->group('u.element');

		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * Installs a language and create necessary data.
	 *
	 * @param   integer $languageId     Language id
	 * @param   bool    $publishContent Publish language content
	 *
	 * @return bool
	 */
	public static function installLanguage($languageId, $publishContent = true)
	{
		// Loading language
		$language = JFactory::getLanguage();
		$language->load('com_installer');

		$languageData = self::getLanguageData($languageId);
		$jiso         = str_replace('pkg_', '', $languageData['element']);

		// Registering some classes
		JLoader::register('InstallerModelLanguages', JPATH_ADMINISTRATOR . '/components/com_installer/models/languages.php');
		JLoader::register('LanguagesModelLanguage', JPATH_ADMINISTRATOR . '/components/com_languages/models/language.php');

		/* @var $languagesInstallerModel InstallerModelLanguages */
		$languagesInstallerModel = JModelLegacy::getInstance('Languages', 'InstallerModel');

		// Install language
		$languagesInstallerModel->install(array($languageId));

		if (self::isLanguageInstalled($jiso) && !self::hasContentCreated($languageData['element']))
		{
			// Assign translation methods to that language
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$i     = 1;

			$query
			  ->insert('#__neno_content_language_defaults')
			  ->columns(
				array(
				  'lang',
				  'translation_method_id',
				  'ordering'
				)
			  );

			while (($translationMethod = NenoSettings::get('translation_method_' . $i)) !== NULL)
			{
				$query->values($db->quote($jiso) . ', ' . $db->quote($translationMethod) . ',' . $db->quote($i));
				$i++;
			}

			$db->setQuery($query);
			$db->execute();

			return self::createContentRow($jiso, $languageData, $publishContent);
		}

		return self::isLanguageInstalled($jiso);
	}

	/**
	 * Get Language data
	 *
	 * @param   int $updateId Update id
	 *
	 * @return array
	 */
	public static function getLanguageData($updateId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  '*',
			  'REPLACE(element, \'pkg_\', \'\') AS iso'
			)
		  )
		  ->from('#__updates')
		  ->where('update_id = ' . (int) $updateId);

		$db->setQuery($query);

		return $db->loadAssoc();
	}

	/**
	 * Check if a language package has been installed.
	 *
	 * @param   string $jiso Joomla language ISO
	 *
	 * @return bool
	 */
	protected static function isLanguageInstalled($jiso)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('1')
		  ->from('#__extensions')
		  ->where(
			array(
			  'type = ' . $db->quote('language'),
			  'element = ' . $db->quote($jiso)
			)
		  );

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Get default translation methods
	 *
	 * @return array
	 */
	public static function getDefaultTranslationMethods()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('tm.*')
		  ->from('#__neno_settings AS s')
		  ->innerJoin('#__neno_translation_methods AS tm ON tm.id = s.setting_value')
		  ->where('setting_key LIKE ' . $db->quote('translation_method_%'))
		  ->order('setting_key ASC');

		$db->setQuery($query);
		$translationMethodsSelected = $db->loadObjectList();

		return $translationMethodsSelected;
	}

	/**
	 * Get language flag
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return string
	 */
	public static function getLanguageImage($languageTag)
	{
		$cleanLanguageTag = str_replace('-', '_', strtolower($languageTag));
		$image            = $cleanLanguageTag;

		if (!file_exists(JPATH_ROOT . '/media/mod_languages/images/' . $cleanLanguageTag . '.gif'))
		{
			$cleanLanguageTagParts = explode('_', $cleanLanguageTag);
			$image                 = $cleanLanguageTagParts[0];
		}

		return $image;
	}

	/**
	 * Get language flag
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool
	 */
	public static function isLanguagePublished($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('published')
		  ->from('#__languages')
		  ->where('lang_code = ' . $db->quote($languageTag));

		$db->setQuery($query);
		$published = $db->loadResult();

		return !empty($published);
	}

	/**
	 * Get language default translation methods
	 *
	 * @param   string $languageTag Language tag
	 * @param   int    $ordering    Ordering
	 *
	 * @return array
	 */
	public static function getLanguageDefault($languageTag, $ordering = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'DISTINCT tm.*',
			  '(ordering - 1) AS ordering'
			)
		  )
		  ->from('#__neno_content_language_defaults AS ld')
		  ->innerJoin('#__neno_translation_methods AS tm ON tm.id = ld.translation_method_id')
		  ->where(
			array(
			  'lang = ' . $db->quote($languageTag),
			  'ld.ordering > ' . $ordering
			)
		  );

		$db->setQuery($query);
		$translationMethods = $db->loadObjectList('ordering');

		return $translationMethods;
	}

	/**
	 * Get the working language for the current user
	 * The value is stored in #__user_profiles
	 *
	 * @return string 'eb-GB' or 'de-DE'
	 */
	public static function getWorkingLanguage()
	{
		$app = JFactory::getApplication();

		if ($app->getUserState('com_neno.working_language') === NULL)
		{
			$userId = JFactory::getUser()->id;

			$db = JFactory::getDbo();

			$query = $db->getQuery(true);

			$query
			  ->select('profile_value')
			  ->from('#__user_profiles')
			  ->where(
				array(
				  'user_id = ' . intval($userId),
				  'profile_key = ' . $db->quote('neno_working_language')
				)
			  );

			$db->setQuery($query);
			$lang = $db->loadResult();

			$app->setUserState('com_neno.working_language', $lang);
		}

		return $app->getUserState('com_neno.working_language');
	}

	/**
	 * Get translations dropdown
	 *
	 * @return string
	 */
	public static function getTranslatorsSelect()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
		  ->clear()
		  ->select(
			array(
			  'translator_name AS value',
			  'translator_name AS text',
			)
		  )
		  ->from('#__neno_machine_translation_apis');
		$db->setQuery($query);
		$values = $db->loadObjectList();

		return JHtml::_('select.genericlist', $values, 'translator', NULL, 'value', 'text', NULL, false, true);
	}

	/**
	 * Generate filters drop down
	 *
	 * @param   int    $fieldId  Field id
	 * @param   string $selected Filter selected
	 *
	 * @return string
	 */
	public static function generateFilterDropDown($fieldId, $selected)
	{
		$filters = array(
		  'INT',
		  'UNIT',
		  'FLOAT',
		  'BOOL',
		  'WORD',
		  'ALNUM',
		  'CMD',
		  'STRING',
		  'HTML',
		  'ARRAY',
		  'TRIM',
		  'PATH',
		  'USERNAME',
		  'RAW'
		);

		return JLayoutHelper::render('dropdownbutton', array(
		  'filters'  => $filters,
		  'selected' => $selected,
		  'fieldId'  => $fieldId
		), JPATH_NENO_LAYOUTS);
	}

	/**
	 * Render tooltip for filters
	 *
	 * @return string
	 */
	public static function renderFilterHelperText()
	{
		echo htmlentities(JLayoutHelper::render('filtertooltip', NULL, JPATH_NENO_LAYOUTS));
	}

	/**
	 * Get translation method
	 *
	 * @param int $id Translation method Id
	 *
	 * @return stdClass|null
	 */
	public static function getTranslationMethodById($id)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->select('*')
			  ->from('#__neno_translation_methods')
			  ->where('id = ' . (int) $id);

			$db->setQuery($query);

			NenoCache::setCacheData($cacheId, $db->loadObject());
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getTranslationMethodsByTableId($tableId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === NULL)
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->select(
				array(
				  'gtm.lang',
				  'gtm.translation_method_id',
				)
			  )
			  ->from('#__neno_content_element_tables AS t')
			  ->innerJoin('#__neno_content_element_groups AS g ON t.group_id = g.id')
			  ->innerJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON gtm.group_id = g.id')
			  ->where('t.id = ' . $tableId)
			  ->order('ordering ASC');
			$db->setQuery($query);
			$translationMethods = $db->loadObjectListMultiIndex('lang');

			NenoCache::setCacheData($cacheId, $translationMethods);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Get language translator comment
	 *
	 * @param    string $languageTag Language tag
	 *
	 * @return string|null
	 */
	public static function getLanguageTranslatorComment($languageTag)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$query
		  ->select('comment')
		  ->from('#__neno_language_external_translators_comments')
		  ->where('language = ' . $db->quote($languageTag));

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get db free space
	 *
	 * @return int 0 => Could not be determined, 1 => Enough Space, 2 => less than 80% free, 3 => less than 50% free
	 */
	public static function getDbFreeSpace()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'sum( data_length + index_length ) / 1024 / 1024 AS occupied_space',
			  'sum( data_free )/ 1024 / 1024 AS free_space'

			)
		  )
		  ->from('information_schema.TABLE')
		  ->where('table_schema = DATABASE()');

		$db->setQuery($query);
		$result = 0;

		try
		{
			$data = $db->loadRow();

			if ($data['free_space'] != 0)
			{
				$totalSpace = $data['free_space'] + $data['occupied_space'];

				// The user has less than 50% of space available
				if ($totalSpace / 2 <= $data['free_space'])
				{
					return 3;
				}
				else
				{
					if ($totalSpace / 2 <= $data['free_space'])
					{
						return 2;
					}
					else
					{
						return 1;
					}
				}
			}
		}
		catch (RuntimeException $e)
		{

		}

		return $result;
	}

	/**
	 * Get db free space
	 *
	 * @return int 0 => Could not be determined, 1 => Enough Space, 2 => less than 80% free, 3 => less than 50% free
	 */
	public static function getSpaceNeedPerLanguage()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('sum( data_length + index_length ) / 1024 / 1024 AS occupied_space')
		  ->from('information_schema.TABLE')
		  ->where('table_schema = DATABASE()');

		$db->setQuery($query);

		try
		{
			$spaceOccupied = $db->loadResult();
			$result        = $spaceOccupied * 0.8;
		}
		catch (RuntimeException $e)
		{
			$result = 0;
		}

		return $result;
	}

	/**
	 * Get element name by Group Id
	 *
	 * @param integer $groupId Group Id
	 *
	 * @return string|null
	 */
	public static function getElementNameByGroupId($groupId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('element')
		  ->from('`#__neno_content_element_groups_x_extensions` AS ge')
		  ->innerJoin('`#__extensions` AS e ON e.extension_id = ge.extension_id')
		  ->where('ge.group_id = ' . (int) $groupId);

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get language configuration data
	 *
	 * @return array
	 */
	public static function getLanguageConfigurationData()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$default = NenoSettings::get('source_language');

		$subquery1 = $db->getQuery(true);
		$subquery2 = $db->getQuery(true);
		$subquery3 = $db->getQuery(true);

		$subquery3
		  ->select('m.id')
		  ->from('#__neno_content_element_translation_x_translation_methods AS m')
		  ->where('m.translation_id = tr.id');

		$subquery2
		  ->select('tr2.*')
		  ->from('#__neno_content_element_translations AS tr2')
		  ->where('content_type = ' . $db->quote('lang_string'));

		$subquery1
		  ->select('tr.*')
		  ->from('#__neno_content_element_translations AS tr')
		  ->innerJoin('#__neno_content_element_fields AS f ON tr.content_id = f.id')
		  ->innerJoin('#__neno_content_element_tables AS t ON t.id = f.table_id')
		  ->where(
			array(
			  'content_type = ' . $db->quote('db_string'),
			  'f.translate = 1',
			  't.translate = 1'
			)
		  )
		  ->union($subquery2);

		$query
		  ->select(
			array(
			  'l.lang_code',
			  'l.published',
			  'l.title',
			  'l.image',
			  'tr.state',
			  'SUM(tr.word_counter) AS word_count',
			  'lc.comment'
			)
		  )
		  ->from('#__languages AS l')
		  ->leftJoin('#__neno_language_external_translators_comments AS lc ON l.lang_code = lc.language')
		  ->leftJoin('(' . (string) $subquery1 . ') AS tr ON tr.language = l.lang_code')
		  ->where('l.lang_code <> ' . $db->quote($default) . ' AND EXISTS (' . (string) $subquery3 . ')')
		  ->group(
			array(
			  'l.lang_code',
			  'tr.state'
			)
		  )
		  ->order('lang_code');

		$db->setQuery($query);

		$languages = $db->loadObjectListMultiIndex('lang_code');
		$items     = array();

		if (!empty($languages))
		{
			foreach ($languages as $language)
			{
				$item              = new stdClass;
				$item->lang_code   = $language[0]->lang_code;
				$item->comment     = $language[0]->comment;
				$item->published   = $language[0]->published;
				$item->title       = $language[0]->title;
				$item->image       = $language[0]->image;
				$item->errors      = NenoHelper::getLanguageErrors((array) $language[0]);
				$item->isInstalled = NenoHelper::isCompletelyInstall($item->lang_code);
				$item              = NenoHelper::getLanguageStats($language, $item);

				$items[] = $item;
			}
		}

		$languagesOnLanguageTable   = array_keys($languages);
		$knownLanguages             = JFactory::getLanguage()
		  ->getKnownLanguages();
		$defaultTranslationsMethods = NenoHelper::getDefaultTranslationMethods();

		foreach ($knownLanguages as $languageTag => $languageInfo)
		{
			if ($languageTag != $default && !in_array($languageTag, $languagesOnLanguageTable))
			{
				$languagesData                     = new stdClass;
				$languagesData->lang_code          = $languageInfo['tag'];
				$languagesData->title              = $languageInfo['name'];
				$languagesData->translationMethods = $defaultTranslationsMethods;
				$languagesData->errors             = NenoHelper::getLanguageErrors((array) $languagesData);
				$languagesData->placement          = 'dashboard';
				$languagesData->image              = NenoHelper::getLanguageImage($languageInfo['tag']);
				$languagesData->published          = NenoHelper::isLanguagePublished($languageInfo['tag']);
				$languagesData->comment            = NenoHelper::getLanguageTranslatorComment($languageInfo['tag']);

				$items[] = $languagesData;
			}
		}

		return $items;
	}

	/**
	 * Get language stats
	 *
	 * @param array    $language Language internal items
	 * @param stdClass $item     Language item
	 *
	 * @return stdClass
	 */
	public static function getLanguageStats($language, $item)
	{
		$translated   = 0;
		$queued       = 0;
		$changed      = 0;
		$untranslated = 0;

		foreach ($language as $internalItem)
		{
			switch ($internalItem->state)
			{
				case NenoContentElementTranslation::TRANSLATED_STATE:
					$translated = (int) $internalItem->word_count;
					break;
				case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
					$queued = (int) $internalItem->word_count;
					break;
				case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
					$changed = (int) $internalItem->word_count;
					break;
				case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
					$untranslated = (int) $internalItem->word_count;
					break;
			}
		}

		$item->wordCount               = new stdClass;
		$item->wordCount->translated   = $translated;
		$item->wordCount->queued       = $queued;
		$item->wordCount->changed      = $changed;
		$item->wordCount->untranslated = $untranslated;
		$item->wordCount->total        = $translated + $queued + $changed + $untranslated;

		return $item;
	}

	/**
	 * Takes a long string and breaks it into natural chunks and returns an array with the chunks
	 * - The method will attempt to break on certain html tags first, then sentence structures and finally spaces if possible
	 * - If $string is shorter than $maxChunkLength an array with one entry is returned
	 *
	 * @param string $string         String to chunk
	 * @param int    $maxChunkLength Maximum chunk length
	 *
	 * @return array
	 */
	public static function chunkHtmlString($string, $maxChunkLength)
	{
		$chunks = array();

		//If the given string can fit in the first chunk then just return that
		if (\Joomla\String\StringHelper::strlen($string) < $maxChunkLength)
		{
			$chunks[] = $string;

			return $chunks;
		}

		$cutStrings   = array();
		$cutStrings[] = '</div>';
		$cutStrings[] = '</p>';
		$cutStrings[] = '</ul>';
		$cutStrings[] = '</table>';
		$cutStrings[] = '</a>';
		$cutStrings[] = '. ';

		while (\Joomla\String\StringHelper::strlen($string) > $maxChunkLength)
		{

			//Look for the breakpoint that is located last in the substring that is less than max
			$potentialCutPoints = array();
			foreach ($cutStrings as $key => $cutString)
			{
				$position = strripos(substr($string, 0, $maxChunkLength), $cutString);
				if ($position !== false)
				{
					$potentialCutPoints[$position] = $cutString;
				}
			}

			//Select the right most breakpoint
			if (count($potentialCutPoints))
			{
				$selectedBreakPoint       = max(array_keys($potentialCutPoints));
				$selectedBreakPointString = $potentialCutPoints[$selectedBreakPoint];
				$breakPoint               = $selectedBreakPoint + utf8_strlen($selectedBreakPointString);

				//Add the chunk
				$chunks[] = \Joomla\String\StringHelper::substr($string, 0, $breakPoint);

			}
			else
			{
				//Unable to find a breakpoint, use wordwrap
				$wordWrappedString = wordwrap($string, $maxChunkLength, '|||---NENO---|||', true);
				$wordWrappedArray  = explode('|||---NENO---|||', $wordWrappedString);
				$chunks[]          = $wordWrappedArray[0];
				$breakPoint        = \Joomla\String\StringHelper::strlen($wordWrappedArray[0]) + 3;
			}

			//Reduce the string
			$string = \Joomla\String\StringHelper::substr($string, $breakPoint);

		}

		//Add the remainder to the last chunk
		$chunks[] = $string;

		return $chunks;
	}

	/**
	 * Gets the language details from a given code
	 * 
	 * @param   string  $code  The lang code
	 *                         
	 * @return  stdClass The language details
	 */
	public static function getLanguageByCode($code)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($db->quoteName('#__languages'))
			->where($db->quoteName('lang_code') . ' = ' . $db->quote($code));

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Clean string from XSS attacks
	 *
	 * @param string $string String to clean.
	 *
	 * @return string
	 */
	public static function cleanXssString($string)
	{
		// Fix &entity\n;
		$data = str_replace(array('&amp;', '&lt;', '&gt;'), array(
		  '&amp;amp;',
		  '&amp;lt;',
		  '&amp;gt;'
		), $string);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

		// Remove javascript: and vbscript: protocols
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

		// Remove namespaced elements (we do not need them)
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

		do
		{
			// Remove really unwanted tags
			$oldData = $data;
			$data    = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		} while ($oldData !== $data);

		// we are done...
		return $data;
	}

	/**
	 * Generate where statement based on
	 *
	 * @param array $filter
	 *
	 * @return string
	 */
	public static function getWhereClauseForTableFilters($filter)
	{
		$db = JFactory::getDbo();
		if ($filter['operator'] == 'IN')
		{
			$values = explode(',', $filter['value']);
			array_walk($values, 'trim');
			$whereClause = $db->quoteName($filter['field']) . ' ' . $filter['operator'] . ' (' . implode(',', $db->quote($values)) . ')';
		}
		else
		{
			$whereClause = $db->quoteName($filter['field']) . ' ' . $filter['operator'] . ' ' . $db->quote($filter['value']);
		}

		return $whereClause;
	}

	/**
	 * Converts euro price in translation credits
	 *
	 * @param float $euroPrice Euro price
	 *
	 * @return float
	 */
	public static function convertEuroToTranslationCredit($euroPrice)
	{
		return number_format(ceil($euroPrice / 0.0005), 2, ',', '.');
	}

	/**
	 * Clean language tag
	 *
	 * @param   string $languageTag Language Tag
	 *
	 * @return string language tag cleaned
	 */
	public static function cleanLanguageTag($languageTag)
	{
		return strtolower(str_replace(array('-'), array(''), $languageTag));
	}

	/**
	 * Get all the front-end modules in the source language
	 *
	 * @return array
	 */
	public static function getModulesInSourceLanguage()
	{
		$sourceLanguage = NenoSettings::get('source_language');
		$db             = JFactory::getDbo();
		$query          = $query = self::generateModulesQuery();;

		$query
		  ->where(
			array(
			  'client_id = 0',
			  'language = ' . $db->quote($sourceLanguage)
			)
		  );

		$db->setQuery($query);
		$modules = $db->loadObjectList();

		return $modules;
	}

	/**
	 * Get a list of modules should be replicated
	 *
	 * @return array
	 */
	public static function getModuleTypesNeedToBeDuplicated()
	{
		return array(
		  'mod_custom',
		  'mod_menu'
		);
	}

	/**
	 * Get similar modules to the provided one.
	 *
	 * @param stdClass $module   Module object
	 * @param string   $language Language string
	 *
	 * @return array
	 */
	public static function getSimilarModulesToModule($module, $language)
	{
		$db    = JFactory::getDbo();
		$query = self::generateModulesQuery();

		$query
		  ->where(
			array(
			  'position = ' . $db->quote($module->position),
			  'ordering = ' . $db->quote($module->ordering),
			  'language = ' . $db->quote($language),
			  'module = ' . $db->quote($module->module)
			)
		  );

		$db->setQuery($query);
		$modules = $db->loadObjectList();

		return $modules;
	}

	/**
	 * Generates common query part for modules (it includes the menu assignment type)
	 *
	 * @return \JDatabaseQuery
	 */
	protected function generateModulesQuery()
	{
		$db            = JFactory::getDbo();
		$query         = $db->getQuery(true);
		$queryAll      = $db->getQuery(true);
		$querySelected = $db->getQuery(true);
		$queryNone     = $db->getQuery(true);

		// This query checks if the
		$queryAll
		  ->select(1)
		  ->from('#__modules_menu AS mm')
		  ->where(
			array(
			  'mm.moduleid = m.id',
			  'mm.menuid = 0'
			)
		  );

		$querySelected
		  ->select(1)
		  ->from('#__modules_menu AS mm')
		  ->where(
			array(
			  'mm.moduleid = m.id',
			  'mm.menuid > 0'
			)
		  );

		$queryNone
		  ->select(1)
		  ->from('#__modules_menu AS mm')
		  ->where('mm.moduleid = m.id');


		$query
		  ->select(
			array(
			  'm.*',
			  'IF(EXISTS(' . (string) $queryAll . '), \'all\', IF(NOT EXISTS(' . (string) $queryNone . '), \'none\', IF(EXISTS(' . (string) $querySelected . ') ,\'selected\', \'not_selected\'))) AS assignment_type'
			)
		  )
		  ->from('#__modules AS m')
		  ->where('m.published IN (0,1)');

		return $query;
	}

	/**
	 * Get the most similar
	 *
	 * @param stdClass      $module   Module object
	 * @param string        $language Language tag
	 * @param null|callable $callback Function that will be executed with the candidates left
	 *
	 * @return bool|stdClass False if there's no similar module, module object otherwise
	 */
	public static function getMostSimilarModuleForLanguage($module, $language, $callback = NULL)
	{
		$modules = self::getSimilarModulesToModule($module, $language);

		if (!empty($modules))
		{
			// Get only the modules that have associated the same configuration for menu items assignment
			$similarModules = array();
			foreach ($modules as $possibleSimilarModule)
			{
				// Check if both modules have the same assignment type
				if ($possibleSimilarModule->assignment_type === $module->assignment_type)
				{
					// If the assignment type is ALL or NONE, it's one of the similar
					if (in_array(
					  $possibleSimilarModule->assignment_type,
					  array(
						'all',
						'none'
					  )
					))
					{
						$similarModules[] = $possibleSimilarModule;
					}
					else
					{
						// Check if the selected or not selected items are associated.
						$db    = JFactory::getDbo();
						$query = $db->getQuery(true);

						$query->select('DISTINCT 1')
						  ->from('#__modules_menu AS mm1')
						  ->innerJoin('#__associations AS a1 ON a1.id = mm1.menuid')
						  ->innerJoin('#__associations AS a2 ON a1.`key` = a2.`key`')
						  ->innerJoin('#__modules_menu AS mm2 ON a2.id = mm2.menuid')
						  ->where(
							array(
							  'mm1.moduleid = ' . $possibleSimilarModule->id,
							  'mm2.moduleid = ' . $module->id,
							  'a1.context = ' . $db->quote('com_menus.item'),
							  'a2.context = ' . $db->quote('com_menus.item')
							)
						  );

						$db->setQuery($query);
						$result = $db->loadResult();

						if (!empty($result))
						{
							$similarModules[] = $possibleSimilarModule;
						}
					}
				}
			}

			if ($callback !== NULL)
			{
				$module = call_user_func_array($callback, array(
				  $module,
				  $similarModules
				));
			}

			return $module;
		}

		return false;
	}

	/**
	 * Filter similar modules for mod_menu module
	 *
	 * @param stdClass $module
	 * @param array    $similarModules
	 *
	 * @return stdClass|null
	 */
	public static function filterSimilarModulesForModMenu($module, $similarModules)
	{
		$moduleData = json_decode($module->params);

		foreach ($similarModules as $similarModule)
		{
			$similarModuleData = json_decode($similarModule->params);

			if (
			  $similarModuleData->startLevel == $moduleData->startLevel &&
			  $similarModuleData->endLevel == $moduleData->endLevel &&
			  $similarModuleData->showAllChildren == $moduleData->showAllChildren &&
			  self::areMenusRelated($moduleData->menutype, $similarModuleData->menutype)
			)
			{
				return $similarModule;
			}
		}

		return $similarModules;
	}

	/**
	 * Check whether or not two menu types are related
	 *
	 * @param string $menuTypeA
	 * @param string $menuTypeB
	 *
	 * @return bool
	 */
	public static function areMenusRelated($menuTypeA, $menuTypeB)
	{
		if ($menuTypeA === $menuTypeB)
		{
			return true;
		}

		$menusRelated = self::getMenusRelated($menuTypeA);

		return in_array($menuTypeB, $menusRelated);
	}

	/**
	 * Get all the menu relates to a menu given
	 *
	 * @param string $menuType
	 *
	 * @return array
	 */
	public static function getMenusRelated($menuType)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'DISTINCT m2.menutype',
			  'm2.language'
			)
		  )
		  ->from('#__menu AS m1')
		  ->leftJoin('#__associations AS a1 ON m1.id = a1.id')
		  ->leftJoin('#__associations AS a2 ON a2.`key` = a2.`key`')
		  ->leftJoin('#__menu AS m2 ON a2.id = m2.id')
		  ->where(
			array(
			  'm1.menutype = ' . $db->quote($menuType),
			  'a1.context = ' . $db->quote('com_menus.item'),
			  'a2.context = ' . $db->quote('com_menus.item'),
			)
		  );

		$db->setQuery($query);
		$menus  = $db->loadAssocList();
		$result = array();

		foreach ($menus as $menu)
		{
			$result[$menu['language']] = $menu['menutype'];
		}

		return $result;
	}

	/**
	 * Return callback for a particular menu type to be applied on module filtering
	 *
	 * @param string $moduleType
	 *
	 * @return callable|null
	 */
	public static function getCallbackForModulesFilteringByModuleType($moduleType)
	{
		$callback = NULL;

		switch ($moduleType)
		{
			case 'mod_menu':
				$callback = array(
				  'NenoHelper',
				  'filterSimilarModulesForModMenu'
				);
				break;
		}

		return $callback;
	}
}