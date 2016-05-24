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
	/****** Priority Constants ******/
	/**
	 * Error priority level
	 */
	const PRIORITY_ERROR = 1;
	/**
	 * Warning priority level
	 */
	const PRIORITY_WARNING = 2;
	/**
	 * Info priority level
	 */
	const PRIORITY_INFO = 3;
	/**
	 * Debug priority level
	 */
	const PRIORITY_VERBOSE = 4;
	/****** Action Constants ******/
	/**
	 * Move translation action
	 */
	const ACTION_MOVE_TRANSLATION = 'move_translation';
	/**
	 * Extension discovered action
	 */
	const ACTION_EXTENSION_DISCOVERED = 'extension_discovered';
	/**
	 * Content discovered action
	 */
	const ACTION_CONTENT_DISCOVERED = 'content_discovered';
	/**
	 * Language installed action
	 */
	const ACTION_LANGUAGE_INSTALLED = 'language_installed';

	/**
	 * A static method that allows logging of errors and messages
	 *
	 * @param   string  $string         The log line that should be saved
	 * @param   string  $action         Action executed @see constants
	 * @param   int     $trigger        Who triggers this log entry
	 * @param   integer $level          @see constants
	 * @param   boolean $displayMessage Weather or not the logged message should be displayed to the user
	 *
	 * @return bool true on success
	 */
	public static function log($string, $action, $trigger = 0, $level = self::PRIORITY_INFO, $displayMessage = false)
	{
		$entry = self::generateNenoEntry($string, $level, NULL, $action, $trigger);

		// Add the log entry
		self::add($entry);

		if ($displayMessage === true)
		{
			JFactory::getApplication()
			  ->enqueueMessage($string, self::getAppMessageLevelByLogPriority($level));
		}

		return true;
	}

	/**
	 * Get App level message based on log entry priority
	 *
	 * @param int $logPriority @see constant
	 *
	 * @return string
	 */
	protected static function getAppMessageLevelByLogPriority($logPriority)
	{
		switch ($logPriority)
		{
			case self::PRIORITY_ERROR:
				$appMessageLevel = 'error';
				break;
			case self::PRIORITY_WARNING:
				$appMessageLevel = 'warning';
				break;
			default:
				$appMessageLevel = 'message';
				break;
		}

		return $appMessageLevel;
	}

	/**
	 * Generate log entry objects
	 *
	 * @param string   $message
	 * @param DateTime $date
	 * @param int      $priority
	 * @param string   $action
	 * @param int      $trigger
	 *
	 * @return \NenoLogEntry
	 */
	protected static function generateNenoEntry($message, $priority, $date = NULL, $action = '', $trigger = 0)
	{
		$entryData = array(
		  'timeAdded' => $date,
		  'action'    => $action,
		  'message'   => (string) $message,
		  'level'     => $priority,
		  'trigger'   => $trigger,
		);

		$logEntry = new NenoLogEntry($entryData);

		return $logEntry;
	}

	/**
	 * Add an entry into the Log
	 *
	 * @param   mixed         $entry    Log entry
	 * @param   int           $priority Entry Priority
	 * @param   string        $category Entry Category
	 * @param   null|DateTime $date     Entry Date
	 *
	 * @return void
	 */
	public static function add($entry, $priority = self::PRIORITY_INFO, $category = '', $date = NULL)
	{
		// Automatically instantiate the singleton object if not already done.
		if (empty(self::$instance) || !(self::$instance instanceof NenoLog))
		{
			self::$instance = new NenoLog;
		}

		// If the entry object isn't a JLogEntry object let's make one.
		if (!($entry instanceof NenoLogEntry))
		{
			$entry = self::generateNenoEntry($entry, $priority, $date);
		}

		$entry->persist();
	}
}
