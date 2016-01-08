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

require_once JPATH_COMPONENT_ADMINISTRATOR . '/controllers/strings.php';

/**
 * Manifest Editor controller class
 *
 * @since  1.0
 */
class NenoControllerEditor extends NenoControllerStrings
{
	/**
	 * Method to handle ajax call for google translation
	 *
	 * @return string
	 */
	public function translate()
	{
		$app             = JFactory::getApplication();
		$input           = $app->input;
		$text            = html_entity_decode($input->getRaw('text'));
		$workingLanguage = NenoHelper::getWorkingLanguage();
		$defaultLanguage = NenoSettings::get('source_language');
		$translator      = NenoSettings::get('translator');
		$result          = array();

		try
		{
			/* @var $nenoTranslate NenoTranslatorApi */
			$nenoTranslate = NenoTranslatorApi::getAdapter($translator);

			try
			{
				$result['text']   = $nenoTranslate->translate($text, $defaultLanguage, $workingLanguage);
				$result['status'] = 'ok';
			} catch (Exception $e)
			{
				$result['text']   = $text;
				$result['status'] = 'err';
				$result['error']  = $e->getMessage();
			}
		} catch (UnexpectedValueException $e)
		{
			$result['text']   = $text;
			$result['status'] = 'err';
			$result['error']  = $e->getMessage();
		}

		echo json_encode($result);

		$app->close();
	}

	/**
	 * Get a translations
	 *
	 * @return void
	 */
	public function getTranslation()
	{
		$input         = $this->input;
		$translationId = $input->getInt('id');

		if (!empty($translationId))
		{
			$translation = NenoContentElementTranslation::getTranslation($translationId, true);

			// Check in translation first
			NenoContentElementTranslation::checkInTranslations();

			// Check out current translation
			$translation->checkOut();

			echo JLayoutHelper::render('editor', $translation->prepareDataForView(true), JPATH_NENO_LAYOUTS);

			//Show related
			if (!empty($translation->related))
			{
				// Get the setting for load_related_content
				$loadRelatedContent = NenoSettings::get('load_related_content');
				echo JLayoutHelper::render('editorrelatedcontentheader', $loadRelatedContent, JPATH_NENO_LAYOUTS);

				if ($loadRelatedContent)
				{
					foreach ($translation->related as $related)
					{
						echo JLayoutHelper::render('editor', $related->prepareDataForView(true), JPATH_NENO_LAYOUTS);
					}
				}
			}
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Get a translations
	 *
	 * @return void
	 */
	public function getTranslationNotes()
	{
		$input         = $this->input;
		$translationId = $input->getInt('id');

		if (!empty($translationId))
		{
			$translation = NenoContentElementTranslation::getTranslation($translationId);
			echo JLayoutHelper::render('editornotetotranslator', $translation->prepareDataForView(true), JPATH_NENO_LAYOUTS);
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Save translation as draft
	 *
	 * @return void
	 */
	public function saveAsDraft()
	{
		$input           = $this->input;
		$translationId   = $input->getInt('id');
		$translationText = NenoHelper::cleanXssString($input->get('text', '', 'RAW'));

		if ($this->saveTranslation($translationId, $translationText, NenoContentElementTranslation::NOT_TRANSLATED_STATE))
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationId, false);

			echo json_encode($translation->prepareDataForView());
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Save translation into the database
	 *
	 * @param   int    $translationId   Translation ID
	 * @param   string $translationText Translation Text
	 * @param   int    $changeState     Translation status
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	protected function saveTranslation($translationId, $translationText, $changeState)
	{
		/* @var $translation NenoContentElementTranslation */
		$translation = NenoContentElementTranslation::load($translationId, false, true);

		if (!empty($translation))
		{
			$translation
				->setString($translationText)
				->setState($changeState)
				->addTranslationMethod(NenoContentElementTranslation::MANUAL_TRANSLATION_METHOD, 1);
		}
		else
		{
			throw new Exception('Error loading translation');
		}

		if ($changeState == NenoContentElementTranslation::TRANSLATED_STATE)
		{
			$translation->setTimeCompleted(new DateTime);
		}

		// Check if the translation can be saved
		if ($translation->canBeSaved())
		{
			$result = $translation->persist();
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Save translation as completed
	 *
	 * @return void
	 */
	public function saveAllAsCompleted()
	{
		// Get input and turn it into an array with objects
		$input       = $this->input;
		$stringsJson = $input->get('strings', '', 'RAW');
		$strings     = json_decode($stringsJson);

		// Create an array to hold info about the translations
		$messages = array();

		if (!empty($strings) && count($strings) > 0)
		{
			$checkedStrings = array();
			foreach ($strings as $string)
			{
				$string->text = NenoHelper::cleanXssString($string->text);
				// Save the translation
				if ($this->saveTranslation($string->translation_id, $string->text, NenoContentElementTranslation::TRANSLATED_STATE))
				{
					/* @var $translation NenoContentElementTranslation */
					$translation = NenoContentElementTranslation::load($string->translation_id, false);

					// Check for number of same translations to offer consolidation
					// Only check for one string once
					$counter      = 0;
					$originalText = $translation->getOriginalText();
					if (in_array(strtolower(trim($originalText)), $checkedStrings) === false)
					{
						$checkedStrings[] = strtolower(trim($originalText));
						$model            = $this->getModel();
						$counter          = $model->getSimilarTranslationsCounter($string->translation_id, $translation->getLanguage(), $originalText);
					}

					// If we found matches prepare data to return
					$message = array();
					if ($counter != 0)
					{
						$message['translation_id'] = $string->translation_id;
						$message['message']        = JLayoutHelper::render('consolidatelayout', array(
							'translationId' => $string->translation_id,
							'counter'       => $counter,
							'originalText'  => $originalText,
							'text'          => $string->text
						), JPATH_NENO_LAYOUTS);
						$message['counter']        = $counter;
					}

					if (!empty($message))
					{
						$messages[] = $message;
					}
				}
			}
		}

		// Echo any messages
		if (count($messages) > 0)
		{
			echo json_encode($messages);
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Get model
	 *
	 * @param   string $name   Model name
	 * @param   string $prefix Model prefix
	 * @param   array  $config Model configuration
	 *
	 * @return NenoModelEditor
	 */
	public function getModel($name = 'Editor', $prefix = 'NenoModel', $config = array())
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Consolidate translation
	 *
	 * @return void
	 */
	public function consolidateTranslations()
	{
		$input   = $this->input;
		$jsonIds = $input->post->get('ids', '', 'RAW');
		$ids     = json_decode($jsonIds);

		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$model = $this->getModel();
				$model->consolidateTranslations($id);
			}
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Save translator configuration (API key)
	 *
	 * @return void
	 */
	public function saveTranslatorConfig()
	{
		$input         = $this->input;
		$translator    = $input->post->getString('translator');
		$translatorKey = $input->post->getString('translatorKey');

		NenoSettings::set('translator', $translator);
		NenoSettings::set('translator_api_key', $translatorKey);
	}

	/**
	 * Set default translate action (Copy or Copy&Translate)
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function saveDefaultAction()
	{
		$input  = $this->input;
		$action = $input->get->getInt('action');
		NenoSettings::set('default_translate_action', $action);

		JFactory::getApplication()->close();
	}

	/**
	 * Toggle show related content setting
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function toggleShowRelated()
	{
		$currentState = NenoSettings::get('load_related_content');
		$newState     = 1 - $currentState;
		NenoSettings::set('load_related_content', $newState);

		JFactory::getApplication()->close();
	}

}
