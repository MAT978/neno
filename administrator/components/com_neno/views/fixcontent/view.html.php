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
class NenoViewDashboard extends JViewLegacy
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
	 * @var bool
	 */
	protected $isLanguageSwitcherPublished;

	/**
	 * @var JForm
	 */
	protected $positionField;

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
		$this->isLanguageSwitcherPublished = $this->getModel()->IsSwitcherPublished();

		if (!$this->isLanguageSwitcherPublished)
		{
			$this->positionField = $this->get('PositionField');
		}


		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		NenoHelperBackend::addSubmenu('dashboard');

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getTcAvailable());

		JToolbarHelper::custom('fixMenus', 'refresh', 'refresh', JText::_('COM_NENO_DASHBOARD_FIX_MENU_BUTTON'), false);

		JToolbarHelper::custom('dashboard.fixContentConfigurationIssue', 'wrench', 'wrench', JText::_('COM_NENO_DASHBOARD_FIX_CONTENT_BUTTON'), false);

		JToolbarHelper::custom('fixNullIssue', 'lightning', 'lightning', JText::_('COM_NENO_DASHBOARD_FIX_NULL_BUTTON'), false);

		JToolbarHelper::title(JText::_('COM_NENO_DASHBOARD_TITLE'), 'screen');

		$this->sidebar = JHtmlSidebar::render();

		$this->extraSidebar = NenoHelperBackend::getSidebarInfobox('dashboard');

		parent::display($tpl);
	}
}
