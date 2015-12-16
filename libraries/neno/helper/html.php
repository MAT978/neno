<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

class NenoHelperHtml
{
	/**
	 * Get word count for a string
	 *
	 * @param string $string String where get the word count
	 *
	 * @return int
	 */
	public static function getWordCount($string)
	{
		$wc = strip_tags(self::splitHtmlText($string));

		// remove one-letter 'words' that consist only of punctuation
		$wc = trim(preg_replace("#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|:|\&|@)]\s*#", " ", $wc));

		// remove superfluous whitespace
		$wc = preg_replace("/\s\s+/", " ", $wc);

		// split string into an array of words
		$wc = explode(" ", $wc);

		// remove empty elements
		$wc = array_filter(array_map('trim', $wc));

		// return the number of words
		return count($wc);
	}

	/**
	 * Split HTML into p tags with content in it
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function splitHtmlText($string)
	{
		$result = self::getStructure($string);

		if (is_array($result))
		{
			return $result[1];
		}

		return $result;
	}

	/**
	 * Get the structure of a HTML text
	 *
	 * @param   string $string        HTML string
	 * @param   bool   $returnStrings If the content strings should be returned too
	 *
	 * @return string|array
	 */
	protected static function getStructure($string, $returnStrings = true)
	{
		$dom = self::createDomDocument($string);

		if ($dom === false)
		{
			return $string;
		}

		$strings   = self::recursiveDomExplorer($dom->documentElement);
		$structure = self::fromDomDocumentToString($dom);

		if ($returnStrings)
		{
			$convertedString = '';

			foreach ($strings as $key => $string)
			{
				$convertedString .= '<p id="' . $key . '">' . $string . '</p>';
			}

			$structure = array( $structure, $convertedString );
		}

		return $structure;
	}

	/**
	 * Creates DOMDocument class if it's a string, if it's not, return false
	 *
	 * @param $string
	 *
	 * @return bool|DOMDocument
	 */
	protected static function createDomDocument($string)
	{
		if (self::isHtml($string))
		{
			$dom = new DOMDocument;
			$dom->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));

			return $dom;
		}

		return false;
	}

	/**
	 * Check if the string is a html string
	 *
	 * @param   string $string String to check
	 *
	 * @return bool
	 */
	protected static function isHtml($string)
	{
		return preg_match('/<[^<]+>/', $string) != 0;
	}

	/**
	 * Recursive function that goes through DOM of a HTML string replacing content with special tags
	 *
	 * @param   DOMElement $domElement DOM element
	 * @param   int        $index      Index
	 *
	 * @return array
	 */
	protected static function recursiveDomExplorer(DOMElement $domElement, &$index = 1)
	{
		$humanReadableAttributes = array( 'alt', 'title', 'summary' );
		$strings                 = array();

		/* @var $node DomElement */
		foreach ($domElement->childNodes as $node)
		{
			if ($node->nodeName == '#text')
			{
				$text              = trim($node->nodeValue);
				$strings[ $index ] = $text;
				$node->nodeValue   = str_replace($text, '[{|' . $index . '|}]}', $node->nodeValue);
				$index++;
			}
			else
			{
				foreach ($humanReadableAttributes as $humanAttribute)
				{
					$nodeClass       = get_class($node);
					$notAllowedClass = array(
						'DOMComment',
						'DOMCdataSection'
					);
					if (!in_array($nodeClass, $notAllowedClass))
					{
						if ($node->hasAttribute($humanAttribute))
						{
							$attribute         = $node->getAttribute($humanAttribute);
							$text              = trim($attribute);
							$strings[ $index ] = $text;
							$node->setAttribute($humanAttribute, str_replace($text, '[{|' . $index . '|}]}', $attribute));
							$index++;
						}
					}
				}
			}

			// If the node has children, let's go through them
			if ($node->hasChildNodes())
			{
				$strings = $strings + self::recursiveDomExplorer($node, $index);
			}
		}

		return $strings;
	}

	/**
	 * Convert from DOMDocument to string, It strips html and body tags because database content won't have those tags.
	 *
	 * @param   DOMDocument $document Document
	 *
	 * @return bool|string
	 */
	protected static function fromDomDocumentToString(DOMDocument $document)
	{
		$matches = null;

		// Get content between body tags (DOMDocument class add it)
		if (preg_match('@<body>(.+)<\/body>@s', $document->saveHTML(), $matches) != 0)
		{
			return $matches[1];
		}

		return false;
	}

	/**
	 * Replaces translations in a HTML text
	 *
	 * @param string $string      Original HTML text
	 * @param string $translation Text received by the translator
	 *
	 * @return string
	 */
	public static function replaceTranslationsInHtmlTag($string, $translation)
	{
		if (preg_match('/(<p[^>]*>.*?<\/p>)/s', $translation))
		{
			$structure = self::getStructure($string, false);

			$document = new DOMDocument;
			$document->loadHTML($translation);

			$pTags = $document->getElementsByTagName('p');

			/* @var $pTag DomElement */
			foreach ($pTags as $pTag)
			{
				$structure = str_replace('[{|' . $pTag->getAttribute('id') . '|}]', $pTag->nodeValue, $structure);
			}

			return $structure;
		}
		else
		{
			return $translation;
		}
	}
}