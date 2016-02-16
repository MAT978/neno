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
class NenoModelSettings extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
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
				'setting_key',
				'a.setting_key',
				'setting_value',
				'a.setting_value'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Get items
	 *
	 * @return array
	 */
	public function getItems()
	{
		$this->setState('list.limit', 0);
		$items = parent::getItems();
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		foreach ($items as $item)
		{
			switch ($item->setting_key)
			{
				case 'translation_method_1':
				case 'translation_method_2':
					$query
						->clear()
						->select(
							array(
								'id',
								'name_constant'
							)
						)
						->from('#__neno_translation_methods');

					$db->setQuery($query);
					$values = $db->loadObjectList();

					$item->dropdown = JHtml::_('select.genericlist', $values, $item->setting_key, null, 'id', 'name_constant', $item->setting_value, false, true);

					break;
				case 'schedule_task_option':
					$values         = array(
						array( 'value' => 'ajax', 'text' => 'COM_NENO_INSTALLATION_TASK_OPTION_AJAX_MODULE_TITLE' ),
						array( 'value' => 'cron', 'text' => 'COM_NENO_INSTALLATION_TASK_OPTION_CRON_TITLE' ),
						array( 'value' => 'disabled', 'text' => 'COM_NENO_INSTALLATION_TASK_OPTION_DISABLE_TITLE' ),
					);
					$item->dropdown = JHtml::_('select.genericlist', $values, $item->setting_key, null, 'value', 'text', $item->setting_value, false, true);
					break;
				case 'translator':
					$query
						->clear()
						->select(
							array(
								'translator_name AS value',
								'translator_name AS text',
							)
						)
						->from('#__neno_machine_translation_apis');
					$db->setQuery($query);
					$values = $db->loadObjectList();

					$item->dropdown = JHtml::_('select.genericlist', $values, $item->setting_key, null, 'value', 'text', $item->setting_value, false, true);
					break;
				case 'default_translate_action':
					if ($item->setting_key == '')
					{
						$item->setting_key = 0;
					}

					$values         = array(
						array(
							'value' => '0',
							'text'  => 'COM_NENO_SETTINGS_SETTING_OPTION_DEFAULT_TRANSLATE_ACTION_NO'
						),
						array(
							'value' => '1',
							'text'  => 'COM_NENO_SETTINGS_SETTING_OPTION_DEFAULT_TRANSLATE_ACTION_COPY'
						),
						array(
							'value' => '2',
							'text'  => 'COM_NENO_SETTINGS_SETTING_OPTION_DEFAULT_TRANSLATE_ACTION_TRANSLATE'
						),
					);
					$item->dropdown = JHtml::_('select.genericlist', $values, $item->setting_key, null, 'value', 'text', $item->setting_value, false, true);
					break;
			}
		}

		// Ensure defaults are set
		$items = $this->ensureDefaultsExists($items);

		return $items;
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
		// Create a new query object.
		$db    = JFactory::getDbo();
		$query = parent::getListQuery();

		$query
			->select('a.*')
			->from('#__neno_settings AS a')
			->where(
				array(
					'a.setting_key NOT LIKE ' . $db->quote('%installation%'),
					'a.setting_key NOT LIKE ' . $db->quote('%setup%'),
					'a.setting_key NOT LIKE ' . $db->quote('%discovering%')
				)
			);

		// Add the list ordering clause.
		$orderCol       = $this->state->get('list.ordering');
		$orderDirection = $this->state->get('list.direction');

		if ($orderCol && $orderDirection)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirection));
		}

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.setting_key', 'asc');
	}

	/**
	 * This method takes care that all the default settings exist.
	 *
	 * @param   array  $items  Setting items
	 *
	 * @return array
	 */
	protected function ensureDefaultsExists($items)
	{
		// Create a simple array with key value of settings
		$settings = array();

		foreach ($items as $item)
		{
			$settings[ $item->setting_key ] = $item->setting_value;
		}

		// Related content
		if (!isset($settings['load_related_content']))
		{
			NenoSettings::set('load_related_content', '0');

			// Refresh the items
			$items = $this->getItems();
		}

		return $items;
	}
}
