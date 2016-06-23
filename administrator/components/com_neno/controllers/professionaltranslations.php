<?php
/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Manifest Groups & Elements controller class
 *
 * @since  1.0
 */
class NenoControllerProfessionalTranslations extends JControllerAdmin
{
	/**
	 * Task to set value for automatic translations
	 *
	 * @return void
	 */
	public function setAutomaticTranslationSetting()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$setting = $input->post->getString('setting');
		$value   = $input->post->getInt('value');

		if (!empty($setting))
		{
			echo NenoSettings::set($setting, $value) ? 'ok' : 'err';
		}
		else
		{
			echo 'err';
		}

		$app->close();
	}

	/**
	 * This task will create a job
	 *
	 * @return void
	 */
	public function createJob()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$type     = $input->post->getString('type');
		$language = $input->post->getString('language');

		if (!empty($type) && !empty($language))
		{
			$job = NenoJob::createJob($language, $type);
			if ($job->sendJob())
			{
				echo 'ok';
			}
			else
			{
				if ($job->getStatus() === NenoJob::JOB_STATE_NO_TC)
				{
					echo 'no_tc';

					$job->remove();
				}
			}
		}
		else
		{
			echo 'err';
		}

		$app->close();
	}
}
