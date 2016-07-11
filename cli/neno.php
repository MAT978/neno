<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);

// Added to prevent the JED scanner from flagging this file
defined('_JEXEC') or die;

if (file_exists(dirname(__DIR__) . '/defines.php'))
{
    include_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Library language
$lang = JFactory::getLanguage();

// Try the files_joomla file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_joomla.sys', JPATH_SITE, NULL, false, false)
// Fallback to the files_joomla file in the default language
|| $lang->load('files_joomla.sys', JPATH_SITE, NULL, true);

/**
 * A command line cron job to attempt to remove files that should have been deleted at update.
 *
 * @since  3.0
 */
class NenoCli extends JApplicationCli
{
    /**
     * @var \Joomla\Registry\Registry
     */
    protected $userState;

    /**
     * Entry point for CLI script
     *
     * @return  void
     *
     * @since   3.0
     */
    public function doExecute()
    {
        $nenoLoader = JPATH_LIBRARIES . '/neno/loader.php';

        if (file_exists($nenoLoader))
        {
            JLoader::register('NenoLoader', $nenoLoader);

            // Register the Class prefix in the autoloader
            NenoLoader::init();

            JFactory::$application = $this;

            // Load custom driver.
            JFactory::$database = NULL;
            JFactory::$database = NenoFactory::getDbo();
        }

        $this->userState = new Joomla\Registry\Registry();

        NenoTaskMonitor::runTask();
    }

    public function getUserState($key)
    {
        return $this->userState->get($key);
    }

    public function setUserState($key, $value)
    {
        $this->userState->set($key, $value);
    }
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('NenoCli')->execute();
