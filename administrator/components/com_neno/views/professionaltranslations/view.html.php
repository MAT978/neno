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
class NenoViewProfessionalTranslations extends JViewLegacy
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
	 * @var int
	 */
	protected $fundsNeeded;
	/**
	 * @var string
	 */
	protected $extraSidebar;
	/**
	 * @var string
	 */
	protected $comment;
	/**
	 * @var bool
	 */
	protected $fundsNeededToBeAdded;
	/**
	 * @var float
	 */
	protected $fundsAvailable;

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
		$this->state                = $this->get('State');
		$this->items                = $this->get('Items');
		$this->fundsNeeded          = $this->get('FundsNeeded');
		$this->comment              = $this->get('Comment');
		$this->fundsAvailable       = NenoHelperApi::getTcAvailable();
		$this->fundsNeededToBeAdded = $this->fundsAvailable < $this->fundsNeeded;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		NenoHelperBackend::addSubmenu('professionaltranslations');
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
		JHtmlSidebar::setAction('index.php?option=com_neno&view=externaltranslations');

		$this->extraSidebar = '';
	}
}
