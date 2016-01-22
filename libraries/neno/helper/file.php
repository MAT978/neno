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

class NenoHelperFile
{
	/**
	 * Search for a string into a language file based on its constant
	 *
	 * @param $languageFile
	 * @param $constant
	 *
	 * @return bool
	 */
	public static function existsStringInsideOfLanguageFile($languageFile, $constant)
	{
		if (file_exists($languageFile))
		{
			$strings = self::readLanguageFile($languageFile);

			return isset($strings[ $constant ]) ? $strings[ $constant ] : false;
		}

		return false;
	}

	/**
	 * Read language file
	 *
	 * @param string $filename
	 *
	 * @return array
	 */
	public static function readLanguageFile($filename)
	{
		return self::unifiedLanguageStrings(parse_ini_file($filename));
	}

	/**
	 * Unified language strings
	 *
	 * @param array $strings language strings
	 * @param bool  $read    which are the source of those strings
	 *
	 * @return array
	 */
	public static function unifiedLanguageStrings($strings, $read = true)
	{
		if ($read)
		{
			$strings = self::unifyLanguageStringsRead($strings);
		}
		else
		{
			$strings = self::unifyLanguageStringsWrite($strings);
		}

		return $strings;
	}

	/**
	 * Unify strings from a ini file
	 *
	 * @param array $strings Strings
	 *
	 * @return array
	 */
	protected static function unifyLanguageStringsRead($strings)
	{
		foreach ($strings as $key => $string)
		{
			$strings[ $key ] = str_replace('"_QQ_"', '"', $string);
		}

		return $strings;
	}

	/**
	 * Unify strings to a ini file
	 *
	 * @param array $strings Strings
	 *
	 * @return array
	 */
	protected static function unifyLanguageStringsWrite($strings)
	{
		foreach ($strings as $key => $string)
		{
			$strings[ $key ] = trim(str_replace('"', '"_QQ_"', $string));
		}

		return $strings;
	}

	/**
	 * Save INI file
	 *
	 * @param   string $filename filename
	 * @param   array  $strings  Strings to save
	 *
	 * @return bool
	 */
	public static function saveIniFile($filename, array $strings)
	{
		$res = array();

		// Unify strings
		$strings = self::unifiedLanguageStrings($strings, false);

		foreach ($strings as $key => $val)
		{
			if (is_array($val))
			{
				$res[] = "[$key]";

				foreach ($val as $stringKey => $stringValue)
				{
					$res[] = "$stringKey = " . (is_numeric($stringValue) ? $stringValue : '"' . $stringValue . '"');
				}
			}
			else
			{
				$res[] = "$key = " . (is_numeric($val) ? $val : '"' . $val . '"');
			}
		}

		if ($fp = fopen($filename, 'w'))
		{
			$startTime = microtime(true);
			$canWrite  = flock($fp, LOCK_EX);

			while ((!$canWrite) && ((microtime(true) - $startTime) < 5))
			{
				// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
				if (!$canWrite)
				{
					usleep(round(rand(0, 100) * 1000));
				}

				$canWrite = flock($fp, LOCK_EX);
			}

			// File was locked so now we can store information
			if ($canWrite)
			{
				fwrite($fp, implode("\r\n", $res));
				flock($fp, LOCK_UN);
			}

			fclose($fp);

			return true;
		}

		return false;
	}
}
