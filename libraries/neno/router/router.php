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
 * NenoRouter
 *
 * @since       2.2.0
 */
class NenoRouter
{
	/**
	 * Generates route for plugin view
	 *
	 * @param string $plugin
	 *
	 * @return string
	 *
	 *
	 * @since 2.2.0
	 */
	public static function routePluginView($plugin, $view)
	{
		return JRoute::_("index.php?option=com_neno&view=plgrender&plugin=$plugin&plgrender=$view");
	}
}