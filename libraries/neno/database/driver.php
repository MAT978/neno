<?php

/**
 * @package     Neno
 * @subpackage  Database
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Database driver class extends from Joomla Platform Database Driver class
 *
 * @package     Neno
 * @subpackage  Database
 * @since       1.0
 */
abstract class NenoDatabaseDriver extends JDatabaseDriver
{
	/**
	 * {@inheritdoc}
	 *
	 * @param   array $options Configuration options
	 *
	 * @return JDatabaseDriver
	 *
	 * @since 1.0
	 */
	public static function getInstance($options = array())
	{
		$options['driver'] = (isset($options['driver'])) ?
			preg_replace('/[^A-Z0-9_\.-]/i', '', $options['driver']) : 'mysqli';

		$options['database'] = (isset($options['database'])) ? $options['database'] : null;

		$options['select'] = (isset($options['select'])) ? $options['select'] : true;

		// Get an option hash to identify the instance
		$driverSignature = md5(serialize($options));

		// Check if the driver has been already instantiated
		if (empty(self::$instances[$driverSignature]))
		{
			// If the class doesn't exists, we cannot work with this driver.
			if (!self::isMysql($options['driver']))
			{
				// Let's using parent method
				return parent::getInstance($options);
			}

			// Let's create our driver instance using the options given.s
			try
			{
				/* @var $instance NenoDatabaseDriverMysqlx */
				$instance = new NenoDatabaseDriverMysqlx($options);
				$instance->refreshTranslatableTables();
			}
			catch ( RuntimeException $ex )
			{
				throw new RuntimeException(
					sprintf('Unable to connect to the database. Error: %s', $ex->getMessage())
				);
			}

			// Save the instance into the instances set.
			self::$instances[$driverSignature] = $instance;

			// Load the tables configured to be translatable
			$instance->refreshTranslatableTables();
		}

		return self::$instances[$driverSignature];
	}

	/**
	 * Check if the driver is MySQL
	 *
	 * @param   string $driver driver name
	 *
	 * @return boolean True if it's a mysql driver, false otherwise
	 */
	public static function isMysql($driver)
	{
		return strpos(strtolower($driver), 'mysql') !== false;
	}

	/**
	 * Method to clear all the instances.
	 *
	 * @return void
	 */
	public static function clearInstances()
	{
		self::$instances = null;
	}
}
