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
 * NenoViewGroupsElements class
 *
 * @since  1.0
 */
class NenoViewIssues extends JViewLegacy
{
	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var Joomla\Registry\Registry
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $sidebar;

	/**
	 * @var string
	 */
	protected $extraSidebar;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return void
	 *
	 * @throws Exception This will happen if there are errors during the process to load the data
	 *
	 * @since 1.0
	 */
	public function display($tpl = null)
	{
		$this->state                       = $this->get('State');
		$this->items                       = $this->get('Items');
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getTcAvailable());

		JToolbarHelper::title(JText::_('COM_NENO_ISSUES_TITLE'), 'screen');

		JToolbarHelper::custom('debug.fixMenus', 'refresh', 'refresh', JText::_('COM_NENO_DASHBOARD_FIX_MENU_BUTTON'), false);

		JToolbarHelper::custom('debug.fixContentConfigurationIssue', 'wrench', 'wrench', JText::_('COM_NENO_DASHBOARD_FIX_CONTENT_BUTTON'), false);

		JToolbarHelper::custom('debug.fixNullIssue', 'lightning', 'lightning', JText::_('COM_NENO_DASHBOARD_FIX_NULL_BUTTON'), false);
		
		NenoHelperBackend::addSubmenu('debug');
		$this->sidebar = JHtmlSidebar::render();

		if ($tpl == 'list')
		{
			$this->setLayout($tpl);
			$tpl = null;
		}

		parent::display($tpl);
	}
}
