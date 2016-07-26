<?php

/**
 * @package     Neno
 * @subpackage  Filter
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;


class NenoFilterAlias extends NenoFilter
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
	public function filter($source)
	{
		if (JFactory::getConfig()->get('unicodeslugs') == 1)
		{
			return JFilterOutput::stringUrlUnicodeSlug($source);
		}
		else
		{
			$transliterator = $this->getTransliterator();

			return $transliterator->transliterate($source);
		}
	}

	/**
	 * Create a transliterator interface depends of the PHP version
	 *
	 * @since 2.1.15
	 *
	 * @return NenoFilterAliasInterfaceTransliterate
	 */
	protected function getTransliterator()
	{
		if (version_compare(PHP_VERSION, '5.4.0', '>='))
		{
			$transliterator = new NenoFilterAliasInterfaceTransliterate();
		}
		else
		{
			$transliterator = new NenoFilterAliasInterfaceLegacy();
		}

		return $transliterator;
	}
}