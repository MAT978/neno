<?php

/**
 * @package    Neno
 * @subpackage Plugin
 *
 * @copyright  Copyright (c) 2016 Jensen Technologies S.L. All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

/**
 * Class NenoPlugin
 *
 * @since 2.2.0
 */
abstract class NenoPlugin extends JPlugin
{
	/**
	 * Translation method plugin
	 *
	 * @since 2.2.0
	 */
	const TRANSLATION_METHOD_PLUGIN = 1;
	/************** ABSTRACT METHODS ********************/

	/**
	 * Get plugin type
	 *
	 * @return string
	 *
	 * @see   Constants
	 * @since 2.2.0
	 */
	abstract public function getType();

	/**
	 * @return JDatabaseQuery
	 *
	 * @since 2.2.0
	 */
	abstract protected function getListQueryForInterface();

	/**
	 * @return string
	 *
	 * @since 2.2.0
	 */
	abstract protected function getLayoutForInterface();

	/**
	 * Get Neno plugins by type
	 *
	 * @param int $pluginType Plugin type
	 *
	 * @see   Constants
	 *
	 * @return array
	 *
	 * @since 2.2.0
	 */
	public static function getPluginsByType($pluginType)
	{
		$nenoPlugins = JPluginHelper::getPlugin('neno');
		$plugins     = array();

		foreach ($nenoPlugins as $nenoPlugin)
		{
			$className = 'plgNeno' . ucfirst($nenoPlugin);

			if (class_exists($className))
			{
				/* @var $plugin NenoPlugin */
				$plugin = new $className;

				if (static::isPluginType($plugin->getType(), $pluginType))
				{
					$plugins[] = $plugin;
				}
			}
		}

		return $plugins;
	}

	/**
	 * Add entry/entries to the left hand side menu
	 *
	 * @since 2.2.0
	 */
	public function onSidebarMenu()
	{
	}

	/**
	 * Render plugin view
	 *
	 * @param string $view View name
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	public function onRenderView($view)
	{
	}


	/**
	 * Check if the plugin is of a certain type
	 *
	 * @param int $pluginType    Plugin type
	 * @param int $pluginToCheck Plugin to check
	 *
	 * @return bool
	 *
	 * @since 2.2.0
	 */
	protected static function isPluginType($pluginType, $pluginToCheck)
	{
		return (!$pluginType ^ $pluginToCheck) === $pluginType;
	}
}