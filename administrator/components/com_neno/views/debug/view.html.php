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

jimport('joomla.application.component.view');

/**
 * NenoViewGroupsElements class
 *
 * @since  1.0
 */
class NenoViewDebug extends JViewLegacy
{
	/**
	 * @var string
	 */
	protected $sidebar;

	/**
	 * Show view
	 *
	 * @param   string|null $tpl Template to use
	 *
	 * @return mixed
	 */
	public function display($tpl = null)
	{
		JToolbarHelper::custom('debug.fixMenus', 'refresh', 'refresh', JText::_('COM_NENO_DASHBOARD_FIX_MENU_BUTTON'), false);

		JToolbarHelper::custom('debug.fixContentConfigurationIssue', 'wrench', 'wrench', JText::_('COM_NENO_DASHBOARD_FIX_CONTENT_BUTTON'), false);

		JToolbarHelper::custom('debug.fixNullIssue', 'lightning', 'lightning', JText::_('COM_NENO_DASHBOARD_FIX_NULL_BUTTON'), false);

		JToolbarHelper::custom('debug.listIssues', 'cube', 'cube', JText::_('COM_NENO_ISSUES_TITLE'), false);

		JToolbarHelper::title(JText::_('COM_NENO_DASHBOARD_TITLE'), 'screen');

		NenoHelperBackend::addSubmenu('debug');
		$this->sidebar = JHtmlSidebar::render();

		return parent::display($tpl);
	}
}
