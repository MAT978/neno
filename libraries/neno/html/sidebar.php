<?php

/**
 * @package    Neno
 * @subpackage Html.Sidebar
 *
 * @copyright  Copyright (c) 2016 Jensen Technologies S.L. All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 *
 * @since       2.2.0
 */
class NenoHtmlSidebar extends JHtmlSidebar
{
	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	public static function render()
	{
		// Collect display data
		$data                 = new stdClass;
		$data->list           = static::getEntries();
		$data->filters        = static::getFilters();
		$data->action         = static::getAction();
		$data->displayMenu    = count($data->list);
		$data->displayFilters = count($data->filters);
		$data->hide           = JFactory::getApplication()->input->getBool('hidemainmenu');

		// Create a layout object and ask it to render the sidebar
		$layout      = new JLayoutFile('libraries.neno.sidebar');
		$sidebarHtml = $layout->render($data);

		return $sidebarHtml;
	}


}