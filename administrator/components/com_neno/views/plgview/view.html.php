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
class NenoViewPlgViewsa extends JViewLegacy
{
	/**
	 * @var string
	 * @since 2.2.0
	 */
	protected $sidebar;

	/**
	 * @var string
	 * @since 2.2.0
	 */
	protected $view;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return void
	 *
	 * @throws Exception This will happen if there are errors during the process to load the data
	 *
	 * @since 2.2.0
	 */
	public function display($tpl = null)
	{
		$this->view = $this->get('View');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		NenoHelperBackend::addSubmenu('debug');

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getFundsAvailable());

		$this->sidebar = JHtmlSidebar::render();

		$this->extraSidebar = NenoHelperBackend::getSidebarInfobox('debug');
		JToolbarHelper::title(JText::_('COM_NENO_DASHBOARD_TITLE'), 'screen');

		parent::display($tpl);
	}
}
