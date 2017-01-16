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
	 * Save a settings
	 *
	 * @return void
	 *
	 * @since 2.1.32
	 */
	public function save()
	{
		$input = $this->input;
		$app   = JFactory::getApplication();

		$jform = $input->post->get('jform', array(), 'ARRAY');

		/* @var $model NenoModelSettings */
		$model = JModelLegacy::getInstance('Settings', 'NenoModel');

		if ($model->save($jform))
		{
			$app->enqueueMessage(JText::_('COM_NENO_SETTINGS_SAVE_SUCCESS'), 'success');
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_NENO_SETTINGS_SAVE_ERROR'), 'error');
		}

		$app->redirect(JRoute::_('index.php?option=com_neno&view=settings', false));
	}
}
