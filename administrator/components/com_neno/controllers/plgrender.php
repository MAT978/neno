<?php

/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

/**
 * Plugin rendering controller
 *
 * @since  2.2.0
 */
class NenoControllerPlgRender extends JControllerAdmin
{
	public function __construct(array $config)
	{
		parent::__construct($config);

		$this->name = 'plgrender';
	}


	/**
	 * Execute plugin action
	 *
	 * @since 2.2.0
	 */
	public function plgaction()
	{
		$plgaction  = $this->input->getCmd('plgaction');
		$pluginName = $this->input->getCmd('plugin');
		$pluginView = $this->input->getCmd('plgrender');
		list($controller, $action) = explode('.', $plgaction);

		/* @var $model NenoModelPlgRender */
		$model  = $this->getModel();
		$plugin = $model->getPlugin();

		$plugin->executeControllerAction($controller, $action);

		$this
			->setRedirect(NenoRouter::routePluginView($pluginName, $pluginView))
			->redirect();
	}

}
