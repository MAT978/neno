<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* No direct access */
defined('_JEXEC') or die;

/**
 * Neno helper.
 *
 * @since  1.0
 */
class NenoHelperLicense
{
	/**
	 * Get any error message pertaining to the license
	 *
	 * @return bool|string false on no errors or a string with an error
	 */
	public static function getLicenseWarning()
	{
		// Check that we have 4 parts
		$licenseParts = self::getLicenseData();

		if (count($licenseParts) != 4)
		{
			return JText::_('COM_NENO_ERROR_IN_LICENSE');
		}

		// Check domain match
		if (self::checkDomainMatch($licenseParts[2]) === false)
		{
			return JText::sprintf('COM_NENO_ERROR_IN_LICENSE_DOMAIN', $licenseParts[2]);
		}

		// Check expiration date
		if (strtotime($licenseParts[3]) < time())
		{
			return JText::_('COM_NENO_ERROR_IN_LICENSE_EXPIRED');
		}

		return false;
	}

	/**
	 * Get data out of the license
	 *
	 * @return array
	 */
	public static function getLicenseData()
	{
		$license = self::getLicense();

		return explode('|', base64_decode($license));
	}

	/**
	 * Get license
	 *
	 * @return string
	 */
	private static function getLicense()
	{
		return NenoSettings::get('license_code', '');
	}

	/**
	 * Check if the given domain name matches the current site
	 *
	 * @param   string $domain Domain
	 *
	 * @return boolean
	 */
	protected static function checkDomainMatch($domain)
	{
		if (mb_strpos(JUri::root(), $domain) === false
			&& mb_strpos(JUri::root(), 'localhost') === false
			&& mb_strpos(JUri::root(), '127.0.0.1') === false)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}
