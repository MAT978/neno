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
class NenoViewJobs extends JViewLegacy
{
	/**
	 * @var JForm
	 */
	public $filterForm;

	/**
	 * @var JForm
	 */
	public $activeFilters;

	/**
	 * @var array
	 */
	public $groups;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var JPagination
	 */
	protected $pagination;

	/**
	 * @var Joomla\Registry\Registry
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $sidebar;

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

		NenoHelperBackend::addSubmenu('externaltranslations');
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
		JHtmlSidebar::setAction('index.php?option=com_neno&view=strings');

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getTCAvailable());
	}

	/**
	 * Get an array of fields to sort by
	 *
	 * @return array
	 */
	protected function getSortFields()
	{
		return array (
			'a.id'           => JText::_('JGRID_HEADING_ID'),
			'a.string'       => JText::_('COM_NENO_SOURCES_STRING'),
			'a.constant'     => JText::_('COM_NENO_SOURCES_CONSTANT'),
			'a.lang'         => JText::_('COM_NENO_SOURCES_LANG'),
			'a.extension'    => JText::_('COM_NENO_SOURCES_EXTENSION'),
			'a.time_added'   => JText::_('COM_NENO_SOURCES_TIME_ADDED'),
			'a.time_changed' => JText::_('COM_NENO_SOURCES_TIME_CHANGED'),
			'a.time_deleted' => JText::_('COM_NENO_SOURCES_TIME_DELETED'),
			'a.version'      => JText::_('COM_NENO_SOURCES_VERSION'),
		);
	}

	/**
	 * Check if a field has been imported already
	 *
	 * @param   string $fieldName Field name to check
	 * @param   array  $fieldList List of fields that have been imported.
	 *
	 * @return bool
	 */
	protected function isAlreadyChecked($fieldName, array $fieldList)
	{
		foreach ($fieldList as $field)
		{
			if ($field->field === $fieldName)
			{
				return true;
			}
		}

		return false;
	}
}
