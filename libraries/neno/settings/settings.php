<?php
/**
 * @package     Neno
 * @subpackage  Settings
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class to handle Neno settings
 *
 * @since  1.0
 */
class NenoSettings
{
	/**
	 * @var array
	 */
	private static $settings = NULL;

	/**
	 * Get the value of a particular property
	 *
	 * @param   mixed      $settingName Setting name
	 * @param   mixed|null $default     Default value in case the setting doesn't exist
	 *
	 * @return mixed
	 */
	public static function get($settingName, $default = NULL)
	{
		// If the settings haven't been loaded yet, let's load them
		if (self::$settings === NULL)
		{
			self::loadSettingsFromDb();
		}

		if (empty(self::$settings[$settingName]))
		{
			self::$settings[$settingName]['value'] = $default;
			static::createSetting($settingName, $default);
		}

		// If the setting doesn't exists, let's return the default value.
		return self::$settings[$settingName]['value'];
	}

	/**
	 * Load settings from the database
	 *
	 * @return void
	 */
	private static function loadSettingsFromDb()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query
		  ->select('*')
		  ->from('#__neno_settings');

		$db->setQuery($query);
		$settings = $db->loadObjectList();

		self::$settings = array();

		foreach ($settings as $setting)
		{
			self::$settings[$setting->setting_key] = array(
			  'value'     => $setting->setting_value,
			  'read_only' => $setting->read_only
			);
		}
	}

	/**
	 * Set the value of a particular property. It will be created if it does not exist before
	 *
	 * @param   mixed   $settingName  Setting name
	 * @param   mixed   $settingValue Setting value
	 * @param   boolean $readOnly     If it should be marked as read only
	 *
	 * @return bool
	 */
	public static function set($settingName, $settingValue, $readOnly = false)
	{
		$refresh = false;

		if (empty(self::$settings[$settingName]))
		{
			self::$settings[$settingName] = array(
			  'value'     => $settingValue,
			  'read_only' => $readOnly
			);

			static::createSetting($settingName, $settingValue, $readOnly);
		}
		else
		{
			if (self::$settings[$settingName]['read_only'] != 1)
			{
				self::$settings[$settingName]['value']     = $settingValue;
				self::$settings[$settingName]['read_only'] = $readOnly;

				if ($settingValue === NULL)
				{
					$db    = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query
					  ->delete('#__neno_settings')
					  ->where('setting_key = ' . $db->quote($settingName));
					$db->setQuery($query);
					$db->execute();
					self::loadSettingsFromDb();
				}
				else
				{
					$refresh = true;
				}
			}
		}

		if ($refresh)
		{
			return self::saveSettingsToDb($settingName);
		}

		return false;
	}

	/**
	 * Save the settings into the database
	 *
	 * @param   string $setting Setting name
	 *
	 * @return bool
	 */
	private static function saveSettingsToDb($setting = NULL)
	{
		$db = JFactory::getDbo();

		/* @var $query NenoDatabaseQueryMysqlx */
		$query = $db->getQuery(true);

		if ($setting === NULL)
		{
			$query
			  ->replace('#__neno_settings')
			  ->columns(
				array(
				  'setting_key',
				  'setting_value',
				  'read_only'
				)
			  );

			foreach (self::$settings as $settingName => $settingData)
			{
				$query->values($db->quote($settingName) . ',' . $db->quote($settingData['value']) . ',' . $db->quote($settingData['read_only']));
			}
		}
		else
		{
			$query
			  ->update('#__neno_settings')
			  ->set('setting_value = ' . $db->quote(self::$settings[$setting]['value']))
			  ->where('setting_key = ' . $db->quote($setting));
		}

		$db->setQuery($query);

		return $db->execute() !== false;
	}

	/**
	 * Create setting in case it does not exist
	 *
	 * @param string $settingName  Setting name
	 * @param string $settingValue Setting value
	 * @param bool   $readOnly     If it's read only or not
	 *
	 * @return bool
	 */
	protected static function createSetting($settingName, $settingValue, $readOnly = false)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->insert('#__neno_settings')
		  ->columns(
			array(
			  'setting_key',
			  'setting_value',
			  'read_only'
			)
		  )
		  ->values($db->quote($settingName) . ',' . $db->quote($settingValue) . ',' . $db->quote($readOnly));

		$db->setQuery($query);

		return $db->execute() !== false;
	}

	/**
	 * Get all the settings keys
	 *
	 * @return array
	 */
	public static function getSettingsKeys()
	{
		if (self::$settings === NULL)
		{
			self::loadSettingsFromDb();
		}

		return array_keys(self::$settings);
	}
}
