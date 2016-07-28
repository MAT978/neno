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
	const TRANSLATION_METHOD_PLUGIN = 'TRANSLATION_METHOD_PLUGIN';
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
		$plugins = static::getPluginsGroupedByType();

		return isset($plugins[$pluginType]) ? $plugins[$pluginType] : array();
	}

	/**
	 * Returns plugins grouped by type
	 *
	 * @return array
	 *
	 * @since 2.2.0
	 */
	public static function getPluginsGroupedByType()
	{
		JPluginHelper::importPlugin('neno');
		$nenoPlugins = JPluginHelper::getPlugin('neno');
		$plugins     = array();
		$dispatcher  = JEventDispatcher::getInstance();

		foreach ($nenoPlugins as $nenoPlugin)
		{
			$className = 'plgNeno' . ucfirst($nenoPlugin->name);

			if (class_exists($className))
			{
				/* @var $plugin NenoPlugin */
				$plugin = new $className($dispatcher, (array) $nenoPlugin);

				if (!isset($plugins[$plugin->getType()]))
				{
					$plugins[$plugin->getType()] = array();
				}

				$plugins[$plugin->getType()][] = $plugin;
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
		if (file_exists($this->getViewsDirectory() . DIRECTORY_SEPARATOR . strtolower($view) . '.php'))
		{
			$model = $this->getModel($view);

			return JLayoutHelper::render($view, $model->getData(), $this->getViewsDirectory());
		}

		throw new RuntimeException(JText::sprintf('View not found: %s', $view));
	}

	/**
	 * Returns plugin model
	 *
	 * @param string $model Model name
	 *
	 * @return NenoPluginModel
	 *
	 * @since version
	 */
	protected function getModel($model)
	{
		$modelClassName = 'NenoPluginModel' . ucfirst($model);
		$modelFilePath  = $this->getModelsDirectory() . DIRECTORY_SEPARATOR . strtolower($model) . '.php';

		if (file_exists($modelFilePath))
		{
			JLoader::register($modelClassName, $modelFilePath);

			$model = new $modelClassName;

			return $model;
		}

		throw new RuntimeException(JText::sprintf('Model class not found: %s', $modelClassName));
	}

	/**
	 * Returns layouts directory
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	protected function getViewsDirectory()
	{
		return $this->getCurrentFilePath() . DIRECTORY_SEPARATOR . 'views';
	}

	/**
	 * Returns controllers directory
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	protected function getControllersDirectory()
	{
		return $this->getCurrentFilePath() . DIRECTORY_SEPARATOR . 'controllers';
	}

	/**
	 * Returns models directory
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	protected function getModelsDirectory()
	{
		return $this->getCurrentFilePath() . DIRECTORY_SEPARATOR . 'models';
	}

	/**
	 * Get current file path
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	protected final function getCurrentFilePath()
	{
		return dirname((new ReflectionClass(get_called_class()))->getFileName());
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

	/**
	 * Execute action
	 *
	 * @param string $controller
	 * @param string $action
	 *
	 *
	 * @since version
	 */
	public function executeControllerAction($controller, $action)
	{
		$controllerClass         = 'NenoPluginController' . ucfirst($controller);
		$controllerClassFilePath = $this->getControllersDirectory() . DIRECTORY_SEPARATOR . strtolower($controller) . '.php';

		if (file_exists($controllerClassFilePath))
		{
			JLoader::register($controllerClass, $controllerClassFilePath);

			/* @var $controllerInstance NenoPluginController */
			$controllerInstance = new $controllerClass;

			$controllerInstance->doTask($action);
		}

		throw new RuntimeException('Controller class not found: %s', $controllerClass);
	}

	/**
	 * Returns an array of buttons to be rendered
	 *
	 * @param string $view
	 *
	 * @return array
	 *
	 * @since version
	 */
	public function onToolbarRendering($view)
	{
	}
}