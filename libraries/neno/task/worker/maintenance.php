<?php
/**
 * @package     Neno
 * @subpackage  Task
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoTaskWorkerLanguage
 *
 * @since  1.0
 */
class NenoTaskWorkerMaintenance extends NenoTaskWorker
{
	/**
	 * Execute the task
	 *
	 * @param   array $taskData Task data
	 *
	 * @return bool True on success, false otherwise
	 */
	public function run($taskData)
	{
		if (!empty($taskData['tableId']))
		{
			/* @var $table NenoContentElementTable */
			$table = NenoContentElementTable::load($taskData['tableId']);

			if (!empty($table))
			{
				$table->applyFiltersToExistingContent();
			}
		}
	}
}
