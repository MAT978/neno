<?php

/**
 * @package     Neno
 * @subpackage  Database
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_NENO') or die;

// If the database type is mysqli, let's created a middle class that inherit from the Mysqli drive
if (JFactory::getConfig()->get('dbtype') === 'mysqli')
{
	/**
	 * Class CommonDriver for Mysqli extension
	 *
	 * @since  1.0
	 */
	class CommonDriver extends JDatabaseDriverMysqli
	{
	}
}
else
{
	// @TODO JDatabaseDriverMysql is already deprecated, so we should remove this class when the minimum PHP version don't support this extension
	/**
	 * Class CommonDriver for Mysql extension
	 *
	 * @since  1.0
	 */
	class CommonDriver extends JDatabaseDriverMysql
	{
	}
}

/**
 * Database driver class extends from Joomla Platform Database Driver class
 *
 * @package     Neno
 * @subpackage  Database
 * @since       1.0
 */
class NenoDatabaseDriverMysqlx extends CommonDriver
{
	/**
	 * Tables configured to be translatable
	 *
	 * @var array
	 */
	private $manifestTables;

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

			$sql = 'ALTER TABLE ' . $shadowTable . ' AUTO_INCREMENT= ' . (int) $data[0]->AUTO_INCREMENT;
			$this->executeQuery($sql);

