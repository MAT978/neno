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
class NenoViewGroupsElements extends NenoView
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
		$this->state = $this->get('State');
		$this->items = NenoHelper::convertNenoObjectListToJobjectList($this->get('Items'));

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
		JToolbarHelper::addNew('addGroup', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_ADD_GROUP'));
		JToolbarHelper::custom('moveelementconfirm.show', 'move', 'move', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_MOVE_ELEMENTS'), true);
		JToolbarHelper::custom('groupselements.scanForContent', 'loop', 'loop', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_SCAN_FOR_CONTENT'), true);
		JToolbarHelper::custom('groupselements.moveTranslationsToTarget', 'download', 'download', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_MOVE_TRANSLATED_CONTENT'), true);
		JToolbarHelper::custom('groupselements.checkIntegrity', 'cogs', 'cogs', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_CHECK_INTEGRITY'), true);
		JToolbarHelper::custom('groupselements.refreshWordCount', 'refresh', 'refresh', JText::_('COM_NENO_VIEW_GROUPSELEMENTS_BTN_REFRESH_WORD_COUNT'), true);

		$toolbar = JToolbar::getInstance();
		$toolbar->addButtonPath(JPATH_NENO . '/button');
		$toolbar->appendButton('TC', NenoHelperApi::getFundsAvailable());

		$this->extraSidebar = NenoHelperBackend::getSidebarInfobox('groupselements');
	}
}
