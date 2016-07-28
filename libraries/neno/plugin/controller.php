<?php

/**
 * @package    Neno
 * @subpackage Plugin
 *
 * @copyright  Copyright (c) 2016 Jensen Technologies S.L. All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 *
 * @since       2.2.0
 */
abstract class NenoPluginController
{
	/**
	 * @var JInput
	 * @since 2.2.0
	 */
	protected $input;

	/**
	 * NenoPluginController constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct()
	{
		$app         = JFactory::getApplication();
		$this->input = $app->input;
	}

	/**
	 * Execute task
	 *
	 * @param string $task
	 *
	 * @return void
	 *
	 * @since 2.2.0
	 */
	public function doTask($task)
	{
		$methodName = 'do' . ucfirst($task);

		if (method_exists($this, $methodName))
		{
			$this->$methodName();
		}

		throw new RuntimeException(JText::sprintf('Task not found %s', $task));
	}
}