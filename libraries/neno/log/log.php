<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.log.log');

/**
 * Neno Log class
 *
 * @since  1.0
 */
class NenoLog extends JLog
{
	/**
	 * Error priority level
	 */
	const PRIORITY_ERROR = 1;

	/**
	 * Info priority level
	 */
	const PRIORITY_INFO = 2;

	/**
	 * Debug priority level
	 */
	const PRIORITY_DEBUG = 3;

	/**
	 * A static method that allows logging of errors and messages
	 *
	 * @param   string  $string         The log line that should be saved
	 * @param   integer $level          1=error, 2=info, 3=debug
	 * @param   boolean $displayMessage Weather or not the logged message should be displayed to the user
	 *
	 * @return bool true on success
	 */
	public static function log($string, $level = 2, $displayMessage = false)
	{
		// Add an extra tab to debug messages
		if ($level > 2)
		{
			$string = "\t" . $string;
		}

		// Get jLog priority
		$priority = self::getJLogPriorityFromDebugLevel($level);

		// Setup the logging method
		self::setLogMethod();

		// Check if log entry should be made
		if (self::checkAddLog($level))
		{
			// Add the log entry
			self::add($string, $priority, 'com_neno');
		}

		if ($displayMessage === true)
		{
			JFactory::getApplication()->enqueueMessage($string);
		}

		return true;
	}

	/**
	 * Convert our simple priority 1,2,3 to appropriate JLog error integer
	 *
	 * @param   integer $priority 1,2 or 3
	 *
	 * @return int JLog priority integer
	 */
	private static function getJLogPriorityFromDebugLevel($priority)
	{
		if ($priority == self::PRIORITY_ERROR)
		{
			return self::ERROR;
		}
		else
		{
			if ($priority == self::PRIORITY_INFO)
			{
				return self::INFO;
			}
			else
			{
				return self::DEBUG;
			}
		}
	}

	/**
	 * Set Log method
	 *
	 * @return void
	 */
	public static function setLogMethod()
	{
		self::addLogger(
			array ('text_entry_format' => "{DATETIME}\t{PRIORITY}\t\t{MESSAGE}", 'text_file' => 'neno_log.php'),
			self::ALL,
			array ('com_neno')
		);
	}

	/**
	 * Method to check if log entry should be made
	 *
	 * @param   integer $level 1,2 or 3
	 *
	 * @return boolean
	 */
	private static function checkAddLog($level)
	{
		$debugMode = 1;

		// Check if priority is debug
		if ($level == self::PRIORITY_DEBUG)
		{
			// Check if debug mode is on
			if (!JDEBUG)
			{
				$debugMode = 0;
			}
		}

		return $debugMode;
	}

	/**
	 * Add an entry into the Log
	 *
	 * @param   mixed  $entry    Log entry
	 * @param   int    $priority Entry Priority
	 * @param   string $category Entry Category
	 * @param   null   $date     Entry Date
	 *
	 * @return void
	 */
	public static function add($entry, $priority = self::INFO, $category = '', $date = null)
	{
		// Automatically instantiate the singleton object if not already done.
		if (empty(self::$instance) || !(self::$instance instanceof NenoLog))
		{
			self::$instance = new NenoLog;
		}

		// If the entry object isn't a JLogEntry object let's make one.
		if (!($entry instanceof JLogEntry))
		{
			$entry = new JLogEntry((string) $entry, $priority, $category, $date);
		}

		self::$instance->addLogEntry($entry);
	}

	/**
	 * Method to add an entry to the appropriate loggers.
	 *
	 * @param   JLogEntry $entry The JLogEntry object to send to the loggers.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	protected function addLogEntry(JLogEntry $entry)
	{
		// Find all the appropriate loggers based on priority and category for the entry.
		$loggers = $this->findLoggers($entry->priority, $entry->category);

		foreach ((array) $loggers as $signature)
		{
			// Attempt to instantiate the logger object if it doesn't already exist.
			if (empty($this->loggers[$signature]))
			{
				// Prefix for Joomla loggers
				$prefix = 'JLogLogger';

				$class = $prefix . ucfirst($this->configurations[$signature]['logger']);

				if (class_exists($class))
				{
					$this->loggers[$signature] = new $class($this->configurations[$signature]);
				}
				else
				{
					throw new RuntimeException('Unable to create a ' . $prefix . ' instance: ' . $class);
				}
			}

			// Add the entry to the logger.
			/** @noinspection PhpUndefinedMethodInspection */
			$this->loggers[$signature]->addEntry(clone $entry);
		}
	}
}
