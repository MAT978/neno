<?php
/**
 * @package     Neno
 * @subpackage  ContentElement
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoContentElementMetadata
 *
 * @since  1.0
 */
class NenoContentElementTranslation extends NenoContentElement
{
	/**
	 * Language string (typically from language file)
	 */
	const LANG_STRING = 'lang_string';
	/**
	 * String from the database
	 */
	const DB_STRING = 'db_string';
	/**
	 * Machine translation method
	 */
	const MACHINE_TRANSLATION_METHOD = '2';
	/**
	 * Manual translation method
	 */
	const MANUAL_TRANSLATION_METHOD = '1';
	/**
	 * Professional translation method
	 */
	const PROFESSIONAL_TRANSLATION_METHOD = '3';
	/**
	 * This state is for a string that has been translated
	 */
	const TRANSLATED_STATE = 1;
	/**
	 * This state is for a string that has been sent to be translated but the translation has not arrived yet.
	 */
	const QUEUED_FOR_BEING_TRANSLATED_STATE = 2;
	/**
	 * This state is for a string that its source string has changed.
	 */
	const SOURCE_CHANGED_STATE = 3;
	/**
	 * This state is for a string that has not been translated yet or the user does not want to translated it
	 */
	const NOT_TRANSLATED_STATE = 4;
	/**
	 * @var array
	 */
	public $sourceElementData;
	/**
	 * @var integer
	 */
	public $charactersCounter;
	/**
	 * @var array
	 */
	public $translationMethods;
	/**
	 * @var string
	 */
	protected $originalText;
	/**
	 * @var string
	 */
	protected $contentType;
	/**
	 * @var NenoContentElement
	 */
	protected $element;
	/**
	 * @var integer
	 */
	protected $contentId;
	/**
	 * @var string
	 */
	protected $language;
	/**
	 * @var integer
	 */
	protected $state;
	/**
	 * @var string
	 */
	protected $string;
	/**
	 * @var DateTime
	 */
	protected $timeAdded;
	/**
	 * @var DateTime
	 */
	protected $timeRequested;
	/**
	 * @var Datetime
	 */
	protected $timeChanged;
	/**
	 * @var DateTime
	 */
	protected $timeCompleted;
	/**
	 * @var int
	 */
	protected $wordCounter;
	/**
	 * @var string
	 */
	protected $comment;
	/**
	 * @var int
	 */
	protected $checkedOut;
	/**
	 * @var DateTime
	 */
	protected $checkedOutTime;
	/**
	 * @var array
	 */
	public $related;

