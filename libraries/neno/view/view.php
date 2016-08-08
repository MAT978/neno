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
 * NenoView class
 *
 * @since  1.0
 */
class NenoView extends JViewLegacy
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
	 * @since 2.2.0
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$input = JFactory::getApplication()->input;
		NenoHelperBackend::addSubmenu($input->getCmd('view'));

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getFundsAvailable());

		if ($this->hasSidebar())
		{
			$this->sidebar      = NenoHtmlSidebar::render();
			$this->extraSidebar = NenoHelperBackend::getSidebarInfobox($input->getCmd('view'));
		}

		$this->addToolbar();


		parent::display($tpl);
	}

	protected function addToolbar()
	{
	}

	/**
	 *
	 * @return bool
	 *
	 * @since 2.2.0
	 */
	public function hasSidebar()
	{
		return true;
	}
}
