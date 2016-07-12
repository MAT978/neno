<?php

/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

/**
 * Manifest Groups & Elements controller class
 *
 * @since  1.0
 */
class NenoControllerDebug extends JControllerAdmin
{
	/**
	 * Gets a list of issues
	 *
	 * @throws Exception
	 */
	public function listIssues()
	{
		$app  = JFactory::getApplication();
		$lang = $app->input->get('lang', NULL);

		$view          = $this->getView('issues', 'html');
		$view->pending = NenoHelperIssue::getList($lang);
		$view->solved  = NenoHelperIssue::getList($lang, false);
		$view->lang    = $lang;
		$view->display('list');
	}

	/**
	 * Issues fixer
	 *
	 * @throws Exception
	 */
	public function fixIssue()
	{
		$app  = JFactory::getApplication();
		$pk   = $app->input->get('id');
		$lang = $app->input->get('lang', NULL);

		$lang  = ($lang == NULL) ? '' : '&lang=' . $lang;
		$issue = NenoHelperIssue::getIssue($pk);

		switch (NenoHelperIssue::fixIssue($pk))
		{
			// Failure
			case 0 :
				$app->enqueueMessage(JText::_('COM_NENO_ISSUE_FIX_FAILURE'), 'error');
				break;
			
			// Success
			case 1 :
				$app->enqueueMessage(JText::_('COM_NENO_ISSUE_FIX_SUCCESS'), 'success');
				NenoLog::log('Issue ' . $issue->error_code . '-' . $issue->lang . ' fixed', NenoLog::ACTION_ISSUE_FIXED, JFactory::getUser()->id);
				break;
			
			// Already fixed
			case 2 :
				$app->enqueueMessage(JText::_('COM_NENO_ISSUE_FIX_ALREADY_FIXED'), 'success');
				break;

			// Cant be automatically fixed
			case 3 :
				$app->enqueueMessage(JText::_('COM_NENO_ISSUE_FIX_CANT_BE_FIXED'), 'success');
				break;
		}

		$this->setRedirect('index.php?option=com_neno&task=debug.listIssues' . $lang);
	}

	/**
	 * Fix non associated menus
	 */
	public function fixMenus()
	{
		$menus  = NenoHelper::createMenuStructure('fixMenus');
		$result = array();

		if (is_array($menus) && count($menus) > 0)
		{
			foreach ($menus as $menu)
			{
				$result[] = NenoHelperBackend::renderMenuFixingMessage(true, $menu->title);
			}
		}
		else
		{
			$result = NenoHelperBackend::renderMenuFixingMessage(false);
		}

		$view        = $this->getView('FixContent', 'html');
		$view->menus = $result;
		$view->display('menus');
	}

	/**
	 * This method fixes content issue
	 *
	 * @throws Exception
	 */
	public function fixContentConfigurationIssue()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$tables = array();

		$languages = NenoHelper::getLanguages(false, false);

		foreach ($languages as $language)
		{
			$destinationTable = $db->generateShadowTableName('#__content', $language->lang_code);

			$query
			  ->updateJoin($db->quoteName($destinationTable, 'c1'), '#__content AS c2')
			  ->set('c1.attribs = c2.attribs')
			  ->set('c1.fulltext = IF(c1.fulltext = \'null\', \'\', c1.fulltext)')
			  ->where('c1.id = c2.id');

			$db->setQuery($query);

			$res      = $db->execute();
			$tables[] = NenoHelperBackend::renderTableFixingMessage($destinationTable, $res);
		}

		$view         = $this->getView('FixContent', 'html');
		$view->tables = $tables;
		$view->display('tables');
	}

	/**
	 * This method fix all old null issues on translations
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function fixNullIssue()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select($db->quoteName(array('tr.id', 'f.field_name')))
		  ->from($db->quoteName('#__neno_content_element_translations', 'tr'))
		  ->join('left', $db->quoteName('#__neno_content_element_fields', 'f') . 'ON (' . $db->quoteName('tr.content_id') . ' = ' . $db->quoteName('f.id') . ')')
		  ->where('tr.content_type = \'db_string\'')
		  ->where('tr.string = ' . $db->quote('null'));

		$db->setQuery($query);
		$translationsWithNull = $db->loadObjectList();
		$result               = NULL;

		foreach ($translationsWithNull as $translationWithNull)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationWithNull->id, false, true);


			if (!empty($translation))
			{
				$translation->refresh();

				// Only replace strings that has null in it
				if ($translation->getString() === 'null')
				{
					$ok = $translation
					  ->setString($translation->getOriginalText())
					  ->persist();

					$item        = new stdClass;
					$item->text  = $translation->getOriginalText();
					$item->table = $translation->getDbTable();
					$item->field = $translationWithNull->field_name;
					$item->lang  = $translation->getLanguage();
					$item->res   = $ok;

					$result[] = NenoHelperBackend::renderNullFixingMessage($item);
					unset($item);
				}
			}
		}

		$view       = $this->getView('FixContent', 'html');
		$view->item = $result;
		$view->display('nullissue');
	}

	/**
	 * Generate and force to download report
	 *
	 * @throws \Exception
	 */
	public function downloadReport()
	{
		header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=report-" . date('YmdHis') . ".txt");

		echo NenoHelperBackend::printServerInformation(NenoHelperBackend::getServerInfo());
		JFactory::getApplication()->close();
	}
}
