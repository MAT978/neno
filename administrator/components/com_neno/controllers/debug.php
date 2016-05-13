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
	 * 
	 */
	public function fixMenus()
	{
		$menus = NenoHelper::createMenuStructure('fixMenus');
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

		$view         = $this->getView('FixContent', 'html');
		$view->menus  = $result;
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
	 * This methhod
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
			->select('tr.id')
			->from('#__neno_content_element_translation AS tr')
			->where('tr.string = ' . $db->quote('null'));

		$db->setQuery($query);
		$translationsWithNull = $db->loadColumn();
		$result               = null;

		foreach ($translationsWithNull as $translationWithNull)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationWithNull, false, true);

			if (!empty($translation))
			{
				$translation->refresh();

				// Only replace strings that has null in it
				if ($translation->getString() === 'null')
				{
					$translation
						->setString($translation->getOriginalText())
						->persist();

					$result[] = $translation->getOriginalText();
				}
			}
		}


		$view        = $this->getView('FixContent', 'html');
		$view->item = $result;
		$view->display('nullissue');
	}
}
