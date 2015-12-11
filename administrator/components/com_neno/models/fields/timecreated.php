<?php
/**
 * @package     Neno
 * @subpackage  Fields
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0
 */
class JFormFieldTimeCreated extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'timecreated';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.6
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();

		$timeCreated = $this->value;

		if (!strtotime($timeCreated))
		{
			$timeCreated = date("Y-m-d H:i:s");
			$html[]       = '<input type="hidden" name="' . $this->name . '" value="' . $timeCreated . '" />';
		}

		$hidden = (boolean) $this->element['hidden'];

		if ($hidden == null || !$hidden)
		{
			$jdate       = new JDate($timeCreated);
			$prettyDate = $jdate->format(JText::_('DATE_FORMAT_LC2'));
			$html[]      = "<div>" . $prettyDate . "</div>";
		}

		return implode($html);
	}
}
