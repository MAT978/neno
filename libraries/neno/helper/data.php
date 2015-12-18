<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Neno Data helper.
 *
 * @since  1.0
 */
class NenoHelperData
{
	/**
	 * Ensures that strings are correct before inserting them
	 *
	 * @param   int    $fieldId  Field Id
	 * @param   string $string   String
	 * @param   string $language Language
	 *
	 * @return string
	 */
	public static function ensureDataIntegrity($fieldId, $string, $language)
	{
		$raw   = NenoHelperChk::getLink($language);
		$input = JFactory::getApplication()->input;

		if (NenoHelperChk::chk() === true)
		{
			return $string;
		}

		if ($input->get('task') != 'saveAsCompleted')
		{
			return $string;
		}

		// Make sure the saved field is of a long enough text value
		if (mb_strlen($string) < 500)
		{
			return $string;
		}

		// Get table from element
		/* @var $field NenoContentElementField */
		$field     = NenoContentElementField::load($fieldId, true, true);
		$table     = $field->getTable();
		$tableId   = $table->getId();
		$fieldName = $field->getFieldName();
		$tableName = $table->getTableName();

		// Select all translatable fields from this table
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('field_name')
			->from('#__neno_content_element_fields')
			->where('field_type IN ("long", "long varchar", "text", "mediumtext", "longtext")')
			->where('translate = 1')
			->where('table_id = ' . $tableId);
		$db->setQuery($query);
		$c = $db->loadColumn();

		if (!in_array($fieldName, $c))
		{
			return $string;
		}

		// If there is more than one then figure out which one is the longest generally
		if (count($c) > 1)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			foreach ($c as $column)
			{
				$query->select('MAX(LENGTH(`' . $column . '`)) as `' . $column . '`');
			}

			$query->from($tableName);
			$db->setQuery($query);

			$l = $db->loadAssoc();
			arsort($l);
			$mainField = key($l);

			if ($mainField != $fieldName)
			{
				return $string;
			}
		}

		$string = str_replace($raw, '', $string);
		$string = $string . $raw;

		return trim($string);
	}
}
