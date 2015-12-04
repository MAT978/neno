<?php

/**
 * @package     Neno
 * @subpackage  Cache
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Class NenoCache
 *
 * @since  1.0
 */
class NenoCache
{
	/**
	 * Get data from the Application cache
	 *
	 * @param   string $cacheId Cache Identifier
	 * @param   mixed  $default Default value for this cache data
	 *
	 * @return mixed Data cached
	 */
	public static function getCacheData($cacheId, $default = null)
	{
		try
		{
			$app  = JFactory::getApplication();
			$data = $app->get($cacheId, $default);
		} catch (Exception $e)
		{
			$data = $default;
		}

		return $data;
	}

	/**
	 * Set data into the cache
	 *
	 * @param   string $cacheId Cache Identifier
	 * @param   mixed  $data    Data to cache
	 *
	 * @return void
	 */
	public static function setCacheData($cacheId, $data)
	{
		$app = JFactory::getApplication();
		$app->set($cacheId, $data);
	}

	/**
	 * Get cache Id
	 *
	 * @param   string $functionName Function name
	 * @param   array  $arguments    Function arguments
	 *
	 * @return string
	 */
	public static function getCacheId($functionName, array $arguments)
	{
		return md5($functionName . md5(json_encode($arguments)));
	}
}
