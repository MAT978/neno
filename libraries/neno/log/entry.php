<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Neno Entry class
 *
 * @since  1.0
 */
class NenoLogEntry extends NenoObject
{
	/**
	 * @var DateTime
	 */
	protected $timeAdded;
	/**
	 * @var string
	 */
	protected $action;
	/**
	 * @var string
	 */
	protected $message;
	/**
	 * @var int
	 */
	protected $level;
	/**
	 * @var int
	 */
	protected $trigger;

	/**
	 * @return \DateTime
	 */
	public function getTimeAdded()
	{
		return $this->timeAdded;
	}

	/**
	 * @param \DateTime $timeAdded
	 *
	 * @return NenoLogEntry
	 */
	public function setTimeAdded($timeAdded)
	{
		$this->timeAdded = $timeAdded;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param string $action
	 *
	 * @return NenoLogEntry
	 */
	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @param string $message
	 *
	 * @return NenoLogEntry
	 */
	public function setMessage($message)
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @param int $level
	 *
	 * @return NenoLogEntry
	 */
	public function setLevel($level)
	{
		$this->level = $level;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTrigger()
	{
		return $this->trigger;
	}

	/**
	 * @param int $trigger
	 *
	 * @return NenoLogEntry
	 */
	public function setTrigger($trigger)
	{
		$this->trigger = $trigger;

		return $this;
	}
}
