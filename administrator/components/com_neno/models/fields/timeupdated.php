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
class JFormFieldTimeUpdated extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'timeupdated';

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

		$oldTimeUpdated = $this->value;
		$hidden           = (boolean) $this->element['hidden'];

		if ($hidden == null || !$hidden)
		{
			if (!strtotime($oldTimeUpdated))
			{
				$html[] = '-';
			}
			else
			{
				$jdate       = new JDate($oldTimeUpdated);
				$prettyDate = $jdate->format(JText::_('DATE_FORMAT_LC2'));
				$html[]      = "<div>" . $prettyDate . "</div>";
			}
		}

		$timeUpdated = date("Y-m-d H:i:s");
		$html[]       = '<input type="hidden" name="' . $this->name . '" value="' . $timeUpdated . '" />';

		return implode($html);
	}
}
