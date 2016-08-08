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
class NenoViewSettings extends JViewLegacy
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
	 * @var JPagination
	 */
	protected $pagination;

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
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		NenoHelperBackend::addSubmenu('settings');

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_neno&view=groupselements');

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getFundsAvailable());
	}
}
