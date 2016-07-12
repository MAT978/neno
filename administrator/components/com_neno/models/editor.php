<?php
/**
 * @package     Neno
 * @subpackage  Models
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/strings.php';

/**
 * NenoModelEditor class
 *
 * @since  1.0
 */
class NenoModelEditor extends NenoModelStrings
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array();
		}

		parent::__construct($config);
	}

	/**
	 * Consolidate Translation
	 *
	 * @param   int $translationId Translation id
	 *
	 * @return  bool True on success
	 */
	public function consolidateTranslations($translationId)
	{
		/* @var $translation NenoContentElementTranslation */
		$translation = NenoContentElementTranslation::load($translationId, false, true);

		if (!empty($translation))
		{
			$translationsToConsolidate = NenoContentElementTranslation::load(
			  array(
				'original_text' => $translation->getOriginalText(),
				'language'      => $translation->getLanguage(),
				'id'            => array(
				  '_field'     => 'id',
				  '_condition' => '<>',
				  '_value'     => $translation->getId()
				)
			  ),
			  false,
			  true
			);

			// Making sure that is an array
			if (!is_array($translationsToConsolidate))
			{
				$translationsToConsolidate = array($translationsToConsolidate);
			}

			/* @var $translationToConsolidate NenoContentElementTranslation */
			foreach ($translationsToConsolidate as $translationToConsolidate)
			{
				$translationToConsolidate
				  ->setString($translation->getString())
				  ->setState(NenoContentElementTranslation::TRANSLATED_STATE)
				  ->persist();

				NenoLog::log('Translation "' . $translationToConsolidate->getString() . '" consolidated successfully"', NenoLog::ACTION_TRANSLATION_CONSOLIDATED);
			}
		}

		return true;
	}

	/**
	 * Get the amount of translations that contains the same text
	 *
	 * @param   int    $translationId       Translation Id
	 * @param   string $translationLanguage Translation language
	 * @param   string $translationText     Translation Text
	 *
	 * @return int
	 */
	public function getSimilarTranslationsCounter($translationId, $translationLanguage, $translationText)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('COUNT(*)')
		  ->from('#__neno_content_element_translations')
		  ->where(
			array(
			  'original_text = ' . $db->quote($translationText),
			  'language = ' . $db->quote($translationLanguage),
			  'state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
			  'id <> ' . $translationId,
			)
		  );

		$db->setQuery($query);

		return (int) $db->loadResult();
	}
}
