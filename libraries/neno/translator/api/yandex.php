<?php
/**
 * @package     Neno
 * @subpackage  TranslateApi
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoTranslateApiYandex
 *
 * @since  1.0
 */
class NenoTranslatorApiYandex extends NenoTranslatorApi
{
	/**
	 * {@inheritdoc}
	 *
	 * @param   Joomla\Registry\Registry $options   JHttp client options
	 * @param   JHttpTransport           $transport JHttp client transport
	 */
	public function __construct(Joomla\Registry\Registry $options = NULL, JHttpTransport $transport = NULL)
	{
		parent::__construct();

		// Get the api key
		$this->apiKey = NenoSettings::get('translator_api_key');
	}

	/**
	 * Translate text using yandex api
	 *
	 * @param   string $text   text to translate
	 * @param   string $source source language
	 * @param   string $target target language
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function translate($text, $source, $target)
	{
		// Convert from JISO to ISO codes
		$target = $this->convertFromJisoToIso($target);

		// Language parameter for url
		$source = $this->convertFromJisoToIso($source);
		$lang   = $source . "-" . $target;

		$apiKey = NenoSettings::get('translator_api_key');

		//Chunk the text if need be
		$chunks           = NenoHelper::chunkHtmlString($text, 9900);
		$translatedChunks = array();

		foreach ($chunks as $chunk)
		{
			$url = 'https://translate.yandex.net/api/v1.5/tr.json/translate?key=' . $apiKey . '&lang=' . $lang;

			// Invoke the POST request.
			$response = $this->post($url, array('text' => $chunk));

			// Log it if server response is not OK.
			if ($response->code != 200)
			{
				NenoLog::log('Yandex API failed with response: ' . $response->code, '', 0, NenoLog::PRIORITY_ERROR);
				$responseData = json_decode($response->body, true);
				throw new Exception(JText::_('COM_NENO_EDITOR_YANDEX_ERROR_CODE_' . $responseData['code']), $responseData['code']);
			}
			else
			{
				$responseBody       = json_decode($response->body);
				$translatedChunks[] = $responseBody->text[0];
			}

		}

		return implode(' ', $translatedChunks);

	}

	/**
	 * Method to make supplied language codes equivalent to yandex api codes
	 *
	 * @param   string $jiso Joomla ISO language code
	 *
	 * @return string
	 */
	public function convertFromJisoToIso($jiso)
	{
		// Split the language code parts using hyphen
		$jisoParts = (explode('-', $jiso));
		$isoTag    = strtolower($jisoParts[0]);

		switch ($isoTag)
		{
			case 'nb':
				$iso = 'no';
				break;

			default:
				$iso = $isoTag;
				break;
		}

		return $iso;
	}
}
