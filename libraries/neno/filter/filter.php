<?php

/**
 * @package     Neno
 * @subpackage  Filter
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;


abstract class NenoFilter
{
	/**
	 * Filter source
	 *
	 * @since 2.1.15
	 *
	 * @param string $source Source text
	 *
	 * @return null|string
	 */
	public abstract function filter($source);

	/**
	 * Get filter class
	 *
	 * @param string $type Filter type
	 *
	 * @since 2.1.15
	 *
	 * @return NenoFilter|boolean
	 */
	public static function getFilter($type)
	{
		$className = 'NenoFilter' . ucfirst(strtolower($type));

		if (class_exists($className))
		{
			$filter = new $className;

			return $filter;
		}

		return false;
	}

}