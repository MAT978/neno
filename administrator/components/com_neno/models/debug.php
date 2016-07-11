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
			  'string',
			  'a.string',
			  'word_counter',
			  'a.word_counter',
			  'group',
			  'a.group',
			  'key',
			  'a.key',
			  'element_name',
			  'a.element_name',
			  'word_counter',
			  'a.word_counter',
			  'characters',
			  'a.characters'
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

		return $query;
	}

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
}
