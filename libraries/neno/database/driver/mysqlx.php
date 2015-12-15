<?php

/**
 * @package     Neno
 * @subpackage  Database
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$config = JFactory::getConfig();

// If the Joomla site is using mysql, let's stick to it
if ($config->get('dbtype') == 'mysql')
{
	class CommonDriver extends JDatabaseDriverMysql
	{

	}
}
else
{
	class CommonDriver extends JDatabaseDriverMysqli
	{

	}
}

/**
 * Database driver class extends from Joomla Platform Database Driver class
 *
 * @since  1.0
 */
class NenoDatabaseDriverMysqlx extends CommonDriver
{
	/**
	 * Select query constant
	 */
	const SELECT_QUERY = 1;

	/**
	 * Insert query constant
	 */
	const INSERT_QUERY = 2;

	/**
	 * Update query constant
	 */
	const UPDATE_QUERY = 3;

	/**
	 * Replace query constant
	 */
	const REPLACE_QUERY = 4;

	/**
	 * Delete query constant
	 */
	const DELETE_QUERY = 5;

	/**
	 * Other query constant, such as SHOW TABLES, etc...
	 */
	const OTHER_QUERY = 6;

	/**
	 * Tables configured to be translatable
	 *
	 * @var array
	 */
	private $manifestTables;

	/**
	 * @var array
	 */
	private $languages;

	/**
	 * @var bool
	 */
	private $propagateQuery;

