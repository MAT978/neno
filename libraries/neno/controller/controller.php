<?php
/**
 * @package     Neno
 * @subpackage  Controller
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Class NenoController
 *
 * @since  1.0
 */
class NenoController extends JControllerLegacy
{
	/**
	 * Process task queue
	 *
	 * @return void
	 */
	public function processTaskQueue()
	{
		NenoTaskMonitor::runTask(1);
		JFactory::getApplication()->close();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   boolean $cachable  If Joomla should cache the response
	 * @param   array   $urlParams URL parameters
	 *
	 * @return JController
	 */
	public function display($cachable = false, $urlParams = array())
	{
		$input = $this->input;
		$view  = $input->getCmd('view', 'dashboard');
		$app   = JFactory::getApplication();

		if (NenoSettings::get('installation_completed') != 1
		  && $view != 'installation' && $view != 'debug' && $app->isAdmin()
		)
		{
			if ($view != 'dashboard')
			{
				$app->enqueueMessage(JText::_('COM_NENO_INSTALLATION_ERROR'), 'error');
			}

			$app->redirect('index.php?option=com_neno&view=installation');
		}

		$input->set('view', $view);

		// Ensure that a working language is set for some views
		$viewsThatRequireWorkingLanguage = array(
		  'groupselements',
		  'editor',
		  'strings'
		);

		$showLanguagesDropDown = false;

		if (in_array($view, $viewsThatRequireWorkingLanguage))
		{
			// Get working language
			$workingLanguage       = NenoHelper::getWorkingLanguage();
			$languages             = JFactory::getLanguage()
			  ->getKnownLanguages();
			$showLanguagesDropDown = true;

			if (empty($workingLanguage) || !in_array($workingLanguage, array_keys($languages)))
			{
				$url = JRoute::_('index.php?option=com_neno&view=setworkinglang&next=' . $view . '&r=' . NenoHelperBackend::generateRandomString(), false);
				$this->setRedirect($url);
				$this->redirect();
			}
		}

		NenoHelperBackend::setAdminTitle($showLanguagesDropDown);

		parent::display($cachable, $urlParams);

		return $this;
	}

	/**
	 * Check if the user has lost the session
	 *
	 * @return void
	 */
	public function checkSession()
	{
		if (!JFactory::getUser()->guest)
		{
			echo 'ok';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Set working language
	 *
	 * @return void
	 */
	public function setWorkingLang()
	{
		$lang = JFactory::getApplication()->input->getString('lang', '');
		$next = JFactory::getApplication()->input->getString('next', 'dashboard');

		NenoHelper::setWorkingLanguage($lang);

		$url = JRoute::_('index.php?option=com_neno&view=' . $next . '&r=' . NenoHelperBackend::generateRandomString(), false);
		$this->setRedirect($url);
		$this->redirect();
	}

	/**
	 * Set a translation as ready
	 *
	 * @return void
	 */
	public function translationReady()
	{
		$input = $this->input;
		$jobId = $input->get->getString('jobId');

		/* @var $job NenoJob */
		$job = NenoJob::load($jobId);

		if ($job === NULL)
		{
			NenoLog::add('Job not found. Job Id:' . $jobId, NenoLog::PRIORITY_ERROR);
		}
		else
		{
			// Set the job as completed by the server but the component hasn't processed it yet.
			$job
			  ->setState(NenoJob::JOB_STATE_COMPLETED)
			  ->persist();

			// Create task into the queue
			NenoTaskMonitor::addTask('job_fetcher');

			echo 'ok';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Fix Language issue
	 *
	 * @return void
	 */
	public function fixLanguageIssue()
	{
		$input    = $this->input;
		$language = $input->post->getString('language');
		$issue    = $input->post->getCmd('issue');

		if (NenoHelper::fixLanguageIssues($language, $issue) === true)
		{
			echo 'ok';
		}
		else
		{
			echo 'err';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Show languages modal content
	 *
	 * @return void
	 */
	public function showInstallLanguagesModal()
	{
		$languages = NenoHelper::findLanguages();
		$placement = $this->input->getString('placement', 'dashboard');

		if (!empty($languages))
		{
			$displayData            = new stdClass;
			$displayData->languages = $languages;
			$displayData->placement = $placement;
			echo JLayoutHelper::render('installlanguages', $displayData, JPATH_NENO_LAYOUTS);
		}
		else
		{
			echo JText::_('COM_NENO_INSTALL_LANGUAGES_NO_LANGUAGES_TO_INSTALL');
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Install language
	 *
	 * @return void
	 */
	public function installLanguage()
	{
		$input     = $this->input;
		$updateId  = $input->post->getInt('update');
		$language  = $input->post->getString('language');
		$placement = $input->post->getCmd('placement');

		if (NenoHelper::installLanguage($updateId, $placement != 'dashboard'))
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->select(
				array(
				  'l.lang_code',
				  'l.published',
				  'l.title',
				  'l.image',
				  'tr.state',
				  'SUM(tr.word_counter) AS word_count'
				)
			  )
			  ->from('#__languages AS l')
			  ->leftJoin('#__neno_content_element_translations AS tr ON tr.language = l.lang_code')
			  ->where('l.lang_code = ' . $db->quote($language))
			  ->group(
				array(
				  'l.lang_code',
				  'tr.state'
				)
			  )
			  ->order('lang_code');

			$db->setQuery($query);
			$languages = $db->loadObjectListMultiIndex('lang_code');
			$item      = new stdClass;

			if (!empty($languages))
			{
				foreach ($languages as $language)
				{
					$item->lang_code          = $language[0]->lang_code;
					$item->published          = $language[0]->published;
					$item->title              = $language[0]->title;
					$item->image              = $language[0]->image;
					$item->errors             = NenoHelper::getLanguageErrors((array) $language[0]);
					$item->translationMethods = NenoHelper::getLanguageDefault($item->lang_code);

					// If the language was installed from the dashboard, let's add a task to set all the shadow tables structure
					if ($placement == 'dashboard')
					{
						// Add task to
						NenoTaskMonitor::addTask('language', array('language' => $item->lang_code));

						// Create menu structure for this language
						NenoHelper::createMenuStructureForLanguage($item->lang_code);
					}

					$item->isInstalled = NenoHelper::isCompletelyInstall($language[0]->lang_code);
					$item->placement   = $placement;
					$item              = NenoHelper::getLanguageStats($language, $item);
				}

				echo JLayoutHelper::render('languageconfiguration', get_object_vars($item), JPATH_NENO_LAYOUTS);
			}
			else
			{
				echo 'err';
			}
		}
		else
		{
			echo 'err';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Install language
	 *
	 * @return void
	 */
	public function removeLanguage()
	{
		$input    = $this->input;
		$language = $input->getString('language');

		if (NenoHelper::deleteLanguage($language))
		{
			echo 'ok';
		}
		else
		{
			echo 'err';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Get translation method selector
	 *
	 * @return void
	 */
	public function getTranslationMethodSelector()
	{
		$input              = $this->input;
		$n                  = $input->getInt('n', 0);
		$selectedMethods    = $input->get('selected_methods', array(), 'ARRAY');
		$placement          = $input->getString('placement', 'general');
		$translationMethods = NenoHelper::loadTranslationMethods();
		$app                = JFactory::getApplication();

		// Ensure that we know what was selected for the previous selector
		if (($n > 0 && !isset($selectedMethods[$n - 1])) || ($n > 0 && $selectedMethods[$n - 1] == 0))
		{
			$app->close();
		}

		// As a safety measure prevent more than 5 selectors and always allow only one more selector than already selected
		if ($n > 4 || $n > count($selectedMethods) + 1)
		{
			$app->close();
		}

		// Reduce the translation methods offered depending on the parents
		if ($n > 0 && !empty($selectedMethods))
		{
			$parentMethod                = $selectedMethods[$n - 1];
			$acceptableFollowUpMethodIds = $translationMethods[$parentMethod]->acceptable_follow_up_method_ids;
			$acceptableFollowUpMethods   = explode(',', $acceptableFollowUpMethodIds);

			foreach ($translationMethods as $k => $translationMethod)
			{
				if (!in_array($k, $acceptableFollowUpMethods))
				{
					unset($translationMethods[$k]);
				}
			}
		}

		// If there are no translation methods left then return nothing
		if (!count($translationMethods))
		{
			JFactory::getApplication()->close();
		}

		// Prepare display data
		$displayData                        = array();
		$displayData['translation_methods'] = $translationMethods;
		$displayData['n']                   = $n;

		if ($placement == 'general')
		{
			$displayData['assigned_translation_methods'] = NenoHelper::getTranslationMethods('dropdown');
		}
		else
		{
			$lang                                        = $input->getString('language');
			$displayData['assigned_translation_methods'] = NenoHelper::getLanguageDefault($lang, $n);
		}

		$selectorHTML = JLayoutHelper::render('translationmethodselector', $displayData, JPATH_NENO_LAYOUTS);

		echo $selectorHTML;

		$app->close();
	}

	/**
	 * Save translation method
	 *
	 * @return void
	 */
	public function saveTranslationMethod()
	{
		$input             = $this->input;
		$language          = $input->getString('language');
		$translationMethod = $input->getInt('translationMethod');
		$ordering          = $input->getInt('ordering');
		$applyToElements   = $input->getInt('applyToElements');

		if (!empty($language))
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->delete('#__neno_content_language_defaults')
			  ->where(
				array(
				  'lang = ' . $db->quote($language),
				  'ordering >= ' . $ordering
				)
			  );

			$db->setQuery($query);
			$db->execute();

			$query
			  ->clear()
			  ->insert('#__neno_content_language_defaults')
			  ->columns(
				array(
				  'lang',
				  'translation_method_id',
				  'ordering'
				)
			  )
			  ->values($db->quote($language) . ',' . $translationMethod . ',' . $ordering);
			$db->setQuery($query);
			$db->execute();

			if ($applyToElements)
			{
				// Deleting translation methods for groups
				$query
				  ->clear()
				  ->delete('#__neno_content_element_groups_x_translation_methods')
				  ->where(
					array(
					  'lang = ' . $db->quote($language),
					  'ordering >= ' . $db->quote($ordering)
					)
				  );

				$db->setQuery($query);
				$db->execute();

				// Delete translation methods for translations
				$query
				  ->clear()
				  ->delete('#__neno_content_element_translation_x_translation_methods')
				  ->where(
					array(
					  'translation_id IN (SELECT id FROM #__neno_content_element_translations WHERE language = ' . $db->quote($language) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE . ')',
					  'ordering >= ' . $db->quote($ordering)
					)
				  );

				$db->setQuery($query);
				$db->execute();

				// Inserting translation methods for groups
				$query = 'INSERT INTO #__neno_content_element_groups_x_translation_methods (group_id, lang, translation_method_id, ordering)
							SELECT id, ' . $db->quote($language) . ', ' . $db->quote($translationMethod) . ',' . $db->quote($ordering) . ' FROM #__neno_content_element_groups';

				$db->setQuery($query);
				$db->execute();

				// Inserting translation methods for translations
				$query = 'INSERT INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering)
							SELECT id, ' . $db->quote($translationMethod) . ',' . $db->quote($ordering) . ' FROM #__neno_content_element_translations
							WHERE language = ' . $db->quote($language) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE;

				$db->setQuery($query);
				$db->execute();
			}

			JFactory::getApplication()->close();
		}
	}

	/**
	 * Check if a particular language has been installed
	 *
	 * @return void
	 */
	public function isLanguageInstalled()
	{
		$input    = $this->input;
		$language = $input->post->getString('language');

		if (!empty($language))
		{
			if (NenoHelper::isCompletelyInstall($language))
			{
				echo 'ok';
			}
			else
			{
				echo 'err';
			}
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Save external translators comment for a particular language
	 *
	 * @param string $language Language
	 * @param string $comment  Translator comment
	 *
	 * @return bool
	 */
	protected function saveExternalTranslatorsCommentForLanguage($language, $comment)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
		  ->select('*')
		  ->from('#__neno_language_external_translators_comments')
		  ->where('language = ' . $db->quote($language));
		$db->setQuery($query);

		$languageComment = $db->loadObject();

		if (empty($languageComment))
		{
			$languageComment           = new stdClass;
			$languageComment->language = $language;
		}

		$languageComment->comment = $comment;

		if (empty($languageComment->id))
		{
			$db->insertObject('#__neno_language_external_translators_comments', $languageComment, 'id');
		}
		else
		{
			$db->updateObject('#__neno_language_external_translators_comments', $languageComment, 'id');
		}

		return true;
	}

	/**
	 * Save translators comment for a particular string
	 *
	 * @param int    $translationId   Translation id
	 * @param bool   $allTranslations Saving the same comment for all the translation for the same source element
	 * @param int    $contentId       Source element id
	 * @param string $comment         Translator comment
	 *
	 * @return bool
	 */
	protected function saveExternalTranslatorsCommentForString($translationId, $allTranslations, $allLanguages, $contentId, $comment)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		/* @var $translation NenoContentElementTranslation */
		$translation = NenoContentElementTranslation::load($translationId, false, true);

		$result = $translation
		  ->setComment($comment)
		  ->persist();

		if ($allLanguages)
		{
			if (!empty($contentId))
			{
				$query
				  ->select('original_text')
				  ->from('#__neno_content_element_translations')
				  ->where($db->quoteName('id') . ' = ' . (int) $translationId);

				$db->setQuery($query);
				$original_text = $db->loadResult();
				$query->clear();

				$query
				  ->update('#__neno_content_element_translations')
				  ->set('comment = ' . $db->quote($comment))
				  ->where(
					array(
					  'content_id = ' . $db->quote($contentId),
					  'original_text = ' . $db->quote($original_text)
					)
				  );

				$db->setQuery($query);
				$db->execute();
			}
		}

		if ($allTranslations)
		{
			if (!empty($contentId))
			{
				$query
				  ->update('#__neno_content_element_translations')
				  ->set('comment = ' . $db->quote($comment))
				  ->where(
					array(
					  'content_id = ' . $db->quote($contentId),
					  'content_type = ' . $db->quote($translation->getContentType()),
					  'language = ' . $db->quote($translation->getLanguage())
					)
				  );

				$db->setQuery($query);
				$db->execute();

				$query->clear();

				if ($translation->getContentType() == NenoContentElementTranslation::DB_STRING)
				{
					$query->update('#__neno_content_element_fields');
				}
				else
				{
					$query->update('#__neno_content_element_language_strings');
				}

				// Saving this comment for the future
				$query
				  ->set('comment = ' . $db->quote($comment))
				  ->where('id = ' . $db->quote($contentId));

				$db->setQuery($query);
				$db->execute();
			}
		}

		return $result;
	}

	public function saveExternalTranslatorsComment()
	{
		$input     = $this->input;
		$placement = $input->post->getString('placement');
		$comment   = $input->post->getHtml('comment', '');
		$result    = false;

		switch ($placement)
		{
			case 'general':
				NenoSettings::set('external_translators_notes', $comment);
				$result = true;
				break;
			case 'language':
				$language = $input->post->getString('language');
				$result   = $this->saveExternalTranslatorsCommentForLanguage($language, $comment);
				break;
			case 'string':
				$translationId   = $input->post->getInt('stringId');
				$allTranslations = $input->post->getBool('alltranslations', false);
				$allLanguages    = $input->post->getBool('allLangs', false);
				$contentId       = $input->post->getInt('contentId');
				$result          = $this->saveExternalTranslatorsCommentForString($translationId, $allTranslations, $allLanguages, $contentId, $comment);

				break;
		}

		echo ($result) ? 'ok' : 'err';

		JFactory::getApplication()->close();
	}

	public function getDiscoverMessage()
	{
		$language = NenoHelperBackend::getLanguageBeingInstalled();

		if ($language !== false && NenoHelper::isInstallationCompleted())
		{
			$layoutData                    = new stdClass;
			$layoutData->cronMode          = NenoSettings::get('schedule_task_option');
			$layoutData->tableToBeDiscover = NenoHelperBackend::getTablesCountToBeInstalledByLanguage($language);
			$layoutData->tableDiscovered   = NenoHelperBackend::getTablesThatHasBeenProcessAlready($language);
			$layoutData->tablesRemain      = $layoutData->tableToBeDiscover - $layoutData->tableDiscovered;
			echo JLayoutHelper::render('discoveralert', $layoutData, JPATH_NENO_LAYOUTS);
		}
		else
		{
			echo '';
		}


		JFactory::getApplication()->close();
	}
}
