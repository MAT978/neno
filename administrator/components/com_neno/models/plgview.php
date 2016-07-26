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
class NenoModelPlgView extends JModelList
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
		$plugin  = $input->getCmd('plugin');
		$plgView = $input->getCmd('plgview');

		JPluginHelper::importPlugin('neno', $plugin);

		$className = 'plgNeno' . ucfirst($plugin);

		if (class_exists($className))
		{
			/* @var $pluginInstance NenoPlugin */
			$pluginInstance = new $className;

			return $pluginInstance->onRenderView($plgView);
		}

		throw new RuntimeException(JText::_('Plugin class not found: %s', $className));
	}
}