	/**
	 * Set Autoincrement index in a shadow table
	 *
	 * @param   string $tableName   Original table name
	 * @param   string $shadowTable Shadow table name
	 *
	 * @return boolean True on success, false otherwise
	 */
	public function setAutoincrementIndex($tableName, $shadowTable)
	{
		try
		{
			// Create a new query object
			$query = $this->getQuery(true);

			$query
				->select($this->quoteName('AUTO_INCREMENT'))
				->from('INFORMATION_SCHEMA.TABLES')
				->where(
					array(
						'TABLE_SCHEMA = ' . $this->quote($this->getDatabase()),
						'TABLE_NAME = ' . $this->quote($this->replacePrefix($tableName))
					)
				);

			$data = $this->executeQuery($query, true, true);

			$sql = 'ALTER TABLE ' . $this->quoteName($shadowTable) . ' AUTO_INCREMENT= ' . $this->quote((int) $data[0]->AUTO_INCREMENT);
			$this->executeQuery($sql);

			return true;
		} catch (RuntimeException $ex)
		{
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   bool $new If the query should be new
	 *
	 * @return NenoDatabaseQueryMysqli|JDatabaseQuery
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
			// Derive the class name from the driver.
			$class = 'NenoDatabaseQueryMysqlx';

			// Make sure we have a query class for this driver.
			if (!class_exists($class))
			{
				// If it doesn't exist we are at an impasse so throw an exception.
				// Derive the class name from the driver.
				$class = 'JDatabaseQuery' . ucfirst($this->name);

				// Make sure we have a query class for this driver.
				if (!class_exists($class))
				{
					// If it doesn't exist we are at an impasse so throw an exception.
					throw new RuntimeException('Database Query Class not found.');
				}
			}

			return new $class($this);
		}
		else
		{
			return $this->sql;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   string $sql    SQL Query
	 * @param   string $prefix DB Prefix
	 *
	 * @return string
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		// Check if the query should be parsed.
		if ($this->isInstallationCompleted() && $this->languageHasChanged() && $this->hasToBeParsed($sql))
		{
			// Get query type
			$queryType = $this->getQueryType($sql);
			$app       = JFactory::getApplication();

			// If the query is a select statement let's get the sql query using its shadow table name
			if ($queryType === self::SELECT_QUERY && $app->isSite())
			{
				$sql = $this->replaceTableNameStatements($sql);
			}
		}

		return parent::replacePrefix($sql, $prefix);
	}

	/**
	 * Checks if the installation process has finished
	 *
	 * @return bool
	 */
	protected function isInstallationCompleted()
	{
		return NenoSettings::get('installation_completed') == 1 && NenoSettings::get('installation_status') == 5;
	}

	/**
	 * Check if the language is different from the default
	 *
	 * @return bool
	 */
	public function languageHasChanged()
	{
		$input           = JFactory::getApplication()->input;
		$defaultLanguage = NenoSettings::get('source_language');
		$lang            = $input->getString('lang', $defaultLanguage);
		$currentLanguage = JLanguage::getInstance($lang);

		return $currentLanguage->getTag() !== $defaultLanguage;
	}

	/**
	 * Check if a table should be parsed
	 *
	 * @param   string $sql SQL Query
	 *
	 * @return bool
	 */
	private function hasToBeParsed($sql)
	{
		// Check if the query contains Neno tables

		if (!preg_match('/#__neno_/', $sql))
		{
			if (!empty($this->manifestTables))
			{
				foreach ($this->manifestTables as $table)
				{
					if (preg_match('/' . preg_quote($table) . '/', $sql))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the type of the SQL query
	 *
	 * @param   string $sql SQL Query
	 *
	 * @return int
	 *
	 * @see constants
	 */
	protected function getQueryType($sql)
	{
		$sql       = trim(strtolower($sql));
		$queryType = self::OTHER_QUERY;

		if (NenoHelper::startsWith($sql, 'insert'))
		{
			$queryType = self::INSERT_QUERY;
		}
		elseif (NenoHelper::startsWith($sql, 'delete'))
		{
			$queryType = self::DELETE_QUERY;
		}
		elseif (NenoHelper::startsWith($sql, 'replace'))
		{
			$queryType = self::REPLACE_QUERY;
		}
		elseif (NenoHelper::startsWith($sql, 'update'))
		{
			$queryType = self::UPDATE_QUERY;
		}
		elseif (NenoHelper::startsWith($sql, 'select'))
		{
			$queryType = self::SELECT_QUERY;
		}

		return $queryType;
	}

	/**
	 * Replace all the table names with shadow tables names
	 *
	 * @param   string $sql                 SQL Query
	 * @param   string $languageTagSelected Language tag selected
	 *
	 * @return string
	 */
	protected function replaceTableNameStatements($sql, $languageTagSelected = null)
	{
		/* @var $config Joomla\Registry\Registry */
		$config         = JFactory::getConfig();
		$databasePrefix = $config->get('dbprefix');
		$pattern        = '/(#__|' . preg_quote($databasePrefix) . ')(\w+)/';
		$matches        = null;
		$sql            = str_replace("\n", ' ', $sql);

		if ($languageTagSelected === null)
		{
			$languageTagSelected = $this->getLanguageTagSelected();
		}

		if ($languageTagSelected != '')
		{
			if (preg_match_all($pattern, $sql, $matches))
			{
				foreach ($matches[0] as $match)
				{
					if ($this->isTranslatable($match))
					{
						$sql = preg_replace('/`?' . $match . '`? /', $this->generateShadowTableName($match, $languageTagSelected) . ' ', $sql);
					}
				}
			}
		}

		return $sql;
	}

	/**
	 * Get language tag to add at the end of the table name
	 *
	 * @return string
	 */
	protected function getLanguageTagSelected()
	{
		$currentLanguage    = JFactory::getLanguage();
		$currentLanguageTag = $currentLanguage->getTag();
		$defaultLanguageTag = NenoSettings::get('source_language', 'en-GB');

		$languageTag = '';

		// If it is not the default language, let's get the language tag
		if ($currentLanguageTag !== $defaultLanguageTag)
		{
			// Clean language tag
			$languageTag = $currentLanguageTag;
		}

		return $languageTag;
	}

	/**
	 * Check if a table is translatable
	 *
	 * @param   string $tableName Table name
	 *
	 * @return boolean
	 */
	public function isTranslatable($tableName)
	{
		return in_array($tableName, $this->manifestTables);
	}

	/**
	 * Generate shadow table name
	 *
	 * @param   string $tableName   Table name
	 * @param   string $languageTag Clean language tag
	 *
	 * @return string shadow table name.
	 */
	public function generateShadowTableName($tableName, $languageTag)
	{
		return '#___' . $this->cleanLanguageTag($languageTag) . '_' . $this->cleanTableName($tableName);
	}

	/**
	 * Clean language tag
	 *
	 * @param   string $languageTag Language Tag
	 *
	 * @return string language tag cleaned
	 */
	public function cleanLanguageTag($languageTag)
	{
		return strtolower(str_replace(array( '-' ), array( '' ), $languageTag));
	}

	/**
	 * Get table name without Joomla prefixes
	 *
	 * @param   string $tableName Table name
	 *
	 * @return string clean table name
	 */
	protected function cleanTableName($tableName)
	{
		$config         = JFactory::getConfig();
		$databasePrefix = $config->get('dbprefix');

		return str_replace(array( '#__', $databasePrefix ), '', $tableName);
	}

	/**
	 * Execute a sql preventing to lose the query previously assigned.
	 *
	 * @param   mixed   $sql                   JDatabaseQuery object or SQL query
	 * @param   boolean $preservePreviousQuery True if the previous query will be saved before, false otherwise
	 * @param   boolean $returnObjectList      True if the method should return a list of object as query result, false otherwise
	 *
	 * @return void|array
	 */
	public function executeQuery($sql, $preservePreviousQuery = true, $returnObjectList = false)
	{
		$currentSql   = null;
		$returnObject = null;

		// If the flag is activated, let's keep it save
		if ($preservePreviousQuery)
		{
			$currentSql = $this->sql;
		}

		$this->sql = $sql;
		$this->execute();

		// If the flag was activated, let's get it from the query
		if ($returnObjectList)
		{
			$returnObject = $this->loadObjectList();
		}

		// If the flag is activated, let's assign to the sql property again.
		if ($preservePreviousQuery)
		{
			$this->sql = $currentSql;
		}

		return $returnObject;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|mixed
	 */
	public function execute()
	{
		$language = JFactory::getLanguage();
		$app      = JFactory::getApplication();

		// Check if the user is trying to insert something in the front-end in different language
		if ($this->getQueryType((string) $this->sql) === self::INSERT_QUERY
		    && $language->getTag() !== NenoSettings::get('source_language')
		    && $app->isSite() && !$this->isNenoSql((string) $this->sql)
		)
		{
			$tables = null;
			preg_match('/insert into (\w+)/', $this->sql, $tables);

			if (!empty($tables))
			{
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load(array( 'table_name' => $tables[1] ));

				if (!empty($table) && $table->isTranslate())
				{
					$language->load('com_neno', JPATH_ADMINISTRATOR);
					throw new Exception(JText::_('COM_NENO_CONTENT_IN_OTHER_LANGUAGES_ARE_NOT_ALLOWED'));
				}
			}
		}
		else
		{
			try
			{
				// Get query type
				$queryType = $this->getQueryType((string) $this->sql);

				$result = parent::execute();

				// If the query is creating/modifying/deleting a record, let's do the same on the shadow tables
				if (($queryType === self::INSERT_QUERY || $queryType === self::DELETE_QUERY || $queryType === self::UPDATE_QUERY || $queryType === self::REPLACE_QUERY) && $this->hasToBeParsed((string) $this->sql) && $this->propagateQuery)
				{
					$sql = $this->sql;

					foreach ($this->languages as $language)
					{
						$newSql = $this->replaceTableNameStatements((string) $sql, $language->lang_code);

						// Execute query if they are different.
						if ($newSql != $sql)
						{
							$this->executeQuery($newSql, false);
						}
					}
				}

				return $result;
			} catch (RuntimeException $ex)
			{
				// If the table(s) doesn't exists, let's create them
				if ($ex->getCode() == 1146)
				{
					$tables = $this->extractTableNamesFromSqlQuery();

					if (!empty($tables))
					{
						foreach ($tables as $tableName)
						{
							/* @var $table NenoContentElementTable */
							$table = NenoContentElementTable::load(array( 'table_name' => $tableName ));

							// If the table exists and it's translatable.
							if (!empty($table) && $table->isTranslate())
							{
								$this->syncTable($tableName);
							}
						}

						$this->execute();
					}
				}
			}
		}

		return false;
	}

	protected function extractTableNamesFromSqlQuery()
	{
		$matches = null;
		if (preg_match_all('/#__[a-zA-Z_]+/', $this->sql, $matches))
		{
			return array_unique($matches[0]);
		}

		return false;
	}

	/**
	 * Check if the SQL is from Neno
	 *
	 * @param   string $sql SQL to check
	 *
	 * @return int
	 */
	public function isNenoSql($sql)
	{
		return preg_match('/#__neno_(.+)/', $sql);
	}

	/**
	 * Load an array of objects based on the query executed, but if the array contains several items with the same key,
	 * it will create a an array
	 *
	 * @param   string $key   Array key
	 * @param   string $class Object class
	 *
	 * @return array|null
	 */
	public function loadObjectListMultiIndex($key = '', $class = 'stdClass')
	{
		$this->connect();

		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set as objects of type $class.
		while ($row = $this->fetchObject($cursor, $class))
		{
			if ($key)
			{
				if (!isset($array[ $row->$key ]))
				{
					$array[ $row->$key ] = array();
				}

				$array[ $row->$key ][] = $row;
			}
			else
			{
				$array[] = $row;
			}
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

	/**
	 * Refresh the translatable tables
	 *
	 * @return void
	 */
	public function refreshTranslatableTables()
	{
		$query = $this->getQuery(true);
		$query
			->select('table_name')
			->from('#__neno_content_element_tables')
			->where('translate = 1');

		$manifestTablesObjectList = $this->executeQuery($query, true, true);

		$this->manifestTables = array();

		if (!empty($manifestTablesObjectList))
		{
			foreach ($manifestTablesObjectList as $object)
			{
				$this->manifestTables[] = $object->table_name;
			}
		}

		$this->languages = NenoHelper::getTargetLanguages();
	}

	/**
	 * Delete all the shadow tables related to a table
	 *
	 * @param   string $tableName Table name
	 *
	 * @return void
	 */
	public function deleteShadowTables($tableName)
	{
		$defaultLanguage = NenoSettings::get('source_language');
		$knownLanguages  = NenoHelper::getLanguages();

		foreach ($knownLanguages as $knownLanguage)
		{
			if ($knownLanguage->lang_code !== $defaultLanguage)
			{
				$shadowTableName = $this->generateShadowTableName($tableName, $knownLanguage->lang_code);
				$this->dropTable($shadowTableName);
			}
		}
	}

	/**
	 * Create all the shadow tables needed for
	 *
	 * @param   string      $tableName   Table name
	 * @param   bool        $copyContent Copy the content of the source table
	 * @param   string|null $language    Generate shadow table for this particular language
	 *
	 * @return void
	 */
	public function createShadowTables($tableName, $copyContent = true, $language = null)
	{
		$defaultLanguage = NenoSettings::get('source_language');
		$tableColumns    = array_keys($this->getTableColumns($tableName));
		$hasLanguage     = in_array('language', $tableColumns);

		// If there's no language passed, let's execute this for each language
		if ($language === null)
		{
			$knownLanguages = NenoHelper::getLanguages(false);

			foreach ($knownLanguages as $knownLanguage)
			{
				if ($knownLanguage->lang_code !== $defaultLanguage)
				{
					$shadowTableName            = $this->generateShadowTableName($tableName, $knownLanguage->lang_code);
					$shadowTableCreateStatement = 'CREATE TABLE IF NOT EXISTS ' . $this->quoteName($shadowTableName) . ' LIKE ' . $this->quoteName($tableName);
					$this->executeQuery($shadowTableCreateStatement);

					if ($copyContent)
					{
						$this->copyContentElementsFromSourceTableToShadowTables($tableName, $shadowTableName, $hasLanguage, $knownLanguage->lang_code);
					}
				}
			}
		}
		else
		{
			$shadowTableName            = $this->generateShadowTableName($tableName, $language);
			$shadowTableCreateStatement = 'CREATE TABLE IF NOT EXISTS ' . $this->quoteName($shadowTableName) . ' LIKE ' . $this->quoteName($tableName);
			$this->executeQuery($shadowTableCreateStatement);

			if ($copyContent)
			{
				$this->copyContentElementsFromSourceTableToShadowTables($tableName, $shadowTableName, $hasLanguage, $language);

				if ($hasLanguage)
				{
					$query = $this->getQuery(true);
					$query
						->update($shadowTableName)
						->set('language = ' . $this->quote($language));
					$this->executeQuery($query);
				}
			}
		}
	}

	/**
	 * Copy all the content to the shadow table
	 *
	 * @param   string $sourceTableName Name of the source table
	 * @param   string $shadowTableName Name of the shadow table
	 * @param   bool   $hasLanguage     Whether or not the shadow table has language field
	 * @param   string $language        Language to set the shadow table in case the it has language field
	 *
	 * @return void
	 */
	public function copyContentElementsFromSourceTableToShadowTables($sourceTableName, $shadowTableName, $hasLanguage, $language)
	{
		$columns = array_keys($this->getTableColumns($sourceTableName));
		$query   = 'REPLACE INTO ' . $this->quoteName($shadowTableName) . ' (' . implode(',', $this->quoteName($columns)) . ' ) SELECT * FROM ' . $this->quoteName($sourceTableName);
		$this->executeQuery($query);

		if ($hasLanguage)
		{
			$query = $this->getQuery(true);
			$query
				->update($shadowTableName)
				->set('language = ' . $this->quote($language));
			$this->executeQuery($query);
		}
	}

	/**
	 * Retrieves field information about a given table.
	 *
	 * @param   string  $table    The name of the database table.
	 * @param   boolean $typeOnly True to only return field types.
	 *
	 * @return  array  An array of fields for the database table.
	 *
	 * @since   12.2
	 * @throws  RuntimeException
	 */
	public function getTableColumns($table, $typeOnly = true)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			NenoCache::setCacheData($cacheId, parent::getTableColumns($table, $typeOnly));
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Copy all the content to the shadow table
	 *
	 * @param   string $sourceTableName Name of the source table
	 * @param   string $language        Language
	 *
	 * @return void
	 */
	public function deleteContentElementsFromSourceTableToShadowTables($sourceTableName, $language)
	{
		$query = $this->getQuery(true);
		$query
			->delete($sourceTableName)
			->where('language = ' . $this->quote($language));
		$this->setQuery($query);
		$oldValue             = $this->propagateQuery;
		$this->propagateQuery = false;
		$this->execute();
		$this->propagateQuery = $oldValue;
	}

	/**
	 * Set from All ('*') to source language
	 *
	 * @param $sourceTableName
	 * @param $sourceLanguage
	 *
	 * @throws Exception
	 */
	public function setContentForAllLanguagesToSourceLanguage($sourceTableName, $sourceLanguage)
	{
		$query = $this->getQuery(true);
		$query
			->update($sourceTableName)
			->set('language =' . $this->quote($sourceLanguage))
			->where('language = ' . $this->quote('*'));
		$this->setQuery($query);
		$this->execute();
	}

	/**
	 * Copy the content to a table that uses Joomla language field
	 *
	 * @param   string $tableName Table name
	 *
	 * @return void
	 */
	public function copyContentElementsUsingJoomlaLanguageField($tableName)
	{
		$defaultLanguage = NenoSettings::get('source_language');
		$knownLanguages  = NenoHelper::getLanguages();
		$columns         = array_keys($this->getTableColumns($tableName));

		foreach ($columns as $key => $column)
		{
			if ($column == 'id')
			{
				unset($columns[ $key ]);
				break;
			}
		}

		foreach ($knownLanguages as $knownLanguage)
		{
			if ($knownLanguage->lang_code !== $defaultLanguage)
			{
				$selectColumns = $columns;

				foreach ($selectColumns as $key => $selectColumn)
				{
					if ($selectColumn == 'language')
					{
						$selectColumns[ $key ] = $this->quote($knownLanguage->lang_code);
					}
					else
					{
						$selectColumns[ $key ] = $this->quoteName($selectColumn);
					}
				}

				$query = 'INSERT INTO ' . $tableName . ' (' . implode(',', $this->quoteName($columns)) . ') SELECT ' . implode(',', $selectColumns) . ' FROM ' . $tableName . ' WHERE language=' . $this->quote($defaultLanguage);
				$this->setQuery($query);
				$this->execute();
			}
		}
	}

	/**
	 * Get primary key of a table
	 *
	 * @param   string $tableName Table name
	 *
	 * @return string|null
	 */
	public function getPrimaryKey($tableName)
	{
		$query       = 'SHOW INDEX FROM ' . $this->quoteName($tableName) . ' WHERE Key_name = \'PRIMARY\' OR Non_unique = 0';
		$results     = $this->executeQuery($query, true, true);
		$foreignKeys = array();

		if (!empty($results))
		{
			foreach ($results as $result)
			{
				$foreignKeys[] = $result->Column_name;
			}
		}

		return $foreignKeys;
	}

	/**
	 * Get all the tables that belong to a particular component.
	 *
	 * @param   string $componentName Component name
	 *
	 * @return array
	 */
	public function getComponentTables($componentName)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$tablePattern = NenoHelper::getTableNamePatternBasedOnComponentName($componentName);
			$query        = $this->getQuery(true);
			$query
				->select('TABLE_NAME')
				->from('information_schema.tables')
				->where(
					array(
						'table_schema = DATABASE()',
						'table_name LIKE ' . $this->quote($tablePattern . '%')
					)
				);

			$tablesList = $this->executeQuery($query, true, true);

			if (!empty($tablesList))
			{
				NenoCache::setCacheData($cacheId, NenoHelper::convertOnePropertyObjectListToArray($tablesList));
			}
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Delete an object from the database
	 *
	 * @param   string  $table Table name
	 * @param   integer $id    Identifier
	 *
	 * @return bool
	 */
	public function deleteObject($table, $id)
	{
		$query = $this->getQuery(true);
		$query
			->delete((string) $table)
			->where('id = ' . (int) $id);

		$this->setQuery($query);

		return $this->execute() !== false;
	}

	/**
	 * Load an array using the first column of the query
	 *
	 * @return array
	 */
	public function loadArray()
	{
		/** @noinspection PhpUndefinedClassInspection */
		$list  = parent::loadRowList();
		$array = array();

		foreach ($list as $listElement)
		{
			$array[] = $listElement[0];
		}

		return $array;
	}

	/**
	 * Sync database table
	 *
	 * @param string $tableName Table name
	 *
	 * @return void
	 */
	public function syncTable($tableName)
	{
		$languages = NenoHelper::getTargetLanguages(false);
		$tables    = $this->getNenoTableList();

		foreach ($languages as $language)
		{
			$shadowTableName = $this->generateShadowTableName($tableName, $language->lang_code);

			// If the table does not exists, let's create it
			if (!in_array($shadowTableName, $tables))
			{
				$this->createShadowTables($tableName, true, $language->lang_code);
			}

			$diff = $this->tablesDiff($tableName, $shadowTableName);

			// If diff is not empty, let's try to sync both tables
			if (!empty($diff))
			{
				// Are there fields that needs to be added?
				if (!empty($diff['add']))
				{
					foreach ($diff['add'] as $field)
					{
						$this->addColumn($shadowTableName, $field->Field, $this->generateColumnType($field));
					}
				}

				// Are there fields that needs to be dropped?
				if (!empty($diff['drop']))
				{
					foreach ($diff['drop'] as $field)
					{
						$this->dropColumn($shadowTableName, $field->Field);
					}
				}
			}
		}
	}

	/**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 *
	 * @throws  RuntimeException
	 */
	public function getNenoTableList()
	{
		$tableList  = parent::getTableList();
		$onlyPrefix = NenoSettings::get('only_prefix', true);

		foreach ($tableList as $key => $table)
		{
			if (($onlyPrefix && NenoHelper::startsWith($table, $this->getPrefix())) || !$onlyPrefix)
			{
				$tableList[ $key ] = str_replace($this->getPrefix(), '#__', $table);
			}
		}

		return $tableList;
	}

	/**
	 * Get diff between tables
	 *
	 * @param   string $table1 Table 1
	 * @param   string $table2 Table 2
	 *
	 * @return array
	 */
	public function tablesDiff($table1, $table2)
	{
		$diff              = array();
		$table1Columns     = $this->getTableColumns($table1, false);
		$table2Columns     = $this->getTableColumns($table2, false);
		$table1ColumnNames = array_keys($table1Columns);
		$table2ColumnNames = array_keys($table2Columns);

		$newFields = array_diff($table1ColumnNames, $table2ColumnNames);
		$oldFields = array_diff($table2ColumnNames, $table1ColumnNames);

		if (!empty($newFields))
		{
			$diff['add'] = array();

			foreach ($newFields as $newField)
			{
				$diff['add'][] = $table1Columns[ $newField ];
			}
		}

		if (!empty($oldFields))
		{
			$diff['drop'] = array();

			foreach ($oldFields as $oldField)
			{
				$diff['drop'][] = $table2Columns[ $oldField ];
			}
		}

		return $diff;
	}

	/**
	 * Add column
	 *
	 * @param   string $tableName  Table name
	 * @param   string $columnName Column name
	 * @param   string $columnType Column type
	 *
	 * @return bool
	 */
	public function addColumn($tableName, $columnName, $columnType)
	{
		$sql = JText::sprintf('ALTER TABLE %s ADD %s %s', $this->quoteName($tableName), $this->quoteName($columnName), $columnType);
		$this->setQuery($sql);

		return $this->execute() !== false;
	}

	/**
	 * Generate
	 *
	 * @param stdClass $fieldData
	 *
	 * @return string
	 */
	protected function generateColumnType(stdClass $fieldData)
	{
		return $fieldData->Type . ($fieldData->Null == 'NO' ? ' NOT NULL' : '');
	}

	/**
	 * Drop column
	 *
	 * @param   string $tableName  Table name
	 * @param   string $columnName Column name
	 *
	 * @return bool
	 */
	public function dropColumn($tableName, $columnName)
	{
		$sql = JText::sprintf('ALTER TABLE %s DROP COLUMN %s', $this->quoteName($tableName), $this->quoteName($columnName));
		$this->setQuery($sql);

		return $this->execute() !== false;
	}

	/**
	 * Set sql propagation
	 *
	 * @param   bool $sqlPropagation SQL propagation
	 *
	 * @return void
	 */
	public function setSqlPropagation($sqlPropagation)
	{
		$this->propagateQuery = $sqlPropagation;
	}
}
