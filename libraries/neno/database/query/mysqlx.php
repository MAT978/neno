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
	class CommonQuery extends JDatabaseQueryMysql
	{

	}
}
else
{
	class CommonQuery extends JDatabaseQueryMysqli
	{

	}
}

/**
 * Class LingoDatabaseQuery
 *
 * @since  1.0
 */
class NenoDatabaseQueryMysqlx extends CommonQuery
{
	/**
	 * @var JDatabaseQueryElement
	 */
	protected $insert = null;

	/**
	 * Set a replace statement
	 *
	 * @param   string $table Table name
	 *
	 * @return NenoDatabaseQueryMysqlx
	 */
	public function replace($table)
	{
		$this->type   = 'insert';
		$this->insert = new JDatabaseQueryElement('REPLACE INTO', $table);

		return $this;
	}

	/**
	 * Create Update join statement
	 *
	 * @param string $tableDestination Destination table
	 * @param string $tableSource      Source table
	 *
	 * @return $this
	 */
	public function updateJoin($tableDestination, $tableSource)
	{
		$this->type   = 'update';
		$this->update = new JDatabaseQueryElement('UPDATE ' . $tableDestination . ' JOIN', $tableSource);

		return $this;
	}

	/**
	 * Add a table name to the INSERT IGNORE clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 *
	 * Usage:
	 * $query->insert('#__a')->set('id = 1');
	 * $query->insert('#__a')->columns('id, title')->values('1,2')->values('3,4');
	 * $query->insert('#__a')->columns('id, title')->values(array('1,2', '3,4'));
	 *
	 * @param   mixed    $table           The name of the table to insert data into.
	 * @param   boolean  $incrementField  The name of the field to auto increment.
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 *
	 * @since   11.1
	 */
	public function insertIgnore($table, $incrementField=false)
	{
		$this->type = 'insert';
		$this->insert = new JDatabaseQueryElement('INSERT IGNORE INTO', $table);
		$this->autoIncrementField = $incrementField;

		return $this;
	}
}
