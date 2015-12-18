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
 * Class NenoTaskWorkMonitor
 *
 * @since  1.0
 */
class NenoTaskWorkMonitor extends NenoTaskWorker
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
		$tasksNeedToBeCleanUp = NenoTask::load(
			array(
				'_field'     => 'numberOfAttemps',
				'_condition' => '>',
				'_value'     => 3
			)
		);

		if (!empty($tasksNeedToBeCleanUp))
		{
			/* @var $taskNeedToBeCleanUp NenoTask */
			foreach ($tasksNeedToBeCleanUp as $taskNeedToBeCleanUp)
			{
				NenoLog::add($taskNeedToBeCleanUp->getTask() . ' task has been deleted because reaches the maximum number of attempts allowed');
				$taskNeedToBeCleanUp->remove();
			}
		}

		return false;
	}
}
