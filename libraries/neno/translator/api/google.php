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
 * Class NenoTranslateApiGoogle
 *
 * @since  1.0
 */
class NenoTranslatorApiGoogle extends NenoTranslatorApi
{
	/**
	 * Translate text using google api
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
		$source = $this->convertFromJisoToIso($source);
		$target = $this->convertFromJisoToIso($target);

		$apiKey = NenoSettings::get('translator_api_key');

		$url = 'https://www.googleapis.com/language/translate/v2';
        
        //Chunk the text if need be
        $chunks = NenoHelper::chunkHtmlString($text, 4900);
        $translatedChunks = array();        
        
        foreach ($chunks as $chunk)
        {
			// Invoke the POST request.
			$response = $this->post(
				$url,
				array ('key' => $apiKey, 'q' => $chunk, 'source' => $source, 'target' => $target),
				array ('X-HTTP-Method-Override' => 'GET')
			);
            
			// Log it if server response is not OK.
			if ($response->code != 200)
			{
				NenoLog::log('Google API failed with response: ' . $response->code, 1);
				$responseData = json_decode($response->body, true);
				throw new Exception($responseData['error']['errors'][0]['message'] . ' (' . $responseData['error']['errors'][0]['reason'] . ')', $response->code);
			}
			else
			{
				$responseBody = json_decode($response->body);
				$translatedChunks[] = $responseBody->data->translations[0]->translatedText;
			}
		}
        
        return implode(' ', $translatedChunks);        
        
	}

	/**
	 * Method to make supplied language codes equivalent to google api codes
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
			case 'zh':
				$iso = $jiso;
				break;

			case 'he':
				$iso = 'iw';
				break;

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
