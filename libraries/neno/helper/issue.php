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
	private static function formatDate($date)
	{
		$d = new DateTime($date);

		return $d->format('Y-m-d \a\t H:i:s');
	}

	/**
	 * Gets the number of issues
	 *
	 * @param   string  $extension  The extension
	 *
	 * @param   string  $lang       Lang code
	 *
	 * @return  int|null  The number
	 */
	public static function getIssuesNumber($extension, $lang)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('COUNT(' . $db->quoteName('id') . ')')
			->from($db->quoteName('#__neno_content_issues'))
			->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
			->where($db->quoteName('lang') . ' = ' . $db->quote($lang));

		$db->setQuery($query);

		return $db->loadResult();
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
		$db = JFactory::getDbo();
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
	 * Render a single issue 
	 * 
	 * @param   stdClass  $issue  The issue
	 *
	 * @return  string The html output
	 */
	public static function renderIssue($issue)
	{
		$displayData             = new stdClass;
		$displayData->id         = $issue->id;
		$displayData->discovered = self::formatDate($issue->discovered);
		$displayData->error_code = $issue->error_code;
		$displayData->item_id    = $issue->item_id;
		$displayData->lang       = $issue->lang;
		$displayData->extension  = $issue->extension;
		$displayData->info       = json_decode($issue->info, true);
		$displayData->fixed      = ($issue->fixed == '0000-00-00 00:00:00') ? false : self::formatDate($issue->fixed);
		$displayData->fixed_by   = $issue->fixed_by;
		$displayData->fixable    = self::isFixable($issue);
		$displayData->details    = self::getIssueDetails($issue);

		return JLayoutHelper::render('issue', $displayData, JPATH_NENO_LAYOUTS);
	}

	private static function getIssueDetails($issue)
	{
		$details = new stdClass;

		switch ($issue->extension)
		{
			case 'com_content' :
				$item                 = self::getItemDetails($issue);
				$details->message     = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_ARTICLE'), $item['title'], $issue->extension) . ' ' . JText::_('COM_NENO_ISSUE_MESSAGE_ERROR_' . $issue->error_code);
				$details->description = sprintf(JText::_('COM_NENO_ISSUE_MESSAGE_ERROR_DESC_' . $issue->error_code), $issue->lang, NenoSettings::get('source_language'));
				break;
		}
		
		return $details;
	}

	private static function getItemDetails($issue)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query
			->select($db->quoteName(array('id', 'title')))
			->from($db->quoteName('#__' . substr($issue->extension, 4)))
			->where($db->quoteName('id') . ' = ' . (int) $issue->item_id);

		$db->setQuery($query);

		return $db->loadAssoc();
	}

	private static function isFixable($issue)
	{
		return ($issue->error_code != 'NOT_SOURCE_LANG_CONTENT');
	}
}
