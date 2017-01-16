<?php

/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

/**
 * Manifest Groups & Elements controller class
 *
 * @since  1.0
 */
class NenoControllerMaintenance extends JControllerAdmin
{
	/**
	 * Do maintenance
	 *
	 * @since 2.1.32
	 */
	public function doMaintenance()
	{
		NenoHelperChk::createBacklinkMetadataFromOldSystem();

		if (NenoHelperApi::isPremium())
		{
			NenoHelperChk::removeBacklink();
		}

		echo 'ok';

		JFactory::getApplication()->close();
	}
}
