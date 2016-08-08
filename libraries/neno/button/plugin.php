<?php
/**
 * @package     Neno
 * @subpackage  Button
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Class NenoButtonTC
 *
 * @since  1.0
 */
class JToolbarButtonPlugin extends JToolbarButton
{
	/**
	 * Get the button
	 *
	 * Defined in the final button class
	 *
	 * @return  string
	 *
	 * @since   2.2.0
	 */
	public function fetchButton()
	{
		return '';
	}

	/**
	 * Render the button
	 *
	 * @param   array &$definition Definition
	 *
	 * @return string
	 *
	 * @since 2.2.0
	 */
	public function render(&$definition)
	{
		$data   = (object) $definition[1];
		$layout = JLayoutHelper::render('libraries.neno.toolbarpluginbutton', $data);

		return $layout;
	}
}
