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
	private static function getLinkText($language)
	{
		$linkTexts          = array ();
		$linkTexts['en-GB'] = "Translated using Neno for Joomla";
		$linkTexts['af-ZA'] = "Vertaal met Neno";
		$linkTexts['sq-AL'] = "Përkthyer me Neno";
		$linkTexts['ar-AA'] = "Neno ترجم مع";
		$linkTexts['be-BY'] = "Пераклад з Neno";
		$linkTexts['bs-BA'] = "Prevedeno sa Neno";
		$linkTexts['bg-BG'] = "Преведено с Neno";
		$linkTexts['ca-ES'] = "Traduït amb Neno";
		$linkTexts['zh-CN'] = "翻译与 Neno";
		$linkTexts['zh-TW'] = "翻譯與 Neno";
		$linkTexts['hr-HR'] = "Prevedeno sa Neno";
		$linkTexts['cs-CZ'] = "Překládal s Neno";
		$linkTexts['da-DK'] = "Oversat med Neno";
		$linkTexts['nl-NL'] = "Vertaald met Neno";
		$linkTexts['et-EE'] = "Tõlgitud on Neno";
		$linkTexts['fi-FI'] = "Käännetty Neno";
		$linkTexts['nl-BE'] = "Vertaald met Neno";
		$linkTexts['fr-CA'] = "Traduit avec Neno";
		$linkTexts['fr-FR'] = "Traduit avec Neno";
		$linkTexts['gl-ES'] = "Traducido con Neno";
		$linkTexts['de-DE'] = "Übersetzt mit Neno";
		$linkTexts['de-CH'] = "Übersetzt mit Neno";
		$linkTexts['de-AT'] = "Übersetzt mit Neno";
		$linkTexts['el-GR'] = "Μεταφράστηκε με Neno";
		$linkTexts['he-IL'] = "Nenoתורגם עם ";
		$linkTexts['hi-IN'] = "Neno के साथ अनुवाद";
		$linkTexts['hu-HU'] = "Fordította a Neno";
		$linkTexts['id-ID'] = "Diterjemahkan dengan Neno";
		$linkTexts['it-IT'] = "Tradotto con Neno";
		$linkTexts['ja-JP'] = "Neno で翻訳";
		$linkTexts['ko-KR'] = "Neno 로 번역";
		$linkTexts['lv-LV'] = "Tulkots ar Neno";
		$linkTexts['mk-MK'] = "Превод со Neno";
		$linkTexts['ms-MY'] = "Diterjemahkan dengan Neno";
		$linkTexts['nb-NO'] = "Oversatt med Neno";
		$linkTexts['fa-IR'] = "Nenoترجمه با ";
		$linkTexts['pl-PL'] = "Tłumaczone z Neno";
		$linkTexts['pt-BR'] = "Traduzido com Neno";
		$linkTexts['pt-PT'] = "Traduzido com Neno";
		$linkTexts['ro-RO'] = "Tradus cu Neno";
		$linkTexts['ru-RU'] = "Перевод с Neno";
		$linkTexts['sr-RS'] = "Преведено са Neno";
		$linkTexts['sr-YU'] = "Преведено са Neno";
		$linkTexts['sk-SK'] = "Prekladal s Neno";
		$linkTexts['es-ES'] = "Traducido con Neno";
		$linkTexts['sw-KE'] = "Kutafsiriwa na Neno";
		$linkTexts['sv-SE'] = "Översatt med Neno";
		$linkTexts['th-TH'] = "แปลกับ Neno";
		$linkTexts['tr-TR'] = "Neno ile çevrilmiş";
		$linkTexts['uk-UA'] = "Переклад з Neno";
		$linkTexts['vi-VN'] = "Dịch với Neno";

		if (!empty($linkTexts[$language]))
		{
			return $linkTexts[$language];
		}
		else
		{
			return $linkTexts['en-GB'];
		}
	}
}
