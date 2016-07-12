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
 * Class NenoTask
 *
 * @since  1.0
 */
class NenoTask extends NenoObject
{
	/**
	 * @var string
	 */
	protected $task;
	/**
	 * @var Datetime
	 */
	protected $timeAdded;
	/**
	 * @var Datetime
	 */
	protected $timeStarted;
	/**
	 * @var integer
	 */
	protected $numberOfAttempts;
	/**
	 * @var array
	 */
	protected $taskData;

	/**
	 * Constructor
	 *
	 * @param   mixed $data          Task data
	 * @param   bool  $loadExtraData Load Extra data flag
	 */
	public function __construct($data, $loadExtraData = true)
	{
		parent::__construct($data, $loadExtraData);

		if (is_string($this->taskData))
		{
			$this->taskData = (array) json_decode($this->taskData, true);
		}
	}

	/**
	 * To object method
	 *
	 * @param   bool $allFields         Convert all the fields
	 * @param   bool $recursive         If the method should be run recursive
	 * @param   bool $convertToDatabase Convert to database naming
	 *
	 * @return stdClass
	 */
	public function toObject($allFields = false, $recursive = false, $convertToDatabase = true)
	{
		$data            = parent::toObject($allFields, $recursive, $convertToDatabase);
		$data->task_data = json_encode($data->task_data);

		return $data;
	}

	/**
	 * Generate an id for a new record
	 *
	 * @return mixed
	 */
	public function generateId()
	{
		return NULL;
	}

	/**
	 * Get task
	 *
	 * @return string
	 */
	public function getTask()
	{
		return $this->task;
	}

	/**
	 * Get the time when this task was added
	 *
	 * @return Datetime
	 */
	public function getTimeAdded()
	{
		return $this->timeAdded;
	}

	/**
	 * Get the time when this task started
	 *
	 * @return Datetime
	 */
	public function getTimeStarted()
	{
		return $this->timeStarted;
	}

	/**
	 * Get how many attemps has happened.
	 *
	 * @return int
	 */
	public function getNumberOfAttempts()
	{
		return $this->numberOfAttempts;
	}

	/**
	 * Get task data.
	 *
	 * @return array
	 */
	public function getTaskData()
	{
		return $this->taskData;
	}

	/**
	 * Execute task
	 *
	 * @return void
	 */
	public function execute()
	{
		// Set the time when the task started
		$this->timeStarted = new DateTime;

		// Increase the number of attempts
		$this->numberOfAttempts++;

		// Save this task on the database
		$this->persist();

		// Get and execute the task through a worker.
		$worker = $this->getWorker();

		NenoLog::log('Executing task: ' . $this->getTask(), '', 0, NenoLog::PRIORITY_VERBOSE);

		$worker->run($this->taskData);
	}

	/**
	 * Get Worker related to a task
	 *
	 * @return NenoTaskWorker
	 */
	protected function getWorker()
	{
		// Generate Worker class name
		$className = 'NenoTaskWorker' . ucfirst(NenoHelper::convertDatabaseColumnNameToPropertyName($this->task));

		// Check if the class exists, if it doesn't, let's try to load it.
		if (class_exists($className))
		{
			$worker = new $className;

			return $worker;
		}
		else
		{
			NenoLog::log('Worker not found for this task', '', 0, NenoLog::PRIORITY_ERROR);

			throw new UnexpectedValueException('Worker not found for this task');
		}
	}
}
