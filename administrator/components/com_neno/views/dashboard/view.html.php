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
	 * @var stdClass
	 */
	protected $menuItemAliasIssue;

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
	public function display($tpl = NULL)
	{
		$this->state                       = $this->get('State');
		$this->items                       = $this->get('Items');
		$this->isLanguageSwitcherPublished = $this->getModel()
		  ->IsSwitcherPublished();
		$this->menuItemAliasIssue          = NenoHelperIssue::getIssuesByCode(NenoHelperIssue::MENU_ITEMS_HAVE_SAME_ALIAS);

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
		$toolbar->appendButton('TC', NenoHelperApi::getFundsAvailable());
		
		$this->sidebar = NenoHtmlSidebar::render();

		$this->extraSidebar = NenoHelperBackend::getSidebarInfobox('dashboard');

		parent::display($tpl);
	}
}
