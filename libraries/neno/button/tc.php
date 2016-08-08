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
class JToolbarButtonTC extends JToolbarButton
{
	/**
	 * Get the button
	 *
	 * Defined in the final button class
	 *
	 * @return  string
	 *
	 * @since   3.0
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
	 */
	public function render(&$definition)
	{
		$data         = new stdClass;
		$data->button = JText::sprintf('COM_NENO_TRANSLATION_CREDIT_TOOLBAR_FAKE_BUTTON', number_format($definition[1], 0, ',', '.'));
		$layout       = JLayoutHelper::render('toolbartcbutton', $data, JPATH_NENO_LAYOUTS);

		return $layout;
	}
}
