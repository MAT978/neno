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
class NenoTranslatorApiBing extends NenoTranslatorApi
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

		list($clientId, $clientSecret) = explode(':', NenoSettings::get('translator_api_key'));
		$accessToken = $this->requestAccessToken($clientId, $clientSecret);

		$url = 'http://api.microsofttranslator.com/V2/Http.svc/Translate';

		//Chunk the text if need be
		$chunks           = NenoHelper::chunkHtmlString($text, 4900);
		$translatedChunks = array();

		foreach ($chunks as $chunk)
		{
			$query = http_build_query(array(
			  'from' => $source,
			  'text' => $chunk,
			  'to'   => $target
			));
			// Invoke the POST request.
			$response = $this->get(
			  $url . '?' . $query,
			  array('Authorization' => 'Bearer ' . $accessToken)
			);

			$responseBody = (array) simplexml_load_string($response->body);

			// Log it if server response is not OK.
			if ($response->code != 200)
			{
				NenoLog::log('Bing API failed with response: ' . $response->code, '', 0, NenoLog::PRIORITY_ERROR);
				throw new Exception((string) $responseBody['body']->p[1], $response->code);
			}
			else
			{
				$translatedChunks[] = $responseBody[0];
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
				$iso = 'zh-CHS';
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

	/**
	 * Request Access token
	 *
	 * @param string $clientId     Client Id
	 * @param string $clientSecret Client secret
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function requestAccessToken($clientId, $clientSecret)
	{
		//Create the request Array.
		$paramArr = array(
		  'grant_type'    => 'client_credentials',
		  'scope'         => 'http://api.microsofttranslator.com',
		  'client_id'     => $clientId,
		  'client_secret' => $clientSecret
		);

		$credentialsRequest = $this->post('https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/', $paramArr);

		//Decode the returned JSON string.
		$objResponse = json_decode($credentialsRequest->body);
		if ($objResponse->error)
		{
			throw new Exception($objResponse->error_description);
		}

		return $objResponse->access_token;
	}
}
