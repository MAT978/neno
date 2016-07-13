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
 */
abstract class NenoPlugin extends JPlugin
{
	/**
	 * Translation method plugin
	 */
	const TRANSLATION_METHOD_PLUGIN = 1;
	/************** ABSTRACT METHODS ********************/

	/**
	 * Get plugin type
	 *
	 * @return string
	 *
	 * @see Constants
	 */
	abstract public function getType();

	/**
	 * @return JDatabaseQuery
	 */
	abstract protected function getListQueryForInterface();

	/**
	 * @return string
	 */
	abstract protected function getLayoutForInterface();

	/**
	 * Get Neno plugins by type
	 *
	 * @param int $pluginType Plugin type
	 *
	 * @see Constants
	 *
	 * @return array
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
	 * Render interface for this plugin
	 *
	 * @param int $limit limit value for the query
	 * @param int $offset offset value for the query
	 *
	 * @return string
	 */
	public function onInterfaceRender($limit, $offset)
	{
		$db = JFactory::getDbo();
		$db->setQuery($this->getListQueryForInterface(), $offset, $limit);
		$results = $db->loadObjectList();

		$layoutFile     = $this->getLayoutForInterface();
		$layoutFileName = basename($layoutFile);
		$layoutPath     = str_replace(DIRECTORY_SEPARATOR . $layoutFileName, '', $layoutFile);

		return JLayoutHelper::render(JFile::stripExt($layoutFileName), $results, $layoutPath);
	}

	/**
	 * Check if the plugin is of a certain type
	 *
	 * @param int $pluginType    Plugin type
	 * @param int $pluginToCheck Plugin to check
	 *
	 * @return bool
	 */
	protected static function isPluginType($pluginType, $pluginToCheck)
	{
		return (!$pluginType ^ $pluginToCheck) === $pluginType;
	}
}