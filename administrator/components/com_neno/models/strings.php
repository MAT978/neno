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
	 * Build base query for database content querying
	 *
	 * @param string $workingLanguage Working Language
	 *
	 * @return JDatabaseQuery
	 */
	protected function getBaseDatabaseQueryStringQuery($workingLanguage)
	{
		$db        = JFactory::getDbo();
		$dbStrings = parent::getListQuery();

		// Create base query
		$dbStrings
			->select(
				array(
					'tr1.*',
					'f.field_name AS `key`',
					't.table_name AS element_name',
					'g1.group_name AS `group`',
					'CHAR_LENGTH(tr1.string) AS characters',
					'g1.id AS group_id'
				)
			)
			->from('`#__neno_content_element_translations` AS tr1')
			->innerJoin('`#__neno_content_element_fields` AS f ON tr1.content_id = f.id')
			->innerJoin('`#__neno_content_element_tables` AS t ON t.id = f.table_id')
			->innerJoin('`#__neno_content_element_groups` AS g1 ON t.group_id = g1.id ')
			->where(
				array(
					'tr1.language = ' . $db->quote($workingLanguage),
					'tr1.content_type = ' . $db->quote('db_string'),
					'f.translate = 1'
				)
			)
			->group(
				array(
					'HEX(tr1.string)',
					'tr1.state'
				)
			)
			->order('tr1.id');

		return $dbStrings;
	}

	/**
	 * Get Database Query for Database content
	 *
	 * @param   string $workingLanguage Working language
	 *
	 * @return JDatabaseQuery
	 */
	protected function buildDatabaseStringQuery($workingLanguage)
	{
		$db        = JFactory::getDbo();
		$dbStrings = $this->getBaseDatabaseQueryStringQuery($workingLanguage);

		$queryWhereDb = array();

		/* @var $groups array */
		/* @var $element array */
		/* @var $field array */
		/* @var $file array */
		/* @var $method array */
		list($groups, $element, $field, $file, $method,) = $this->getFilterByElements();

		if (!empty($groups) && !in_array('none', $groups))
		{
			$queryWhereDb[] = 't.group_id IN (' . implode(', ', $db->quote($groups)) . ')';
		}

		if (!empty($element))
		{
			$queryWhereDb[] = 't.id IN (' . implode(', ', $db->quote($element)) . ')';
		}

		if (!empty($field))
		{
			$queryWhereDb[] = 'f.id IN (' . implode(', ', $db->quote($field)) . ')';
		}

		if (!empty($file) && empty($field) && empty($element))
		{
			$queryWhereDb[] = 'f.id = 0 AND t.id = 0';
		}

		if (count($queryWhereDb))
		{
			$dbStrings->where('(' . implode(' OR ', $queryWhereDb) . ')');
		}

		if (!empty($method) && !in_array('none', $method))
		{
			$dbStrings
				->where('tr_x_tm1.translation_method_id IN (' . implode(', ', $db->quote($method)) . ')')
				->leftJoin('`#__neno_content_element_translation_x_translation_methods` AS tr_x_tm1 ON tr1.id = tr_x_tm1.translation_id');
		}

		return $dbStrings;
	}

	/**
	 * Build base query for language file content querying
	 *
	 * @param string $workingLanguage Working Language
	 *
	 * @return JDatabaseQuery
	 */
	protected function getBaseLanguageFileQuery($workingLanguage)
	{
		$db                  = JFactory::getDbo();
		$languageFileStrings = parent::getListQuery();

		// Create base query
		$languageFileStrings
			->select(
				array(
					'tr2.*',
					'ls.constant AS `key`',
					'lf.filename AS element_name',
					'g2.group_name AS `group`',
					'CHAR_LENGTH(tr2.string) AS characters',
					'g2.id AS group_id'
				)
			)
			->from('`#__neno_content_element_translations` AS tr2')
			->innerJoin('`#__neno_content_element_language_strings` AS ls ON tr2.content_id = ls.id')
			->innerJoin('`#__neno_content_element_language_files` AS lf ON lf.id = ls.languagefile_id')
			->innerJoin('`#__neno_content_element_groups` AS g2 ON lf.group_id = g2.id ')
			->where(
				array(
					'tr2.language = ' . $db->quote($workingLanguage),
					'tr2.content_type = ' . $db->quote('lang_string')
				)
			)
			->group(
				array(
					'HEX(tr2.string)',
					'tr2.state'
				)
			)
			->order('tr2.id');

		return $languageFileStrings;
	}

	/**
	 * Get Database Query for Language file content
	 *
	 * @param   string $workingLanguage Working language
	 *
	 * @return JDatabaseQuery
	 */
	protected function buildLanguageFileQuery($workingLanguage)
	{
		$db                  = JFactory::getDbo();
		$languageFileStrings = $this->getBaseLanguageFileQuery($workingLanguage);

		/* @var $groups array */
		/* @var $element array */
		/* @var $field array */
		/* @var $file array */
		/* @var $method array */
		/* @var $status array */
		list($groups, $element, $field, $file, $method,) = $this->getFilterByElements();

		if (!empty($groups) && !in_array('none', $groups))
		{
			$languageFileStrings->where('lf.group_id IN (' . implode(', ', $db->quote($groups)) . ')');
		}

		if (!empty($file))
		{
			$languageFileStrings->where('lf.id IN (' . implode(',', $db->quote($file)) . ')');
		}
		elseif (!empty($field) || (empty($file) && !empty($element)))
		{
			$languageFileStrings->where('lf.id = 0');
		}

		if (!empty($method) && !in_array('none', $method))
		{
			$languageFileStrings
				->where('tr_x_tm2.translation_method_id IN ("' . implode('", "', $method) . '")')
				->leftJoin('`#__neno_content_element_translation_x_translation_methods` AS tr_x_tm2 ON tr2.id = tr_x_tm2.translation_id');
		}

		return $languageFileStrings;
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

		// Create a new query object.
		$dbStrings           = $this->buildDatabaseStringQuery($workingLanguage);
		$languageFileStrings = $this->buildLanguageFileQuery($workingLanguage);
		$query               = parent::getListQuery();

		$query
			->select('DISTINCT a.*')
			->from('((' . (string) $dbStrings . ') UNION (' . (string) $languageFileStrings . ')) AS a')
			->group('id')
			->order('a.id ASC');

		$search = $this->getState('filter.search');

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

		list(, , , , , $status) = $this->getFilterByElements();

		if (!empty($status) && $status[0] !== '' && !in_array('none', $status))
		{
			$query->where('a.state IN (' . implode(', ', $status) . ')');
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
		$groups  = $this->getState('filter.group_id', array());
		$element = $this->getState('filter.element', array());
		$field   = $this->getState('filter.field', array());
		$file    = $this->getState('filter.files', array());
		$method  = (array) $this->getState('filter.translator_type', array());
		$status  = (array) $this->getState('filter.translation_status', array());

		if (!is_array($groups))
		{
			$groups = array( $groups );
		}

		return array( $groups, $element, $field, $file, $method, $status );
	}
}
