<?php
/**
 * @package     Neno
 * @subpackage  Views
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * View to edit
 *
 * @since  1.0
 */
class NenoViewInstallation extends NenoView
{
	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 *
	 * @since 2.2.0
	 */
	public function hasSidebar()
	{
		return false;
	}


}
