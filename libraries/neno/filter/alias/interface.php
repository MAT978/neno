<?php

/**
 * @package     Neno
 * @subpackage  Filter
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;


interface NenoFilterAliasInterface
{
	/**
	 * Converts string to the given encoding
	 *
	 * @since 2.1.15
	 *
	 * @param string $string
	 *
	 * @return mixed
	 */
	public function transliterate($string);
}