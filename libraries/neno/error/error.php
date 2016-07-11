<?php

/**
 * @package     Neno
 * @subpackage  Error
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class Error
 */
class NenoError
{
	/**
	 * Error handler
	 *
	 * @param int    $errorNumber  Error code number
	 * @param string $errorMessage Error message
	 * @param string $file         File where the error occurs
	 * @param int    $line         Line inside of the file
	 *
	 * @return void
	 */
	public static function error($errorNumber, $errorMessage, $file, $line)
	{
		$errorType = 'none';
		switch ($errorNumber)
		{
			case E_ERROR:
				$errorType = 'error';
				break;
			case E_WARNING:
				$errorType = 'warning';
				break;
			case E_PARSE:
				$errorType = 'parse';
				break;
			case E_CORE_ERROR:
				$errorType = 'core error';
				break;
			case E_CORE_WARNING:
				$errorType = 'core warning';
				break;
			case E_USER_ERROR:
				$errorType = 'user error';
				break;
			case E_USER_WARNING:
				$errorType = 'user warning';
				break;
			case E_RECOVERABLE_ERROR:
				$errorType = 'recoverable error';
				break;
		}

		if ($errorType !== 'none')
		{
			NenoLog::log("Encountered $errorType error in $file, line $line: $errorMessage", '', 0, NenoLog::PRIORITY_ERROR);
		}
	}
}