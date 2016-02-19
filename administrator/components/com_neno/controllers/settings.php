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
class NenoControllerSettings extends JControllerAdmin
{
	/**
	 * Save a setting
	 *
	 * @return void
	 */
	public function saveSetting()
	{
		$input = $this->input;

		$setting  = $input->getString('setting');
		$newValue = $input->getString('value');

		if ($setting == 'save_history')
		{
			if ($this->saveContentHistory($newValue))
			{
				echo 'ok';
			}
		}

		$error = false;

		// Saving component params
		if ($setting == 'save_history') {
			if (!$this->saveContentHistory($newValue)) {
				$error = true;
			}
		}

		// Check for errors
		if (!$error)
		{
			// Saving neno settings
			if (NenoSettings::set($setting, $newValue))
			{
				$ok = 'ok';
			}
		}

		JFactory::getApplication()->close();
	}

	/*
	 * Activate save history in component params
	 *
	 * @param   bool  $value  Value if saving or not
	 *
	 * @return  bool
	 */
	public function saveContentHistory($value)
	{
		/**
		 * TODO: save component params for ('save_history', $value)
		 */
		$model  = new ConfigModelComponent;
		$form = $model->getForm();


		/*
		// Validate the posted data.
		$return = $model->validate($form, $data);

		// Attempt to save the configuration.
		$data = array(
			'params' => $return,
			'id'     => $id,
			'option' => $option
		);

		if ($model->save($data))
		{
			return true;
		}*/

		return false;
	}
}
