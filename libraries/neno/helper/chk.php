<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* No direct access */
defined('_JEXEC') or die;

/**
 * Neno chk helper.
 *
 * @since  1.0
 */
class NenoHelperChk extends NenoHelperLicense
{
	/**
	 * Checks license
	 *
	 * @return bool
	 */
	public static function chk()
	{
		$licenseData = self::getLicenseData();

		if (count($licenseData) !== 4)
		{
			return false;
		}

		if (self::checkDomainMatch($licenseData[2]) === false)
		{
			return false;
		}

		if (strtotime($licenseData[3]) < time())
		{
			return false;
		}

		return true;
	}

	/**
	 * Check domain
	 *
	 * @param   string $domain Domain
	 *
	 * @return bool
	 */
	protected static function checkDomainMatch($domain)
	{
		if (mb_strpos(JUri::root(), $domain) === false)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Get link
	 *
	 * @param   string $language Language tag
	 *
	 * @return string
	 */
	public static function getLink($language)
	{
		$linkText = self::getLinkText($language);
		$link     = '<br /><br /><a target="_blank" href="http://www.neno-translate.com" title="' . $linkText . ' (Joomla)">' . $linkText . '</a>';

		return $link;
	}

	/**
	 * Get Link text
	 *
	 * @param   string $language Language
	 *
	 * @return string
	 */
	protected static function getLinkText($language)
	{
		$linkTexts = static::getLinkTexts();

		if (!empty($linkTexts[$language]))
		{
			return $linkTexts[$language];
		}
		else
		{
			return $linkTexts['en-GB'];
		}
	}

	/**
	 * Get text for links
	 *
	 *
	 * @return array
	 *
	 * @since 2.1.32
	 */
	protected static function getLinkTexts()
	{
		$linkTexts = array(
			'en-GB' => 'Translated using Neno for Joomla',
			'af-ZA' => 'Vertaal met Neno',
			'sq-AL' => 'Përkthyer me Neno',
			'ar-AA' => 'Neno ترجم مع',
			'be-BY' => 'Пераклад з Neno',
			'bs-BA' => 'Prevedeno sa Neno',
			'bg-BG' => 'Преведено с Neno',
			'ca-ES' => 'Traduït amb Neno',
			'zh-CN' => '翻译与 Neno',
			'zh-TW' => '翻譯與 Neno',
			'hr-HR' => 'Prevedeno sa Neno',
			'cs-CZ' => 'Překládal s Neno',
			'da-DK' => 'Oversat med Neno',
			'nl-NL' => 'Vertaald met Neno',
			'et-EE' => 'Tõlgitud on Neno',
			'fi-FI' => 'Käännetty Neno',
			'nl-BE' => 'Vertaald met Neno',
			'fr-CA' => 'Traduit avec Neno',
			'fr-FR' => 'Traduit avec Neno',
			'gl-ES' => 'Traducido con Neno',
			'de-DE' => 'Übersetzt mit Neno',
			'de-CH' => 'Übersetzt mit Neno',
			'de-AT' => 'Übersetzt mit Neno',
			'el-GR' => 'Μεταφράστηκε με Neno',
			'he-IL' => 'Nenoתורגם עם ',
			'hi-IN' => 'Neno के साथ अनुवाद',
			'hu-HU' => "Fordította a Neno",
			'id-ID' => "Diterjemahkan dengan Neno",
			'it-IT' => "Tradotto con Neno",
			'ja-JP' => "Neno で翻訳",
			'ko-KR' => "Neno 로 번역",
			'lv-LV' => "Tulkots ar Neno",
			'mk-MK' => "Превод со Neno",
			'ms-MY' => "Diterjemahkan dengan Neno",
			'nb-NO' => "Oversatt med Neno",
			'fa-IR' => "Nenoترجمه با ",
			'pl-PL' => "Tłumaczone z Neno",
			'pt-BR' => "Traduzido com Neno",
			'pt-PT' => "Traduzido com Neno",
			'ro-RO' => "Tradus cu Neno",
			'ru-RU' => "Перевод с Neno",
			'sr-RS' => "Преведено са Neno",
			'sr-YU' => "Преведено са Neno",
			'sk-SK' => "Prekladal s Neno",
			'es-ES' => "Traducido con Neno",
			'sw-KE' => "Kutafsiriwa na Neno",
			'sv-SE' => "Översatt med Neno",
			'th-TH' => "แปลกับ Neno",
			'tr-TR' => "Neno ile çevrilmiş",
			'uk-UA' => "Переклад з Neno",
			'vi-VN' => "Dịch với Neno"
		);

		return $linkTexts;
	}

	/**
	 * Remove backlink from shadow tables
	 *
	 * @param string $language
	 *
	 * @return void
	 *
	 *
	 * @since 2.1.32
	 */
	public static function removeBacklink($language)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from('#__neno_backlink_metadata')
			->where('language = ' . $db->quote($language));

		$db->setQuery($query);
		$backlinksMetadata = $db->loadAssocList();
		$link              = static::getLink($language);

		foreach ($backlinksMetadata as $backlinkMetadata)
		{
			$whereStatements = json_decode($backlinkMetadata['where_statements'], true);

			$query
				->clear()
				->select($db->quoteName($backlinkMetadata['column_name']))
				->from($db->quoteName($backlinkMetadata['table_name']))
				->where($whereStatements);

			$db->setQuery($query);
			$text      = $db->loadResult();
			$cleanText = preg_replace('/' . preg_quote($link, '/') . '/', '', $text);

			// The text has been cleaned, let's updated
			if ($cleanText != $text)
			{
				$query
					->clear()
					->update($db->quoteName($backlinkMetadata['table_name']))
					->set($db->quoteName($backlinkMetadata['column_name']) . ' = ' . $db->quote($cleanText))
					->where($whereStatements);

				$db->setQuery($query);
				$db->execute();

				// If the data has been updated, let's remove its metadata
				if ($db->getAffectedRows() != 0)
				{
					$db->deleteObject('#__neno_backlink_metadata', $backlinkMetadata['id']);
				}
			}
		}
	}

