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

/**
 * Installs some files that the installer does not move.
 *
 * @since  1.0
 */
class com_NenoInstallerScript
{
	/**
	 * Copying files
	 *
	 * @param   string                     $type   Installation type
	 * @param   JInstallerAdapterComponent $parent Installation adapter
	 *
	 * @return bool False if something happens
	 */
	public function postflight($type, $parent)
	{
		$app              = JFactory::getApplication();
		$installationPath = $parent->getParent()->getPath('source');

		jimport('joomla.filesystem.folder');

		// If the layout folder exists, let's delete them first
		if (JFolder::exists(JPATH_ROOT . '/layouts/libraries/neno'))
		{
			JFolder::delete(JPATH_ROOT . '/layouts/libraries/neno');
		}

		// Moving Layouts
		$layoutsError = JFolder::move($installationPath . '/layouts', JPATH_ROOT . '/layouts/libraries/neno');

		if ($layoutsError !== true)
		{
			$app->enqueueMessage('Error installing layouts: ' . $layoutsError);
		}

		// If the media folder exists, let's delete them first
		if (JFolder::exists(JPATH_ROOT . '/media/neno'))
		{
			JFolder::delete(JPATH_ROOT . '/media/neno');
		}

		// Moving media files
		$mediaError = JFolder::move($installationPath . '/media', JPATH_ROOT . '/media/neno');

		if ($mediaError !== true)
		{
			$app->enqueueMessage('Error installing layouts: ' . $mediaError);
		}

		return true;
	}

	/**
	 * Copying files
	 *
	 * @return bool False if something happens
	 */
	public function uninstall()
	{
		JFolder::delete(JPATH_ROOT . '/layouts/libraries/neno');
		JFolder::delete(JPATH_ROOT . '/media/neno');
	}
}
