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
class NenoViewDebug extends NenoView
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
	 * @var JPagination
	 */
	protected $pagination;

	/**
	 * @var JForm
	 */
	public $filterForm;

	/**
	 * @var
	 */
	public $activeFilters;

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
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		JToolbarHelper::custom('debug.fixMenus', 'refresh', 'refresh', JText::_('COM_NENO_DASHBOARD_FIX_MENU_BUTTON'), false);
		JToolbarHelper::custom('debug.fixContentConfigurationIssue', 'wrench', 'wrench', JText::_('COM_NENO_DASHBOARD_FIX_CONTENT_BUTTON'), false);
		JToolbarHelper::custom('debug.fixNullIssue', 'lightning', 'lightning', JText::_('COM_NENO_DASHBOARD_FIX_NULL_BUTTON'), false);
		JToolbarHelper::custom('debug.syncShadowTables', 'loop', 'loop', JText::_('COM_NENO_DASHBOARD_SYNC_SHADOW_TABLES_BUTTON'), false);
		JToolbarHelper::custom('debug.optimizeTranslationsTable', 'filter', 'filter', JText::_('COM_NENO_DASHBOARD_OPTIMIZE_TRANSLATIONS_TABLE_BUTTON'), false);
		JToolbarHelper::custom('debug.listIssues', 'cube', 'cube', JText::_('COM_NENO_ISSUES_TITLE'), false);
		JToolbarHelper::custom('debug.downloadReport', 'download', 'download', JText::_('COM_NENO_ISSUES_DOWNLOAD_REPORT'), false);
		JToolbarHelper::title(JText::_('COM_NENO_DASHBOARD_TITLE'), 'screen');

		parent::display($tpl);
	}
}
