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

jimport('joomla.application.component.modellist');

/**
 * NenoModelGroupsElements class
 *
 * @since  1.0
 */
class NenoModelStrings extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
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
	 * Get elements
	 *
	 * @return array
	 */
	public function getItems()
	{
		$elements     = parent::getItems();
		$translations = array();
		$groups       = array();

		foreach ($elements as $element)
		{
			if (!empty($element->group_id))
			{
				$groups[] = $element->group_id;
			}

			$translation    = new NenoContentElementTranslation($element, false);
			$translations[] = $translation->prepareDataForView(true);
		}

		if (!empty($groups))
		{
			$this->setState('filter.parent_group_id', $groups);
		}

		return $translations;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getstart');

		// Try to load the data from internal storage.
		if (isset($this->cache[ $store ]))
		{
			return $this->cache[ $store ];
		}

		$start = $this->getState('list.start');

		// Add the total to the internal cache.
		$this->cache[ $store ] = $start;

		return $this->cache[ $store ];
	}

	/**
	 * Populate content element filters
	 *
	 * @throws Exception
	 */
	protected function populateContentElementFilters()
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		$groups = $app->getUserStateFromRequest($this->context . '.group', 'group', array());

		if (!empty($groups))
		{
			$this->setState('filter.group_id', $groups);
			$app->setUserState($this->context . '.filter.elements', array());
		}

		// Element(s) filtering
		$elements = $app->getUserStateFromRequest($this->context . '.filter.elements', 'table', array());

		if (!empty($elements))
		{
			$app->setUserState($this->context . '.filter.elements', $elements);
		}

		$this->setState('filter.element', $app->getUserState($this->context . '.filter.elements'));

		// Language file filtering
		$files = $app->getUserStateFromRequest($this->context . '.filter.files', 'file', array());

		if (!empty($files))
		{
			$app->setUserState($this->context . '.filter.files', $files);
		}

		$this->setState('filter.files', $app->getUserState($this->context . '.filter.files'));

		// Field(s) filtering
		$fields = $app->getUserStateFromRequest($this->context . '.field', 'field', array());

		if (!empty($fields))
		{
			$this->setState('filter.field', $fields);
		}
	}

	/**
	 * Populate other filters
	 *
	 * @throws Exception
	 */
	protected function populateOtherFilters()
	{
		// Initialise variables.
		$app    = JFactory::getApplication();
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'raw');

		if (!empty($search))
		{
			$this->setState('filter.search', $search);
		}

		// Status filtering
		$status = (array) $app->getUserStateFromRequest($this->context . '.status', 'status', array());

		if (!empty($status))
		{
			$index = array_search(0, $status);

			if ($index !== false)
			{
				unset($status[ $index ]);
			}

			$this->setState('filter.translation_status', $status);
		}

		// Translation methods filtering
		$method = (array) $app->getUserStateFromRequest($this->context . '.type', 'type', array());

		if (!empty($method))
		{
			$index = array_search(0, $method);

			if ($index !== false)
			{
				unset($method[ $index ]);
			}

			$app->setUserState($this->context . '.filter.translator_type', $method);
		}

		$this->setState('filter.translator_type', $app->getUserState($this->context . '.filter.translator_type'));
	}

	/**
	 * Get and set current values of filters
	 *
	 * @param   string $ordering  Ordering field
	 * @param   string $direction Direction field
	 *
	 * @return void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$this->populateContentElementFilters();

		$this->populateOtherFilters();

		// List state information.
		parent::populateState('a.id', 'asc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$db              = JFactory::getDbo();
		$workingLanguage = NenoHelper::getWorkingLanguage();
		list($groups, $tables, $fields, $files, $translationMethods, $translationStatuses, $search) = $this->getFilterByElements();

		// Create a new query object.
		$query = NenoContentElementTranslation::buildTranslationQuery($workingLanguage, $groups, $tables, $fields, $files, $translationMethods);

		if (!empty($search))
		{
			$search = $db->quote('%' . $search . '%');
			$query->where('(a.original_text LIKE ' . $search . ' OR a.string LIKE ' . $search . ')');
		}

		// Hide empty strings if the user wants to do that
		if (NenoSettings::get('hide_empty_strings', true))
		{
			$query->where('a.original_text <> ' . $db->quote(''));
		}

		if (!empty($translationStatuses) && $translationStatuses[0] !== '' && !in_array('none', $translationStatuses))
		{
			$query->where('a.state IN (' . implode(', ', $translationStatuses) . ')');
		}

		return $query;
	}

	/**
	 * Get elements to filter by
	 *
	 * @return array
	 */
	protected function getFilterByElements()
	{
		$groups              = $this->getState('filter.group_id', array());
		$elements            = $this->getState('filter.element', array());
		$fields              = $this->getState('filter.field', array());
		$files               = $this->getState('filter.files', array());
		$translationMethods  = (array) $this->getState('filter.translator_type', array());
		$translationStatuses = (array) $this->getState('filter.translation_status', array());
		$search              = $this->getState('filter.search');

		if (!is_array($groups))
		{
			$groups = array( $groups );
		}

		return array( $groups, $elements, $fields, $files, $translationMethods, $translationStatuses, $search );
	}
}
