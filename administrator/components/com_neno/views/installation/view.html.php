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
class NenoViewInstallation extends JViewLegacy
{
	/**
	 * @var string
	 */
	public $sidebar;

	/**
	 * Render view
	 *
	 * @param   null|string $tpl Template name
	 *
	 * @return mixed
	 */
	public function display($tpl = null)
	{
		NenoHelperBackend::addSubmenu('');

		$this->sidebar = JHtmlSidebar::render();

		return parent::display($tpl);
	}
}