			return true;
		}
		catch (RuntimeException $ex)
		{
			return false;
		}
	}

	/**
	 * @param bool $new
	 *
	 * @return JDatabaseQuery|string
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
			// Derive the class name from the driver.
			$class = 'NenoDatabaseQuery' . ucfirst($this->name);

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
	 * @param string $sql
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		// Check if the query should be parsed.
		if ($this->hasToBeParsed($sql))
		{
			// Get query type
			$queryType = NenoDatabaseParser::getQueryType($sql);

			// Get table name
			$tableName = NenoDatabaseParser::getSourceTableName($sql);

			// If the query is a select statement let's get the sql query using its shadow table name
			if ($queryType === NenoDatabaseParser::SELECT_QUERY && $this->isTranslatable($tableName))
			{
				$sql = NenoDatabaseParser::getSqlQueryUsingShadowTable($sql);
			}
		}

		// Call to the parent replacePrefix
		return parent::replacePrefix($sql, $prefix);
	}

	/**
	 * @param string $sql
	 *
	 * @return bool
	 */
	private function hasToBeParsed($sql)
	{
		$ignoredQueryRegex = array(
			'/show (.+)/i',
			'/#__neno_(.+)/'
		);

		foreach ($ignoredQueryRegex as $queryRegex)
		{
			if (preg_match($queryRegex, $sql))
			{
				return false;
			}
		}

		return true;

	}

	/**
	 * Check if a table is translatable
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function isTranslatable($tableName)
	{
		return in_array($tableName, $this->manifestTables);
	}

	/**
	 * Execute a sql preventing to lose the query previously assigned.
	 *
	 * @param mixed   $sql                   JDatabaseQuery object or SQL query
	 * @param boolean $preservePreviousQuery True if the previous query will be saved before, false otherwise
	 * @param boolean $returnObjectList      True if the method should return a list of object as query result, false otherwise
	 *
	 * @return void|array
	 */
	public function executeQuery($sql, $preservePreviousQuery = true, $returnObjectList = false)
	{
		$currentSql = null;

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

		if ($returnObjectList)
		{
			return $returnObject;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool|mixed
	 */
	public function execute()
	{
		try
		{
			$result = parent::execute();

			//$this->processQueryExecution();

			return $result;
		}
		catch (RuntimeException $ex)
		{
			echo $ex->getMessage() . "\n";

			//NenoLog::log($ex, NenoLog::PRIORITY_ERROR);

			return false;
		}
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

		foreach ($manifestTablesObjectList as $object)
		{
			$this->manifestTables[] = $object->table_name;
		}
	}

	/**
	 * Delete all the shadow tables related to a table
	 *
	 * @param string $tableName
	 *
	 * @return void
	 */
	public function deleteShadowTables($tableName)
	{
		$defaultLanguage = JFactory::getLanguage()->getDefault();
		$knownLanguages  = NenoHelper::getLanguages();

		foreach ($knownLanguages as $knownLanguage)
		{
			if ($knownLanguage->lang_code !== $defaultLanguage)
			{
				$shadowTableName = NenoDatabaseParser::generateShadowTableName($tableName, $knownLanguage->lang_code);
				$this->dropTable($shadowTableName);
			}
		}
	}

	/**
	 * Create all the shadow tables needed for
	 *
	 * @param   string $tableName
	 *
	 * @return void
	 */
	public function createShadowTables($tableName)
	{
		$defaultLanguage = JFactory::getLanguage()->getDefault();
		$knownLanguages  = NenoHelper::getLanguages();

		$createStatement = $this->getTableCreate($tableName)[$tableName];

		foreach ($knownLanguages as $knownLanguage)
		{
			if ($knownLanguage->lang_code !== $defaultLanguage)
			{
				$shadowTableName            = NenoDatabaseParser::generateShadowTableName($tableName, $knownLanguage->lang_code);
				$shadowTableCreateStatement = str_replace($tableName, $shadowTableName, $createStatement);

				$this->executeQuery($shadowTableCreateStatement);
				$this->copyContentElementsFromSourceTableToShadowTables($tableName, $shadowTableName);
			}
		}


	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $tables
	 *
	 * @return array
	 */
	public function getTableCreate($tables)
	{
		$result = parent::getTableCreate($tables);

		// Get all the keys for the create table array
		$tableNames = array_keys($result);

		// Go through all the create statements and make them IF NOT EXISTS create statements
		foreach ($tableNames as $tableName)
		{
			$result[$tableName] = str_replace(array('CREATE TABLE', $this->getPrefix()), array('CREATE TABLE IF NOT EXISTS', '#__'), $result[$tableName]);
		}

		return $result;
	}

	/**
	 * Copy all the content to the shadow table
	 *
	 * @param   string $sourceTableName Name of the source table
	 * @param   string $shadowTableName Name of the shadow table
	 *
	 * @return void
	 */
	public function copyContentElementsFromSourceTableToShadowTables($sourceTableName, $shadowTableName)
	{
		$columns = array_map(array($this, 'quoteName'), array_keys($this->getTableColumns($sourceTableName)));
		$query   = 'REPLACE INTO ' . $shadowTableName . ' (' . implode(',', $columns) . ' ) SELECT * FROM ' . $sourceTableName;
		$this->executeQuery($query);
	}

	/**
	 * Get primary key of a table
	 *
	 * @param string $tableName
	 *
	 * @return string|null
	 */
	public function getPrimaryKey($tableName)
	{
		$query   = 'SHOW INDEX FROM ' . $tableName . ' WHERE Key_name = \'PRIMARY\'';
		$results = $this->executeQuery($query, true, true);

		if (!empty($results))
		{
			return $results[0]->Column_name;
		}

		return null;
	}

	/**
	 * Get all the tables that belong to a particular component.
	 *
	 * @param string $componentName
	 *
	 * @return array
	 */
	public function getComponentTables($componentName)
	{
		$tablePattern = NenoHelper::getTableNamePatternBasedOnComponentName($componentName);
		$query        = 'SHOW TABLES LIKE ' . $this->quote($tablePattern . '%');
		$tablesList   = $this->executeQuery($query, true, true);

		return NenoHelper::convertOnePropertyObjectListToArray($tablesList);
	}

	/**
	 *
	 *
	 * @param string  $table
	 * @param integer $id
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
	 *
	 */
	private function processQueryExecution()
	{
		$sqlParsed = NenoDatabaseParser::parseQuery($this->sql);

		// Process insertions
		if (!empty($sqlParsed['INSERT']) || !empty($sqlParsed['REPLACE']))
		{
//			Kint::dump($sqlParsed);
//			exit;
		}

		// Process updating
	}
}
