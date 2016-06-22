<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoHelperApi
 *
 * @since  1.0
 */
class NenoHelperApi
{
	/**
	 * @var JHttp
	 */
	protected static $httpClient;

	/**
	 * Return the amount of the link credits available
	 *
	 * @return int
	 */
	public static function getTcAvailable()
	{
		list($status, $userData) = self::makeApiCall('user');

		if ($status !== false && is_array($userData))
		{
			return empty($userData['response']['fundsAvailable']) ? 0 : $userData['response']['fundsAvailable'];
		}

		return 0;
	}

	/**
	 * Execute API Call
	 *
	 * @param   string $apiCall    API Call
	 * @param   string $method     Http Method
	 * @param   array  $parameters API call parameters
	 *
	 * @return array
	 */
	public static function makeApiCall($apiCall, $method = 'GET', $parameters = array())
	{
		self::getHttp();

		$apiEndpoint    = NenoSettings::get('api_server_url');
		$licenseCode    = NenoSettings::get('license_code');
		$response       = null;
		$responseStatus = false;

		if (!empty($apiEndpoint) && !empty($licenseCode))
		{
			$method = strtolower($method);

			if (method_exists(self::$httpClient, $method))
			{
				if ($method === 'get')
				{
					if (!empty($parameters))
					{
						$query   = implode('/', $parameters);
						$apiCall = $apiCall . '/' . $query;
					}

					$apiResponse = self::$httpClient->{$method}($apiEndpoint . $apiCall, array('Authorization' => $licenseCode));
				}
				else
				{
					// Get information from the user
					$parameters = array_merge($parameters, self::getUserInformation());

					$apiResponse = self::$httpClient->{$method}(
						$apiEndpoint . $apiCall,
						json_encode($parameters),
						array(
							'Content-Type'  => 'application/json',
							'Authorization' => $licenseCode
						)
					);
				}

				/* @var $apiResponse JHttpResponse */
				$data = $apiResponse->body;

				if ($apiResponse->headers['Content-Type'] === 'application/json')
				{
					$data = json_decode($data, true);
				}

				$response = $data;

				if ($apiResponse->code == 200)
				{
					$responseStatus = true;
				}
			}
		}

		return array($responseStatus, $response);
	}

	/**
	 * Get information about the domain, source language and target languages.
	 *
	 * @return array
	 */
	protected static function getUserInformation()
	{
		return array(
			'user_information' => array(
				'domain'           => JUri::root(),
				'source_language'  => NenoSettings::get('source_language'),
				'target_languages' => array_keys(NenoHelper::getTargetLanguages(false))
			)
		);
	}

	/**
	 * Instantiate http client using Singleton approach
	 *
	 * @return void
	 */
	protected static function getHttp()
	{
		if (self::$httpClient === null)
		{
			self::$httpClient = JHttpFactory::getHttp();
		}
	}

	/**
	 * Download Job file from the API
	 *
	 * @param int    $jobId    Job ID
	 * @param string $filePath File path where the file is going to be saved
	 *
	 * @return bool
	 */
	public static function getJobFile($jobId, $filePath)
	{
		list($status, $fileContents) = self::makeApiCall('job', 'GET', array((int) $jobId));

		if ($status)
		{
			file_put_contents($filePath, $fileContents);
		}
		else
		{
			NenoLog::log($fileContents, NenoLog::PRIORITY_ERROR, true);
		}

		return $status;
	}
}