	/**
	 * @var boolean
	 * @since 2.1.15
	 */
	public $deleted;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Element data
	 * @param   bool  $loadExtraData Load extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);

		$data = new JObject($data);

		if ($data->get('content') !== null)
		{
			$this->element = $data->get('content');
		}
		elseif ($loadParent)
		{
			$contentId = $data->get('content_id') === null ? $data->get('contentId') : $data->get('content_id');

			if (!empty($contentId))
			{
				// If it's a language string, let's create a NenoContentElementLangstring
				if ($this->contentType == self::LANG_STRING)
				{
					$this->element = NenoContentElementLanguageString::load($contentId, $loadExtraData, $loadParent);
				}
				else
				{
					$this->element = NenoContentElementField::load($contentId, $loadExtraData, $loadParent);
				}
			}
		}

		$this->charactersCounter = mb_strlen($this->getString());

		if (!$this->isNew())
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select('tm.*')
				->from('#__neno_translation_methods AS tm')
				->innerJoin('#__neno_content_element_translation_x_translation_methods AS tr_x_tm ON tr_x_tm.translation_method_id = tm.id')
				->where('tr_x_tm.translation_id = ' . (int) $this->id);

			$db->setQuery($query);
			$this->translationMethods = $db->loadObjectList();

			// Let's check the status of the translation
			$contentId = $data->get('content_id') === null ? $data->get('contentId') : $data->get('content_id');
			list(, $tableName) = NenoHelper::getOriginalText($contentId);
			$whereValues   = NenoHelper::getWhereValuesForTranslation($this->getId());
			$record        = NenoHelper::getRecordContentFromTranslationData($tableName, $whereValues);
			$this->deleted = empty($record);
		}
	}

	/**
	 * Get the string of the translation
	 *
	 * @return string
	 */
	public function getString()
	{
		return $this->string;
	}

	/**
	 * Set the string
	 *
	 * @param   string $string String
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setString($string)
	{
		$this->string = $string;

		return $this;
	}

	/**
	 * Load Translation by ID
	 *
	 * @param   integer $translationId          Tran
	 * @param boolean   $getRelatedTranslations Weather or not related translations should be loaded
	 *
	 * @return NenoContentElementTranslation
	 */
	public static function getTranslation($translationId, $getRelatedTranslations = false)
	{
		$translation = self::load($translationId);

		if ($getRelatedTranslations === true)
		{
			$translation->related = self::getRelatedTranslations($translationId);
		}

		return $translation;
	}

	/**
	 * Load translations content that is related to the given id
	 * - For a database field it will load the translations for all fields that should be translated
	 * - For a language file entry, it will load the following 10 rows
	 *
	 * @param integer $translationId
	 *
	 * @return array
	 */
	protected static function getRelatedTranslations($translationId)
	{
		$relatedTranslations = array();
		$translation         = self::load($translationId);

		if ($translation->getContentType() == 'db_string')
		{
			$relatedIds = self::getRelatedDbTranslationIds($translationId);
		}
		else
		{
			if ($translation->getContentType() == 'lang_string')
			{
				$relatedIds = self::getRelatedFileTranslationIds($translationId, $translation->getLanguage(), 20);
			}
		}
		if (!empty($relatedIds))
		{
			foreach ($relatedIds as $relatedId)
			{
				$relatedTranslations[] = self::getTranslation($relatedId);
			}
		}

		// Remove related translations where source is empty if that is the setting
		$hideEmptyStrings = NenoSettings::get('hide_empty_strings');
		if ($hideEmptyStrings)
		{
			/* @var $relatedTranslation NenoContentElementTranslation */
			foreach ($relatedTranslations as $key => $relatedTranslation)
			{
				$originalText = $relatedTranslation->getOriginalText();
				if (empty($originalText))
				{
					unset($relatedTranslations[$key]);
				}
			}
		}

		return $relatedTranslations;

	}

	/**
	 * Find id's of database translations that are related to the given translation id
	 *
	 * @param integer $translationId
	 *
	 * @return array
	 */
	public static function getRelatedDbTranslationIds($translationId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select(
				array(
					'ft.field_id',
					'ft.value',
				)
			)
			->from('`#__neno_content_element_fields_x_translations` AS ft')
			->where('ft.translation_id = ' . $translationId);

		$db->setQuery($query);
		$whereValues = $db->loadAssocList();

		$query->clear();

		$query
			->select('a2.translation_id')
			->from('#__neno_content_element_fields_x_translations AS a2')
			->where(
				array(
					'a2.field_id = ' . $db->quote($whereValues[0]['field_id']),
					'a2.value = ' . $db->quote($whereValues[0]['value']),
					'a2.translation_id <> ' . (int) $translationId
				)
			);

		$whereValuesCount = count($whereValues);
		for ($key = 1; $key < $whereValuesCount; $key++)
		{
			$subquery = clone $query;
			$query
				->clear()
				->select('a' . ($key + 2) . '.translation_id')
				->from('#__neno_content_element_fields_x_translations AS a' . ($key + 2))
				->where(
					array(
						'a' . ($key + 2) . '.field_id = ' . $db->quote($whereValues[$key]['field_id']),
						'a' . ($key + 2) . '.value = ' . $db->quote($whereValues[$key]['value']),
						'a' . ($key + 2) . '.translation_id IN (' . (string) $subquery . ')'
					)
				);
		}

		$subquery = clone $query;
		$query
			->clear()
			->select('a' . (count($whereValues) + 2) . '.translation_id')
			->from('(' . (string) $subquery . ') AS a' . (count($whereValues) + 2))
			->innerJoin('#__neno_content_element_translations AS t ON t.id = a' . (count($whereValues) + 2) . '.translation_id')
			->innerJoin('#__neno_content_element_fields AS f ON f.id = t.content_id')
			->where(
				array(
					't.language = (SELECT language FROM #__neno_content_element_translations WHERE id =  ' . $db->quote($translationId) . ')',
					'f.translate = 1'
				)
			);

		$db->setQuery($query);
		$translations = array_keys($db->loadAssocList('translation_id'));

		return $translations;
	}

	/**
	 * Setter for original text
	 *
	 * @param   string $string
	 */
	public function setOriginalText($string)
	{
		$this->originalText = $string;
	}

	/**
	 * Find related language strings
	 *
	 * @param int $translationId
	 * @param int $limit
	 *
	 * @return array with content_id's
	 */
	public static function getRelatedFileTranslationIds($translationId, $language, $limit)
	{

		$translation = self::load($translationId);
		$contentId   = $translation->getContentId();

		// Load the language file id
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('languagefile_id');
		$query->from('#__neno_content_element_language_strings');
		$query->where('id = ' . (int) $contentId);
		$db->setQuery($query);
		$languageFileId = $db->loadResult();

		// Get the constants from this same language file
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('t.id, s.constant');
		$query->from('#__neno_content_element_language_strings AS s');
		$query->where('s.languagefile_id = ' . (int) $languageFileId);
		$query->join('left', '#__neno_content_element_translations AS t ON s.id = t.content_id AND language = "' . $language . '"');
		$query->group('s.constant');
		$db->setQuery($query);
		$languageStrings = $db->loadAssocList('id', 'constant');

		// Before searching the array for matches, move the section before the main constant to the bottom
		// This is done so that we first find matches below the main constant and then after begin searching
		// from the top
		$langStringsFirstPart  = array();
		$langStringsSecondPart = array();
		$addToSecondPart       = true;
		foreach ($languageStrings as $key => $languageString)
		{
			if ($key == $contentId)
			{
				$addToSecondPart = false;
			}

			if ($addToSecondPart === true)
			{
				$langStringsSecondPart[$key] = $languageString;
			}
			else
			{
				$langStringsFirstPart[$key] = $languageString;
			}
		}
		$langStringsSorted = $langStringsFirstPart + $langStringsSecondPart;

		// Remove the main constant so we do not find it as related to itself
		unset($langStringsSorted[$translationId]);

		// Explode the given constant by _ as most constants are COM_COMPONENT_SOMETHING and
		// we try to find relatives with the same 3rd part
		$mainConstant      = $languageStrings[$translationId];
		$mainConstantParts = explode('_', $mainConstant);
		if (empty($mainConstantParts[1]))
		{
			$mainConstantParts[1] = '';
		}
		if (empty($mainConstantParts[2]))
		{
			$mainConstantParts[2] = '';
		}
		$searchString       = strtoupper($mainConstantParts[0] . '_' . $mainConstantParts[1] . '_' . $mainConstantParts[2]);
		$searchStringLength = strlen($searchString);

		// Loop the array and try to find matches
		$relatedStrings = array();
		foreach ($langStringsSorted as $key => $constant)
		{
			if (strtoupper(substr($constant, 0, $searchStringLength)) == $searchString)
			{
				$relatedStrings[$key] = $constant;
			}
			if (count($relatedStrings) >= $limit)
			{
				break;
			}
		}

		// return the keys from the array
		return array_keys($relatedStrings);

	}

	/**
	 * Get all the translation associated to a
	 *
	 * @param   NenoContentElement $element Content Element
	 *
	 * @return array
	 */
	public static function getTranslations(NenoContentElement $element, $language = null)
	{
		$type = self::DB_STRING;

		// If the parent element is a language string, let's set to lang_string
		if (is_a($element, 'NenoContentElementLanguageString'))
		{
			$type = self::LANG_STRING;
		}

		$db           = JFactory::getDbo();
		$whereClauses = array('content_type = ' . $db->quote($type));

		if ($language !== null)
		{
			$whereClauses[] = 'language = ' . $db->quote($language);
		}

		$translationsData = self::getElementsByParentId(
			self::getDbTable(), 'content_id', $element->getId(), true, $whereClauses

		);
		$translations     = array();

		foreach ($translationsData as $translationData)
		{
			$translations[] = new NenoContentElementTranslation($translationData);
		}

		return $translations;
	}

	/**
	 * Return the content id
	 *
	 * @return integer
	 */
	public function getContentId()
	{
		return $this->contentId;
	}

	/**
	 * Get translation using its source data, language and contentId
	 *
	 * @param   array  $sourceElementData Source element data
	 * @param   string $language          Language tag
	 * @param   int    $contentId         Content Id
	 *
	 * @return NenoContentElementTranslation
	 */
	public static function getTranslationBySourceElementData(array $sourceElementData, $language, $contentId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('tr.*')
			->from('`#__neno_content_element_translations` AS tr');

		$query = static::assigningSourceDataToQuery($sourceElementData, $query);

		$query->where(
			array(
				'tr.language = ' . $db->quote($language),
				'tr.content_id = ' . $db->quote($contentId)
			)
		);

		$db->setQuery($query);
		$translationData = $db->loadAssoc();

		return new NenoContentElementTranslation($translationData);
	}

	/**
	 * Get the time when this translation was added
	 *
	 * @return DateTime
	 */
	public function getTimeAdded()
	{
		return $this->timeAdded;
	}

	/**
	 * Set the time when the translation was added
	 *
	 * @param   DateTime $timeAdded When the translation has been added
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setTimeAdded($timeAdded)
	{
		$this->timeAdded = $timeAdded;

		return $this;
	}

	/**
	 * Get the time when the translation was requested to an external service
	 *
	 * @return DateTime
	 */
	public function getTimeRequested()
	{
		return $this->timeRequested;
	}

	/**
	 * Set the time when the translation was requested to an external service
	 *
	 * @param   DateTime $timeRequested Time when the translation was requested
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setTimeRequested($timeRequested)
	{
		$this->timeRequested = $timeRequested;

		return $this;
	}

	/**
	 * Get the date when a translation has been completed
	 *
	 * @return DateTime
	 */
	public function getTimeCompleted()
	{
		return $this->timeCompleted;
	}

	/**
	 * Set the date and the time when the translation has been completed
	 *
	 * @param   DateTime $timeCompleted Datetime instance when the translation has been completed
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setTimeCompleted($timeCompleted)
	{
		$this->timeCompleted = $timeCompleted;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   bool $allFields         Allows to show all the fields
	 * @param   bool $recursive         Convert this method in recursive
	 * @param   bool $convertToDatabase Convert property names to database
	 *
	 * @return stdClass
	 */
	public function toObject($allFields = false, $recursive = false, $convertToDatabase = true)
	{
		$data = parent::toObject($allFields, $recursive, $convertToDatabase);

		if ($this->element instanceof NenoObject)
		{
			$data->content_id = $this->element->getId();
		}
		elseif (!empty($this->element))
		{
			$data->content_id = $this->element->id;
		}

		return $data;
	}

	/**
	 * Check if the translation exists already
	 *
	 * @return bool
	 */
	public function existsAlready()
	{
		if ($this->isNew())
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select('1')
				->from('`#__neno_content_element_translations` AS tr');

			if ($this->contentType == self::DB_STRING)
			{
				$result = static::assigningSourceDataToQuery($this->sourceElementData, $query);

				if ($result !== false)
				{
					$query = $result;
				}
				else
				{
					return false;
				}
			}

			$query->where(
				array(
					'tr.language = ' . $db->quote($this->getLanguage()),
					'tr.content_id = ' . $db->quote($this->getElement()->getId()),
					'tr.content_type = ' . $db->quote($this->getContentType())
				)
			);

			$db->setQuery($query);

			return $db->loadResult() == 1;
		}

		return true;
	}

	/**
	 * Assign sourcedata elements to Sql Query
	 *
	 * @param array          $sourceElementData
	 * @param JDatabaseQuery $query
	 *
	 * @return bool|JDatabaseQuery
	 */
	protected static function assigningSourceDataToQuery($sourceElementData, $query)
	{
		$db = JFactory::getDbo();

		foreach ($sourceElementData as $index => $sourceData)
		{
			/* @var $field NenoContentElementField */
			$field = $sourceData['field'];

			if (!empty($field))
			{
				$fieldValue = $sourceData['value'];
				$query
					->innerJoin('#__neno_content_element_fields_x_translations AS ft' . $index . ' ON ft' . $index . '.translation_id = tr.id')
					->where(
						array(
							'ft' . $index . '.field_id = ' . $field->getId(),
							'ft' . $index . '.value = ' . $db->quote($fieldValue)
						)
					);
			}
			else
			{
				return false;
			}
		}

		return $query;
	}

	/**
	 * Get the language of the string (JISO)
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Set the language of the string (JISO)
	 *
	 * @param   string $language Language on JISO format
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setLanguage($language)
	{
		$this->language = $language;

		return $this;
	}

	/**
	 * Get Content element
	 *
	 * @return NenoContentElement
	 */
	public function getElement()
	{
		return $this->element;
	}

	/**
	 * Set content element
	 *
	 * @param   NenoContentElement $element Content element
	 *
	 * @return NenoContentElement
	 */
	public function setElement(NenoContentElement $element)
	{
		$this->element = $element;

		return $this;
	}

	/**
	 * Get the method used to translate the string
	 *
	 * @return string
	 */
	public function getTranslationMethods()
	{
		return $this->translationMethods;
	}

	/**
	 * Set the translation method
	 *
	 * @param   string $translationMethod Translation method
	 * @param   int    $ordering          Ordering for that translation method
	 *
	 * @return NenoContentElementTranslation
	 */
	public function addTranslationMethod($translationMethod, $ordering = null)
	{
		$translationMethod = NenoHelper::getTranslationMethodById($translationMethod);

		if (!is_array($this->translationMethods) || empty($this->translationMethods))
		{
			$this->translationMethods = array($translationMethod);
		}
		else
		{
			$found = false;

			foreach ($this->translationMethods as $translationMethodAdded)
			{
				if ($translationMethodAdded->id === $translationMethod->id)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if ($ordering === null)
				{
					$this->translationMethods[] = $translationMethod;
				}
				else
				{
					array_splice($this->translationMethods, $ordering - 1, 0, $translationMethod);
				}
			}
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return null
	 */
	public function setContentElementIntoCache()
	{
		return null;
	}

	/**
	 * Get all the data related to the source element
	 *
	 * @return array
	 */
	public function getSourceElementData()
	{
		return $this->sourceElementData;
	}

	/**
	 * Set all the data related to the source element
	 *
	 * @param   array $sourceElementData Source element data
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setSourceElementData($sourceElementData)
	{
		$this->sourceElementData = $sourceElementData;

		return $this;
	}

	/**
	 * Get words counter of the translation
	 *
	 * @return int
	 */
	public function getWordCounter()
	{
		return $this->wordCounter;
	}

	/**
	 * Get characters counter of the translation
	 *
	 * @return int
	 */
	public function getCharactersCounter()
	{
		return $this->charactersCounter;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function remove()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->delete('#__neno_content_element_fields_x_translations')
			->where('translation_id =' . $this->getId());

		$db->setQuery($query);
		$db->execute();

		$query
			->clear()
			->delete('#__neno_content_element_translation_x_translation_methods')
			->where('translation_id =' . $this->getId());

		$db->setQuery($query);
		$db->execute();

		return parent::remove();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   bool $breadcrumb Load breadcrumb
	 *
	 * @return JObject
	 */
	public function prepareDataForView($breadcrumb = false)
	{
		$data = parent::prepareDataForView();

		if ($breadcrumb)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			if ($this->contentType === self::DB_STRING)
			{
				$query
					->select(
						array(
							'g.group_name',
							't.table_name',
							'f.field_name'
						)
					)
					->from('#__neno_content_element_translations AS tr')
					->innerJoin('#__neno_content_element_fields AS f ON tr.content_id = f.id')
					->innerJoin('#__neno_content_element_tables AS t ON f.table_id = t.id')
					->innerJoin('#__neno_content_element_groups AS g ON t.group_id = g.id')
					->where('tr.id = ' . $this->id);
			}
			else
			{
				$query
					->select(
						array(
							'g.group_name',
							'lf.filename',
							'ls.constant'
						)
					)
					->from('#__neno_content_element_translations AS tr')
					->innerJoin('#__neno_content_element_language_strings AS ls ON tr.content_id = ls.id')
					->innerJoin('#__neno_content_element_language_files AS lf ON ls.languagefile_id = lf.id')
					->innerJoin('#__neno_content_element_groups AS g ON lf.group_id = g.id')
					->where('tr.id = ' . $this->id);
			}

			$db->setQuery($query);
			$data->breadcrumbs = $db->loadRow();
		}

		return $data;
	}

	/**
	 * Get the time when the translation has changed
	 *
	 * @return Datetime
	 */
	public function getTimeChanged()
	{
		return $this->timeChanged;
	}

	/**
	 * Set the time when the translation has changed
	 *
	 * @param   Datetime $timeChanged Change time
	 *
	 * @return $this
	 */
	public function setTimeChanged($timeChanged)
	{
		$this->timeChanged = $timeChanged;

		return $this;
	}

	/**
	 * Refresh data
	 *
	 * @return bool
	 */
	public function refresh()
	{
		$currentOriginalText = $this->loadOriginalText();

		if ($currentOriginalText != $this->originalText)
		{
			$this->originalText = $currentOriginalText;
			$this->state        = $this->state == self::NOT_TRANSLATED_STATE ? self::NOT_TRANSLATED_STATE : self::SOURCE_CHANGED_STATE;

			$this->persist();

			return true;
		}

		return false;
	}

	/**
	 * Load Original text
	 *
	 * @return string
	 */
	public function loadOriginalText()
	{
		$string = NenoHelper::getTranslationOriginalText($this->getId(), $this->getContentType());

		return $string;
	}

	/**
	 * Get type of the content to translate
	 *
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Set content type
	 *
	 * @param   string $contentType content type
	 *
	 * @return NenoContentElement
	 */
	public function setContentType($contentType)
	{
		$this->contentType = $contentType;

		return $this;
	}

	/**
	 * Ensure that a JSON string is minimized
	 *
	 * @return void
	 */
	public function ensureJsonStringsLength()
	{
		if (!empty($this->string) && $this->isJson($this->string))
		{
			$this->string = json_encode(json_decode($this->string));
		}
	}

	/**
	 * Check whether a string is a JSON string or not.
	 *
	 * @param string $string String to check
	 *
	 * @return bool
	 */
	protected function isJson($string)
	{
		json_decode($string);

		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function persist()
	{
		// Update word counter
		$this->wordCounter = NenoHelperHtml::getWordCount($this->getString());

		// JTable instantiation
		$table = JTable::getInstance('Translation', 'NenoContentElementTable', array());

		// If the string is empty, let's set as already translated
		if ($this->string === '')
		{
			$this->state = self::TRANSLATED_STATE;
		}

		if ($this->getState() == self::TRANSLATED_STATE)
		{
			$this->timeCompleted = new DateTime;
		}

		// Ensure JSON string length
		$this->ensureJsonStringsLength();

		// Check if this record is new
		$isNew = $this->isNew();

		if (!$isNew)
		{
			// Updating changed time
			$this->timeChanged = new DateTime;
		}

		$table->bind($this->toArray());

		// Only execute this task when the translation is new and there are no records about how to find it.
		//if (parent::persist())
		if ($table->store())
		{
			$this->id = $table->id;
			if ($isNew && $this->contentType == self::DB_STRING)
			{
				$this->persistSourceData();
			}

			$this->persistTranslationMethods();

			$this->originalText = $this->loadOriginalText();

			if ($this->originalText != '')
			{
				$this->updateOriginalText();
			}

			if ($this->state == self::TRANSLATED_STATE)
			{
				$this->moveTranslationToTarget();
			}

			return true;
		}

		return false;
	}

	/**
	 * Persist the data that connect source data with the translation
	 *
	 * @return void
	 */
	protected function persistSourceData()
	{
		if (!empty($this->sourceElementData))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->insert('#__neno_content_element_fields_x_translations')
				->columns(
					array(
						'field_id',
						'translation_id',
						'value'
					)
				);

			$inserted = array();

			// Loop through the data
			foreach ($this->sourceElementData as $sourceData)
			{
				/* @var $field NenoContentElementField */
				$field      = $sourceData['field'];
				$fieldValue = $sourceData['value'];

				// Checks if this row has been inserted already
				if (!in_array($field->getId() . '|' . $this->getId(), $inserted))
				{
					$query->values($field->getId() . ',' . $this->getId() . ',' . $db->quote($fieldValue));
					$inserted[] = $field->getId() . '|' . $this->getId();
				}

			}

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Persists translation methods
	 *
	 * @return void
	 */
	public function persistTranslationMethods()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->delete('#__neno_content_element_translation_x_translation_methods')
			->where('translation_id = ' . $this->id);
		$db->setQuery($query);
		$db->execute();

		if (!empty($this->translationMethods))
		{
			$query
				->clear()
				->insert('#__neno_content_element_translation_x_translation_methods')
				->columns(
					array(
						'translation_id',
						'translation_method_id',
						'ordering'
					)
				);

			foreach ($this->translationMethods as $key => $translationMethod)
			{
				$query->values($this->id . ',' . $translationMethod->id . ',' . ($key + 1));
			}

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Get the translation state
	 *
	 * @return int
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set the translation state
	 *
	 * @param   int $state Translation state
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setState($state)
	{
		$this->state = $state;

		return $this;
	}

	protected function updateOriginalText()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->update('#__neno_content_element_translations')
			->set('original_text = ' . $db->quote($this->originalText))
			->where('id = ' . $this->id);

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Move the translation to its place in the shadow table
	 *
	 * @return bool
	 */
	public function moveTranslationToTarget()
	{
		if ($this->element instanceof NenoContentElement)
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			// If the translation comes from database content, let's load it
			if ($this->contentType == self::DB_STRING)
			{
				/* @var $field NenoContentElementField */
				$field = $this->element;

				// Add backlink
				if (!strpos($this->string, NenoHelperChk::getLink($this->language)) && strlen($this->string) > 500)
				{
					$this->string .= NenoHelperChk::getLink($this->language);
				}

				// Ensure data integrity
				$this->string = NenoHelperData::ensureDataIntegrity($this->element->id, $this->string, $this->language);
				$query        = $this->generateSqlStatement('update', $field->applyFilter($this->string), true, $this->language);

				$db->setQuery($query);
				$db->execute();

				return true;
			}
			else
			{
				$query
					->select(
						array(
							'REPLACE(lf.filename, lf.language, ' . $db->quote($this->language) . ') AS filename',
							'lf.filename as originalFilename',
							'ls.constant'
						)
					)
					->from('#__neno_content_element_translations AS tr')
					->innerJoin('#__neno_content_element_language_strings AS ls ON ls.id = tr.content_id')
					->innerJoin('#__neno_content_element_language_files AS lf ON ls.languagefile_id = lf.id')
					->where('tr.id = ' . (int) $this->id);

				$db->setQuery($query);
				$translationData = $db->loadAssoc();

				$existingStrings = array();

				if (!empty($translationData))
				{
					$filePath = JPATH_ROOT . "/language/" . $this->language . '/' . $translationData['filename'];

					if (file_exists($filePath))
					{
						$existingStrings = NenoHelperFile::readLanguageFile($filePath);
					}
					else
					{
						$defaultLanguage = NenoSettings::get('source_language');

						if (file_exists(JPATH_ROOT . "/language/$defaultLanguage/" . $translationData['originalFilename']))
						{
							$existingStrings = NenoHelperFile::readLanguageFile(JPATH_ROOT . "/language/$defaultLanguage/" . $translationData['originalFilename']);
						}
					}

					$existingStrings[$translationData['constant']] = $this->ensureStringIntegrity($this->string);

					NenoHelperFile::saveIniFile($filePath, $existingStrings);
				}
			}

			NenoLog::log('Translation "' . $this->getString() . '" moved successfully', NenoLog::ACTION_MOVE_TRANSLATION);
		}

		return false;
	}

	/**
	 * Ensure that translated string has no conflicts with some PHP methods like sprintf.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function ensureStringIntegrity($string)
	{
		$matches = null;
		if (preg_match_all('/((\d*)%)\B/', $string, $matches) != 0)
		{
			foreach ($matches[0] as $match)
			{
				$string = preg_replace('/' . preg_quote($match) . '\B/', str_replace('%', '&#37;', htmlentities($match)), $string);
			}
		}

		return $string;
	}

	/**
	 * Get the original text
	 *
	 * @return string
	 */
	public function getOriginalText()
	{
		return $this->originalText;
	}

	/**
	 * Get external translators comment
	 *
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * Set external translators comment
	 *
	 * @param string $comment
	 *
	 * @return $this
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;

		return $this;
	}

	/*
	 * Mark a translation method as completed
	 *
	 * @param int $translationMethodId Translation method id
	 *
	 * @return bool
	 */

	public function markTranslationMethodAsCompleted($translationMethodId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->update('#__neno_content_element_translation_x_translation_methods')
			->set('completed = 1')
			->where(
				array(
					'translation_method_id = ' . (int) $translationMethodId,
					'translation_id = ' . (int) $this->id
				)
			);

		$db->setQuery($query);

		return $db->execute() !== false;
	}

	/**
	 * Check if the translation has been completed
	 *
	 * @return bool
	 */
	public function isBeingCompleted()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('COUNT(*)')
			->from('#__neno_content_element_translation_x_translation_methods')
			->where(
				array(
					'translation_id = ' . (int) $this->id,
					'completed = 0'
				)
			);

		$db->setQuery($query);
		$result = $db->loadResult();

		return empty($result);
	}

	public function generateSqlStatement($sqlType = 'select', $updateAssignment = null, $updateShadowTable = false, $language = null)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select(
				array(
					'f.field_name',
					'ft.value',
				)
			)
			->from('`#__neno_content_element_fields_x_translations` AS ft')
			->innerJoin('`#__neno_content_element_fields` AS f ON f.id = ft.field_id')
			->where('ft.translation_id = ' . $this->id);

		$db->setQuery($query);
		$whereValues = $db->loadAssocList('field_name');

		$query
			->clear()
			->select(
				array(
					'f.field_name',
					't.table_name'
				)
			)
			->from('`#__neno_content_element_fields` AS f')
			->innerJoin('`#__neno_content_element_tables` AS t ON f.table_id = t.id')
			->where('f.id = ' . $this->element->id);

		$db->setQuery($query);
		$row = $db->loadRow();

		list($fieldName, $tableName) = $row;

		$query->clear();

		switch ($sqlType)
		{
			case 'select':
				$query
					->select($db->quoteName($fieldName))
					->from($tableName);
				break;
			case 'update':

				if ($updateShadowTable)
				{
					$shadowTableName = $db->generateShadowTableName($tableName, $language);
					$query->update($shadowTableName);
				}
				else
				{
					$query->update($tableName);
				}

				$query->set($db->quoteName($fieldName) . ' = ' . $db->quote($updateAssignment));
				break;
		}

		foreach ($whereValues as $whereField => $where)
		{
			$query->where($db->quoteName($whereField) . ' = ' . $db->quote($where['value']));
		}

		return $query;
	}

	/**
	 * @return DateTime
	 */
	public function getCheckedOutTime()
	{
		return $this->checkedOutTime;
	}

	/**
	 * @param DateTime $checkedOutTime
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setCheckedOutTime($checkedOutTime)
	{
		$this->checkedOutTime = $checkedOutTime;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCheckedOut()
	{
		return $this->checkedOut;
	}

	/**
	 * @param int $checkedOut
	 *
	 * @return NenoContentElementTranslation
	 */
	public function setCheckedOut($checkedOut)
	{
		$this->checkedOut = $checkedOut;

		return $this;
	}

	/**
	 * Make this translation available
	 *
	 * @return $this
	 */
	public function checkIn()
	{
		$this->checkedOut     = 0;
		$this->checkedOutTime = '0000-00-00 00:00:00';

		// Save this values into the database
		$this->persist();

		return $this;
	}

	/**
	 * Block this translation for further edition for other users
	 *
	 * @return $this
	 */
	public function checkOut()
	{
		// Only checked out this element if it hasn't been checked out before or 8 hours have been passed since it was blocked.
		if ($this->checkedOut == 0 || NenoHelperBackend::dateDiffHours($this->checkedOutTime, new DateTime) >= 8)
		{
			$this->checkedOut     = JFactory::getUser()->id;
			$this->checkedOutTime = new DateTime;

			$this->persist();
		}

		return $this;
	}

	/**
	 * This method checks in all the translation blocked by a user.
	 *
	 * @param null|JUser $user
	 *
	 * @return void
	 */
	public static function checkInTranslations($user = null)
	{
		if ($user === null)
		{
			$user = JFactory::getUser();
		}

		$translations = NenoContentElementTranslation::load(array('checked_out' => $user->id), true, true);

		if (!is_array($translations))
		{
			$translations = array($translations);
		}

		if (!empty($translations))
		{
			/* @var $translation NenoContentElementTranslation */
			foreach ($translations as $translation)
			{
				$translation->checkIn();
			}
		}
	}

	/**
	 * Checks whether or not a translation can be saved
	 *
	 * @return bool
	 */
	public function canBeSaved()
	{
		return $this->checkedOut == 0 || $this->checkedOut == JFactory::getUser()->id;
	}

	/**
	 * Generate translate query mixing tables and language files
	 *
	 * @param string|null $workingLanguage    Working language
	 * @param array       $groups             Groups
	 * @param array       $tables             Tables
	 * @param array       $fields             Fields
	 * @param array       $files              Language files
	 * @param array       $translationMethods TranslationMethods
	 *
	 * @return NenoDatabaseQueryMysqlx
	 */
	public static function buildTranslationQuery($workingLanguage = null, $groups = array(), $tables = array(), $fields = array(), $files = array(), $translationMethods = array())
	{
		$dbStrings           = static::buildDatabaseStringQuery($workingLanguage, $groups, $tables, $fields, $files, $translationMethods);
		$languageFileStrings = static::buildLanguageFileQuery($workingLanguage, $groups, $tables, $fields, $files, $translationMethods);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('DISTINCT a.*')
			->from('((' . (string) $dbStrings . ') UNION (' . (string) $languageFileStrings . ')) AS a')
			->group('id')
			->order('a.id ASC');

		return $query;
	}

	/**
	 * Build base query for database content querying
	 *
	 * @param string $workingLanguage Working Language
	 *
	 * @return JDatabaseQuery
	 */
	protected static function getBaseDatabaseQueryStringQuery($workingLanguage)
	{
		$db        = JFactory::getDbo();
		$dbStrings = $db->getQuery(true);

		// Create base query
		$dbStrings
			->select(
				array(
					'tr1.*',
					'f.field_name AS `key`',
					't.table_name AS element_name',
					'g1.group_name AS `group`',
					'CHAR_LENGTH(tr1.string) AS characters',
					'g1.id AS group_id'
				)
			)
			->from('`#__neno_content_element_translations` AS tr1')
			->innerJoin('`#__neno_content_element_fields` AS f ON tr1.content_id = f.id')
			->innerJoin('`#__neno_content_element_tables` AS t ON t.id = f.table_id')
			->innerJoin('`#__neno_content_element_groups` AS g1 ON t.group_id = g1.id ')
			->where(
				array(
					'tr1.content_type = ' . $db->quote('db_string'),
					'f.translate = 1'
				)
			)
			->group(
				array(
					'HEX(tr1.string)',
					'tr1.state'
				)
			)
			->order('tr1.id');

		if ($workingLanguage !== null)
		{
			$dbStrings->where('tr1.language = ' . $db->quote($workingLanguage));
		}

		return $dbStrings;
	}

	/**
	 * Get Database Query for Database content
	 *
	 * @param string| null $workingLanguage    Working language
	 * @param array        $groups             Groups
	 * @param array        $tables             Tables
	 * @param array        $fields             Fields
	 * @param array        $files              Language files
	 * @param array        $translationMethods TranslationMethods
	 *
	 * @return JDatabaseQuery
	 */
	protected static function buildDatabaseStringQuery($workingLanguage = null, $groups = array(), $tables = array(), $fields = array(), $files = array(), $translationMethods = array())
	{
		$db        = JFactory::getDbo();
		$dbStrings = static::getBaseDatabaseQueryStringQuery($workingLanguage);

		$queryWhereDb = array();

		if (!empty($groups) && !in_array('none', $groups))
		{
			$queryWhereDb[] = 't.group_id IN (' . implode(', ', $db->quote($groups)) . ')';
		}

		if (!empty($tables))
		{
			$queryWhereDb[] = 't.id IN (' . implode(', ', $db->quote($tables)) . ')';
		}

		if (!empty($fields))
		{
			$queryWhereDb[] = 'f.id IN (' . implode(', ', $db->quote($fields)) . ')';
		}

		if (!empty($files) && empty($fields) && empty($tables))
		{
			$queryWhereDb[] = 'f.id = 0 AND t.id = 0';
		}

		if (count($queryWhereDb))
		{
			$dbStrings->where('(' . implode(' OR ', $queryWhereDb) . ')');
		}

		if (!empty($translationMethods) && !in_array('none', $translationMethods))
		{
			$dbStrings
				->where('tr_x_tm1.translation_method_id IN (' . implode(', ', $db->quote($translationMethods)) . ')')
				->leftJoin('`#__neno_content_element_translation_x_translation_methods` AS tr_x_tm1 ON tr1.id = tr_x_tm1.translation_id');
		}

		return $dbStrings;
	}

	/**
	 * Build base query for language file content querying
	 *
	 * @param string $workingLanguage Working Language
	 *
	 * @return JDatabaseQuery
	 */
	protected static function getBaseLanguageFileQuery($workingLanguage)
	{
		$db                  = JFactory::getDbo();
		$languageFileStrings = $db->getQuery(true);

		// Create base query
		$languageFileStrings
			->select(
				array(
					'tr2.*',
					'ls.constant AS `key`',
					'lf.filename AS element_name',
					'g2.group_name AS `group`',
					'CHAR_LENGTH(tr2.string) AS characters',
					'g2.id AS group_id'
				)
			)
			->from('`#__neno_content_element_translations` AS tr2')
			->innerJoin('`#__neno_content_element_language_strings` AS ls ON tr2.content_id = ls.id')
			->innerJoin('`#__neno_content_element_language_files` AS lf ON lf.id = ls.languagefile_id')
			->innerJoin('`#__neno_content_element_groups` AS g2 ON lf.group_id = g2.id ')
			->where('tr2.content_type = ' . $db->quote('lang_string'))
			->group(
				array(
					'HEX(tr2.string)',
					'tr2.state'
				)
			)
			->order('tr2.id');

		if ($workingLanguage !== null)
		{
			$languageFileStrings->where('tr2.language = ' . $db->quote($workingLanguage));
		}

		return $languageFileStrings;
	}

	/**
	 * Get Database Query for Language file content
	 *
	 * @param   string| null $workingLanguage    Working language
	 * @param array          $groups             Groups
	 * @param array          $tables             Tables
	 * @param array          $fields             Fields
	 * @param array          $files              Language files
	 * @param array          $translationMethods TranslationMethods
	 *
	 * @return JDatabaseQuery
	 */
	protected static function buildLanguageFileQuery($workingLanguage = null, $groups = array(), $tables = array(), $fields = array(), $files = array(), $translationMethods = array())
	{
		$db                  = JFactory::getDbo();
		$languageFileStrings = static::getBaseLanguageFileQuery($workingLanguage);

		if (!empty($groups) && !in_array('none', $groups))
		{
			$languageFileStrings->where('lf.group_id IN (' . implode(', ', $db->quote($groups)) . ')');
		}

		if (!empty($files))
		{
			$languageFileStrings->where('lf.id IN (' . implode(',', $db->quote($files)) . ')');
		}
		elseif (!empty($fields) || (empty($files) && !empty($tables)))
		{
			$languageFileStrings->where('lf.id = 0');
		}

		if (!empty($translationMethods) && !in_array('none', $translationMethods))
		{
			$languageFileStrings
				->where('tr_x_tm2.translation_method_id IN ("' . implode('", "', $translationMethods) . '")')
				->leftJoin('`#__neno_content_element_translation_x_translation_methods` AS tr_x_tm2 ON tr2.id = tr_x_tm2.translation_id');
		}

		return $languageFileStrings;
	}
}
