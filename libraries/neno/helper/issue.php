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
 * Neno helper.
 *
 * @since  1.0
 */
class NenoHelperIssue
{
	/**
	 * Formats a given date
	 *
	 * @param   $date  The date
	 *
	 * @return  string  The formated date
	 */
	private static function formatDate($date)
	{
		$d = new DateTime($date);

		return $d->format('Y-m-d \a\t H:i:s');
	}

	/**
	 * Gets the number of issues
	 *
	 * @param   string  $table      The table
	 *
	 * @param   string  $lang       Lang code
	 *
	 * @param   bool    $pending    Flag to count just the pending issues
	 *
	 * @return  int|null  The number
	 */
	public static function getIssuesNumber($table, $lang, $pending = true)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('COUNT(' . $db->quoteName('id') . ')')
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
			->where($db->quoteName('lang') . ' = ' . $db->quote($lang));

		if ($pending)
		{
			$query->where($db->quoteName('fixed') . ' = ' . $query->quote('0000-00-00 00:00:00'));
		}

		$db->setQuery($query);

		return $db->loadResult() ? $db->loadResult() : 0;
	}

	/**
	 * Cleans the issues that are not related to any item and are still pending
	 */
	private static function cleanIssues()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName(array('item_id', 'table_name')))
			->from('#__neno_content_issues')
			->where($db->quoteName('fixed') . ' = ' . $db->quote('0000-00-00 00:00:00'));

		$db->setQuery($query);
		$issues = $db->loadObjectList();

		foreach ($issues as $issue)
		{
			$subquery = '( SELECT id FROM ' . $db->quoteName($issue->table_name) . ' AS t WHERE (' . $db->quoteName('id') . ' = ' . (int) $issue->item_id . '))';

			$query->clear();
			$query
				->delete($db->quoteName('#__neno_content_issues'))
				->where(' NOT EXISTS ' . $subquery);

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Gets a list of issues
	 * 
	 * @param   bool  $pending  If true, it gets not solved issues
	 *                          
	 * @return array  The list                         
	 */
	public static function getList($lang, $pending = true)
	{
		// Clean pending unlinked issues
		self::cleanIssues();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		
		// List pending or solved issues
		$comp = ($pending) ? ' = ' : ' <> ';
		
		$query
			->select('*')
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('fixed') . $comp . $db->quote('0000-00-00 00:00:00'));

		if ($lang != null)
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote($lang));
		}

		$db->setQuery($query);
		
		return $db->loadObjectList();
	}

	/**
	 * Marks an issue as fixed
	 *
	 * @param   int  $pk  The issue id
	 *
	 * @return  mixed The result
	 */
	private static function solveIssue($pk)
	{
		$user  = JFactory::getUser();
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->update('#__neno_content_issues')
			->set(
				array(
					$db->quoteName('fixed_by') . ' = ' . (int) $user->id,
					$db->quoteName('fixed') . ' = NOW()'
				)
			)
			->where($db->quoteName('id') . ' = ' . (int) $pk);

		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Gets an issue by its id and try to fix it
	 *
	 * @param   int  $pk  The issue id
	 *
	 * @return  int The result
	 */
	public static function fixIssue($pk)
	{
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$result = 1;

		$query
			->select('*')
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('id') . ' = ' . (int) $pk);

		$db->setQuery($query);

		$issue = $db->loadObject();

		// Check the issue status
		if ($issue == null)
		{
			$result = 0;
		}
		elseif ($issue->fixed != '0000-00-00 00:00:00')
		{
			$result = 2;
		}
		else
		{
			switch ($issue->error_code)
			{
				case 'TRANSLATED_OUT_NENO' :
					$issue->parent = json_decode($issue->info)->parent;

					if (self::moveContentIntoShadowTables($issue) && self::solveIssue($pk))
					{
						$result = 1;
					}

					break;

				case 'NOT_SOURCE_LANG_CONTENT' :
					$result = 3;
					break;
			}
		}

		return $result;
	}

	private static function isFixed($issue)
	{
		return ($issue->fixed != '0000-00-00 00:00:00');
	}

	/**
	 * Render a single issue 
	 * 
	 * @param   stdClass  $issue     The issue
	 *
	 * @param   mixed     $viewLang  Filter by lang, if any                      
	 *                            
	 * @return  string The html output
	 */
	public static function renderIssue($issue, $viewLang = null)
	{
		$displayData             = new stdClass;
		$displayData->id         = $issue->id;
		$displayData->discovered = self::formatDate($issue->discovered);
		$displayData->error_code = $issue->error_code;
		$displayData->item_id    = $issue->item_id;
		$displayData->lang       = $issue->lang;
		$displayData->viewLang   = $viewLang;
		$displayData->extension  = $issue->table_name;
		$displayData->info       = json_decode($issue->info, true);
		$displayData->fixed      = (self::isFixed($issue)) ? self::formatDate($issue->fixed) : false;
		$displayData->fixed_by   = $issue->fixed_by;
		$displayData->fixable    = self::isFixable($issue);
		$displayData->details    = self::getIssueDetails($issue);

		return JLayoutHelper::render('issue', $displayData, JPATH_NENO_LAYOUTS);
	}

	/**
	 * Gets issue details
	 *
	 * @param   stdClass  $issue  The issue
	 *
	 * @return  stdClass  The details
	 */
	private static function getIssueDetails($issue)
	{
		$details = new stdClass;

		switch ($issue->table_name)
		{
			case '#__content' :

				if (self::isFixed($issue))
				{
					$user = JFactory::getUser($issue->fixed_by);
					$details->message     = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_SOLVED_' . $issue->error_code), $issue->table_name);
					$details->description = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_SOLVED_DESC_' . $issue->error_code), self::formatDate($issue->fixed), $user->name);
				}
				else
				{
					$item                 = self::getItemDetails($issue);
					$details->message     = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_ARTICLE'), $item['title'], $issue->table_name) . ' ' . JText::_('COM_NENO_ISSUE_MESSAGE_ERROR_' . $issue->error_code);
					$details->description = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_ERROR_DESC_' . $issue->error_code), $issue->lang, NenoSettings::get('source_language'));
				}

				break;
		}
		
		return $details;
	}

	/**
	 * Gets the details of the item linked to the issue
	 *
	 * @param   stdClass  $issue  The issue
	 *
	 * @return  array  The details
	 */
	private static function getItemDetails($issue)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName(array('id', 'title')))
			->from($db->quoteName($issue->table_name))
			->where($db->quoteName('id') . ' = ' . (int) $issue->item_id);

		$db->setQuery($query);

		return $db->loadAssoc();
	}

	/**
	 * Check if an issue can be automatically fixed
	 *
	 * @param   stdClass  $issue  The issue
	 *
	 * @return   bool  True if fixable, false if not
	 */
	private static function isFixable($issue)
	{
		return ($issue->error_code != 'NOT_SOURCE_LANG_CONTENT');
	}

	/**
	 * Generates an issue
	 *
	 * @param   string  $code       Error code
	 *
	 * @param   int     $item       Item id
	 *
	 * @param   string  $table      Table name
	 *
	 * @param   string  $opt        Json options string
	 *
	 * @return  bool
	 */
	public static function generateIssue($code, $item, $table, $lang, $opt)
	{
		$info   = json_encode($opt);
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$result = true;

		$query
			->select($db->quoteName(array('id', 'item_id', 'error_code', 'lang', 'fixed')))
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
			->where($db->quoteName('item_id') . ' = ' . (int) $item)
			->where($db->quoteName('fixed') . ' = ' . $db->quote('0000-00-00 00:00:00'));

		$db->setQuery($query);
		$db->execute();

		// If no issue on the item, generate a new one
		if ($db->getNumRows() == 0)
		{
			$query->clear();

			$query
				->insert($db->quoteName('#__neno_content_issues'))
				->columns(array('discovered', 'error_code', 'item_id', 'table_name', 'lang', 'info'))
				->values('NOW(), ' . $db->quote($code) . ', ' . $db->quote($item) . ', ' . $db->quote($table) . ', ' . $db->quote($lang) . ', ' . $db->quote($info));

			$db->setQuery($query);
			$result = $db->execute();
		}
		else
		{
			// The item has an unsolved issue
			$issue = $db->loadObject();

			// If the issue is different remove the old one and then create a new one
			if ($issue->error_code != $code)
			{
				$query->clear();

				$query
					->delete($db->quoteName('#__neno_content_issues'))
					->where($db->quoteName('id') . ' = ' . (int) $issue->id);

				$db->setQuery($query);
				$result = $db->execute();

				if ($result)
				{
					$result = self::generateIssue($code, $item, $table, $lang, $opt);
				}
			}
		}

		return $result;
	}

	/**
	 * Removes issues according to their parent item and table
	 *
	 * @param   int     $pk         The id
	 *
	 * @param   string  $tableName  The table name
	 *
	 * @return  mixed
	 */
	public static function removeIssues($pk, $tableName)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->delete($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('item_id') . ' = ' . (int) $pk)
			->where($db->quoteName('table_name') . ' = ' . $db->quote($tableName));

		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Check if an item is issued
	 *
	 * @param   int  $pk  The item_id
	 *
	 * @return stdClass The issue id & error_code
	 */
	public static function isIssued($pk)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName(array('id', 'error_code')))
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('item_id') . ' = ' . (int) $pk);

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Move content to shadow tables
	 *
	 * @param   stdClass  $opt  Item options
	 *
	 * @return bool
	 */
	private static function moveContentIntoShadowTables($opt)
	{
		/** @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('*')
			->from($db->quoteName($opt->table_name))
			->where($db->quoteName('language') . ' = ' . $db->quote($opt->lang));

		$db->setQuery($query);
		$elements = $db->loadAssocList();

		if (count($elements) > 0)
		{
			$query = $db->getQuery(true);
			$query
				->select(array('f.*', 't.table_name'))
				->from($db->quoteName('#__neno_content_element_fields', 'f'))
				->join('left', $db->quoteName('#__neno_content_element_tables', 't') . ' ON (t.id = f.table_id)')
				->where($db->quoteName('f.translate') . ' = 1')
				->where($db->quoteName('t.table_name') . ' = ' . $db->quote($opt->table_name));

			$db->setQuery($query);
			$fields = $db->loadAssocList();

			foreach ($elements as $element)
			{
				foreach ($fields as $field)
				{
					/* @var $f NenoContentElementField */
					$f = NenoContentElementField::load($field['id'], true, true);

					$f->convertContentToTranslation(array('id' => $element['id']), $opt->parent, $opt->lang);
				}
				
				$query = $db->getQuery(true);
				$query
					->delete($db->quoteName($opt->table_name))
					->where($db->quoteName('id') . ' = ' . (int) $element['id']);

				$db->setQuery($query);
				$db->execute();

				$table = NenoContentElementTable::load(array('table_name' => $opt->table_name), false, true);
				NenoHelper::trashTranslations($table, $element['id']);
			}
		}

		return true;
	}
}
