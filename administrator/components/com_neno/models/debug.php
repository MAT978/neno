<?php
/**
 * @package     Neno
 * @subpackage  Models
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * NenoModelGroupsElements class
 *
 * @since  1.0
 */
class NenoModelDebug extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
			  'id',
			  'a.id',
			  'level',
			  'a.level',
			  '`trigger`',
			  'a.`trigger`',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('a.*')
		  ->from('#__neno_log_entries AS a');

		// Filter by level.
		if ($level = $this->getState('filter.level'))
		{
			$query->where('a.level = ' . (int) $level);
		}

		// Filter by level.
		if ($trigger = $this->getState('filter.trigger'))
		{
			$query->where('a.`trigger` = ' . (int) $trigger);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('a.message LIKE ' . $search);
			}
		}


		return $query;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return mixed
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $item)
		{
			switch ($item->level)
			{
				case NenoLog::PRIORITY_INFO:
					$item->level = 'info';
					break;
				case NenoLog::PRIORITY_ERROR:
					$item->level = 'important';
					break;
				case NenoLog::PRIORITY_WARNING:
					$item->level = 'warning';
					break;
				case NenoLog::PRIORITY_VERBOSE:
					$item->level = 'verbose';
					break;
			}
		}

		return $items;
	}

	protected function populateState($ordering = NULL, $direction = NULL)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', NULL, 'int');
		$this->setState('filter.level', $level);

		$trigger = $this->getUserStateFromRequest($this->context . '.filter.trigger', 'filter_trigger', '', 'int');
		$this->setState('filter.trigger', $trigger);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_neno');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.time_added', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string $id A prefix for the store id.
	 *
	 * @return  string  A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.level');
		$id .= ':' . $this->getState('filter.trigger');

		return parent::getStoreId($id);
	}
}
