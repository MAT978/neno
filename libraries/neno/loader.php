<?php

/**
 * @package    Neno
 *
 * @copyright  Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

// Define Neno path constant
if (!defined('JPATH_NENO'))
{
	define('JPATH_NENO', dirname(__FILE__));
}

if (!defined('JPATH_NENO_LAYOUTS'))
{
	define('JPATH_NENO_LAYOUTS', JPATH_ROOT . '/layouts/libraries/neno');
}

if (!defined('JPATH_NENO_CONTENT_FILES'))
{
	define('JPATH_NENO_CONTENT_FILES', JPATH_NENO . '/contentelements');
}

/**
 * Class to handle dependencies
 *
 * @package  Neno
 *
 * @since    1.0
 */
class NenoLoader
{
	/**
	 * Adding Neno and external libraries to the Class Loader
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public static function init()
	{
		// Registering Neno libraries prefix
		JLoader::registerPrefix('Neno', JPATH_NENO);

		set_error_handler(array( 'NenoError', 'error' ));
	}
}
