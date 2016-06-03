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

// Make sure that the Neno package is defined.
if (!defined('JPATH_NENO'))
{
	$nenoLoader = JPATH_LIBRARIES . '/neno/loader.php';

	if (file_exists($nenoLoader))
	{
		JLoader::register('NenoLoader', $nenoLoader);

		// Register the Class prefix in the autoloader
		NenoLoader::init();
	}
}

if (!NenoHelperBackend::isDatabaseDriverEnabled())
{
	$app = JFactory::getApplication();
	$app->enqueueMessage('Please enable the plugin to use Neno', 'error');

	NenoLog::log('Plugin disabled in frontend', '', 0, NenoLog::PRIORITY_ERROR);

	$app->setUserState('com_plugins.plugins.filter.search', 'neno');
	$app->redirect('index.php?option=com_plugins');
}

// Include dependencies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Neno');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
