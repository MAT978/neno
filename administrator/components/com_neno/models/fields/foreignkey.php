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
 * Supports a value from an external table
 *
 * @since  1.0
 */
class JFormFieldForeignKey extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'foreignkey';

	/**
	 * @var string
	 */
	private $inputType;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var string
	 */
	private $keyField;

	/**
	 * @var string
	 */
	private $valueField;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.6
	 */
	protected function getInput()
	{
		// Assign field properties.
		// Type of input the field shows
		$this->inputType = $this->getAttribute('input_type');

		// Database Table
		$this->table = $this->getAttribute('table');

		// The field that the field will save on the database
		$this->keyField = $this->getAttribute('key_field');

		// The column that the field shows in the input
		$this->valueField = $this->getAttribute('value_field');

		// Load all the field options
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					$this->keyField,
					$this->valueField
				)
			)
			->from($this->table);

		$db->setQuery($query);
		$results = $db->loadObjectList();

		$input_options = 'class="' . $this->getAttribute('class') . '"';

		// Depends of the type of input, the field will show a type or another
		switch ($this->inputType)
		{
			case 'list':
			default:
				$options = array ();

				// Iterate through all the results
				foreach ($results as $result)
				{
					$options[] = JHtml::_('select.option', $result->{$this->keyField}, $result->{$this->valueField});
				}

				$value = $this->value;

				// If the value is a string -> Only one result
				if (is_string($value))
				{
					$value = array ($value);
				}
				else
				{
					if (is_object($value))
					{
						// If the value is an object, let's get its properties.
						$value = get_object_vars($value);
					}
				}

				// If the select is multiple
				if ($this->multiple)
				{
					$input_options .= 'multiple="multiple"';
				}
				else
				{
					array_unshift($options, JHtml::_('select.option', '', ''));
				}

				$html = JHtml::_('select.genericlist', $options, $this->name, $input_options, 'value', 'text', $value, $this->id);
				break;
		}

		return $html;
	}

	/**
	 * Wrapper method for getting attributes from the form element
	 *
	 * @param   string $attrName Attribute name
	 * @param   mixed  $default  Optional value to return if attribute not found
	 *
	 * @return mixed The value of the attribute if it exists, null otherwise
	 */
	public function getAttribute($attrName, $default = null)
	{
		if (!empty($this->element[$attrName]))
		{
			return $this->element[$attrName];
		}
		else
		{
			return $default;
		}
	}
}