	/**
	 * Create backlink metadata from old system
	 *
	 * @since 2.1.32
	 */
	public static function createBacklinkMetadataFromOldSystem()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db           = JFactory::getDbo();
		$shadowTables = $db->getShadowTables();
		$linkTexts    = static::getLinkTexts();
		$query        = $db->getQuery(true);

		foreach ($shadowTables as $shadowTable)
		{
			$columns = array_keys($db->getTableColumns($shadowTable, true));
			$query
				->clear()
				->select('*')
				->from($db->quoteName($shadowTable));

			foreach ($columns as $column)
			{
				$query->where('CHAR_LENGTH(' . $db->quoteName($column) . ') >= 500', 'OR');
			}

			$db->setQuery($query);
			$records = $db->loadAssocList();

			foreach ($records as $record)
			{
				$field        = null;
				$linkLanguage = null;
				foreach ($record as $key => $value)
				{
					if (mb_strlen($value) > 500)
					{
						foreach ($linkTexts as $language => $linkText)
						{
							if (preg_match('/' . preg_quote($linkText) . '/', $value))
							{
								$field        = $key;
								$linkLanguage = $language;
								break 2;
							}
						}
					}
				}

				if ($field !== null)
				{
					$whereStatements = array();
					foreach ($record as $key => $value)
					{
						if ($key != $field)
						{
							$whereStatements[] = $db->quoteName($key) . ' = ' . $db->quote($value);
						}
					}

					$linkMetadata = (object) array(
						'table_name'      => $shadowTable,
						'column_name'     => $field,
						'language'        => $linkLanguage,
						'where_statement' => json_encode($whereStatements)
					);

					$db->insertObject('#__neno_backlink_metadata', $linkMetadata, array('id'));
				}
			}
		}
	}
}
