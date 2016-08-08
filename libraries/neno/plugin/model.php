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
abstract class NenoPluginModel
{
	/**
	 * Get data for the view
	 *
	 * @return mixed
	 *
	 * @since 2.2.0
	 */
	abstract public function getData();
}