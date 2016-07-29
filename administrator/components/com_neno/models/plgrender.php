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
 * NenoModelPlgView class
 *
 * @since  2.2.0
 */
class NenoModelPlgRender extends JModelList
{
	/**
	 * Get view content
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	public function getView()
	{
		$input   = JFactory::getApplication()->input;
		$plgView = $input->getCmd('plgrender');

		/* @var $pluginInstance NenoPlugin */
		$pluginInstance = $this->getPlugin();

		return $pluginInstance->onRenderView($plgView);
	}

	/**
	 * Get toolbar buttons
	 *
	 * @return array
	 *
	 * @since 2.2.0
	 */
	public function getButtons()
	{
		$input   = JFactory::getApplication()->input;
		$plgView = $input->getCmd('plgrender');

		/* @var $pluginInstance NenoPlugin */
		$pluginInstance = $this->getPlugin();

		return $pluginInstance->onToolbarRendering($plgView);
	}

	/**
	 * Create NenoPlugin instance
	 *
	 * @return NenoPlugin
	 *
	 * @since 2.2.0
	 */
	public function getPlugin()
	{
		$input      = JFactory::getApplication()->input;
		$plugin     = $input->getCmd('plugin');
		$dispatcher = JEventDispatcher::getInstance();

		JPluginHelper::importPlugin('neno', $plugin);

		$className = 'plgNeno' . ucfirst($plugin);

		if (class_exists($className))
		{
			/* @var $pluginInstance NenoPlugin */
			$pluginInstance = new $className($dispatcher);

			return $pluginInstance;
		}

		throw new RuntimeException(JText::sprintf('Plugin class not found: %s', $className));
	}
}
