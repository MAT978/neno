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
 * NenoModelJobs class
 *
 * @since  1.0
 */
class NenoModelJobs extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array ())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array (
				'id',
				'state',
				'to_language',
				'translation_method',
				'word_count',
				'translation_credit',
				'created_time',
				'completion_time'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Get Items
	 *
	 * @return array
	 */
	public function getItems()
	{
		$queryData = array (
			'_offset' => $this->getStart(),
			'_limit'  => $this->getState('list.limit'),
			'_order'  => array (
				(string) $this->getState('list.ordering') => $this->getState('list.direction')
			)
		);

		$jobs = NenoJob::load($queryData);

		if (!is_array($jobs))
		{
			$jobs = array ($jobs);
		}

		/* @var $job NenoJob */
		foreach ($jobs as $key => $job)
		{
			$data                     = $job->prepareDataForView(true);
			$data->translation_method = $job->getTranslationMethod();
			$jobs[$key]               = $data;
		}

		return $jobs;
	}

	/**
	 * Get the total amount of record
	 *
	 * @return int
	 */
	public function getTotal()
	{
		$result = NenoJob::load(array ('_select' => array ('COUNT(*) AS counter')));

		return (int) $result['counter'];
	}

	/**
	 * Get and set current values of filters
	 *
	 * @param   string  $ordering   Ordering clause
	 * @param   string  $direction  Direction clause
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		$this->setState('limit', $app->getUserState('limit', 20));
		$this->setState('limitStart', $app->getUserState('limitStart', 0));

		// List state information.
		parent::populateState('id', 'asc');
	}
}
