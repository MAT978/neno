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
 * Manifest Strings controller class
 *
 * @since  1.0
 */
class NenoControllerJobs extends JControllerAdmin
{
	/**
	 * Resend job
	 *
	 * @return void
	 */
	public function resend()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$jobId = $input->getInt('jobId');

		/* @var $job NenoJob */
		$job = NenoJob::load($jobId, false, true);

		if (!empty($job))
		{
			if ($job->sendJob())
			{
				$app->enqueueMessage(JText::sprintf('COM_NENO_JOBS_JOB_SENT_SUCCESS', $job->getId()));
			}
			else
			{
				$app->enqueueMessage(JText::sprintf('COM_NENO_JOBS_JOB_SENT_ERROR', $job->getId()), 'error');
			}
		}

		$app->redirect('index.php?option=com_neno&view=jobs');
	}

	/**
	 * Fetch job file from server
	 *
	 * @return void
	 */
	public function fetch()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$jobId = $input->getInt('jobId');

		/* @var $job NenoJob */
		$job = NenoJob::load($jobId, false, true);

		if ($job->fetchJobFromServer() === true)
		{
			if ($job->processJobFinished() === true)
			{
				$job
					->setState(NenoJob::JOB_STATE_PROCESSED)
					->persist();

				$app->enqueueMessage(JText::sprintf('COM_NENO_JOBS_JOB_PROCESSED_SUCCESS', $job->getId()));
			}
			else
			{
				$app->enqueueMessage(JText::_('COM_NENO_JOBS_JOB_PROCESSED_ERROR_READING_FILE'), 'error');
			}
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_NENO_JOBS_JOB_PROCESSED_ERROR_FETCHING_FILE'), 'error');
		}

		$app->redirect('index.php?option=com_neno&view=jobs');
	}

	public function delete()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$jobId = $input->getInt('jobId');

		/* @var $job NenoJob */
		$job = NenoJob::load($jobId, false, true);

		if (in_array($job->getStatus(), array(NenoJob::JOB_STATE_NO_TC, NenoJob::JOB_STATE_GENERATED, NenoJob::JOB_STATE_NOT_READY)))
		{
			if ($job->remove())
			{
				$app->enqueueMessage(JText::sprintf('COM_NENO_JOBS_JOB_DELETED_SUCCESS', $job->getId()));
			}
			else
			{
				$app->enqueueMessage(JText::_('COM_NENO_JOBS_JOB_DELETED_ERROR_GENERAL'), 'error');
			}
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_NENO_JOBS_JOB_DELETED_ERROR_STATUS'), 'error');
		}

		$app->redirect('index.php?option=com_neno&view=jobs');
	}
}
