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
 * Class ContentElementField
 *
 * @since  1.0
 */
class NenoContentElementField extends NenoContentElement implements NenoContentElementInterface
{
	/**
	 * @var array
	 */
	public static $translatableFields = array(
	  'varchar',
	  'tinytext',
	  'text',
	  'mediumtext',
	  'longtext'
	);
	/**
	 * @var
	 */
	private static $filterMapByFieldName = array(
	  'alias' => 'CMD',
	  'slug'  => 'CMD'
	);
	/**
	 * @var stdClass
	 */
	public $wordCount;
	/**
	 * @var array
	 */
	public $translationMethodUsed;
	/**
	 * @var NenoContentElementTable
	 */
	protected $table;
	/**
	 * @var string
	 */
	protected $fieldName;
	/**
	 * @var string
	 */
	protected $fieldType;
	/**
	 * @var boolean
	 */
	protected $translate;
	/**
	 * @var array|null
	 */
	protected $translations;
	/**
	 * @var string
	 */
	protected $filter;
	/**
	 * @var bool
	 */
	protected $discovered;
	/**
	 * @var string
	 */
	protected $comment;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Field data
	 * @param   bool  $loadExtraData Load extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);

		$data = new JObject($data);

		if ($loadParent)
		{
			$this->table = $data->get('table') == NULL
			  ? NenoContentElementTable::load($data->get('tableId'), $loadExtraData, $loadParent)
			  : $data->get('table');
		}

		$this->translations = NULL;

		if (!$this->isNew() && $loadExtraData)
		{
			$this->getWordCount();
		}
	}

	/**
	 * Get an object with the amount of words per state
	 *
	 * @return stdClass
	 */
	public function getWordCount()
	{
		if ($this->wordCount === NULL)
		{
			$cacheId   = NenoCache::getCacheId(get_called_class() . '.' . __FUNCTION__, array($this->getId()));
			$cacheData = NenoCache::getCacheData($cacheId);

			if ($cacheData === NULL)
			{
				$db              = JFactory::getDbo();
				$query           = $db->getQuery(true);
				$workingLanguage = NenoHelper::getWorkingLanguage();

				$query
				  ->select(
					array(
					  'SUM(word_counter) AS counter',
					  'tr.state'
					)
				  )
				  ->from('#__neno_content_element_translations AS tr')
				  ->where(
					array(
					  'tr.content_type = ' . $db->quote('db_string'),
					  'tr.language LIKE ' . $db->quote($workingLanguage),
					  'tr.content_id = ' . $this->getId()
					)
				  )
				  ->group('tr.state');

				$db->setQuery($query);
				$statistics = $db->loadAssocList('state');

				$this->wordCount        = $this->generateWordCountObjectByStatistics($statistics);
				$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;

				$cacheData = $this->wordCount;
				NenoCache::setCacheData($cacheId, $cacheData);
			}

			$this->wordCount = $cacheData;
		}

		return $this->wordCount;
	}

	/**
	 * Get a field using its field Id
	 *
	 * @param   integer $fieldId Field Id
	 *
	 * @return NenoContentElementField
	 */
	public static function getFieldById($fieldId)
	{
		return self::load($fieldId);
	}

	/**
	 * Check if a Database type is translatable
	 *
	 * @param   string $fieldType Field type
	 *
	 * @return bool
	 */
	public static function isTranslatableType($fieldType)
	{
		return in_array($fieldType, self::$translatableFields);
	}

	/**
	 * Get comment
	 *
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * Set comment
	 *
	 * @param string $comment Comment
	 *
	 * @return $this
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;

		return $this;
	}

	/**
	 * Get field type
	 *
	 * @return string
	 */
	public function getFieldType()
	{
		return $this->fieldType;
	}

	/**
	 * Set field type
	 *
	 * @param   string $fieldType Field type
	 *
	 * @return $this
	 */
	public function setFieldType($fieldType)
	{
		$this->fieldType = $fieldType;

		return $this;
	}

	/**
	 * check if the field is translatable
	 *
	 * @return boolean
	 */
	public function isTranslate()
	{
		return $this->translate;
	}

	/**
	 * Mark this field as translatable
	 *
	 * @param   boolean $translate If field should be translated
	 * @param   boolean $force     Force the translate status
	 *
	 * @return $this
	 */
	public function setTranslate($translate, $force = false)
	{
		$this->translate = $translate;

		if ($this->translate && !$force)
		{
			$this->checkTranslatableStatusFromContentElementFile();
		}

		return $this;
	}

	/**
	 * Check if the table should be translatable
	 *
	 * @return void
	 */
	public function checkTranslatableStatusFromContentElementFile()
	{
		$filePath = JPATH_NENO . '/contentelements/' . str_replace('#__', '', $this->getTable()
			->getTableName()) . '_contentelements.xml';

		// If the file exists, let's check what is there
		if (file_exists($filePath))
		{
			$this->translate = NenoHelper::getFieldAttributeFromContentElementFile($filePath, $this->fieldName, 'translate') == 1;
		}
	}

	/**
	 * Get the table that contains this field
	 *
	 * @return NenoContentElementTable
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Set Table
	 *
	 * @param   NenoContentElementTable $table Table
	 *
	 * @return $this
	 */
	public function setTable(NenoContentElementTable $table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Check if the field has been marked as translatable
	 *
	 * @return boolean
	 */
	public function hasBeenMarkedAsTranslated()
	{
		return $this->translate;
	}

	/**
	 * Check if this field is translatable
	 *
	 * @return bool
	 */
	public function isTranslatable()
	{
		return $this->translate;
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
		$object = parent::toObject($allFields, $recursive, $convertToDatabase);

		// If the table property is not null and it's an instance of NenoObject, let's use the getId method
		if (!empty($this->table) && $this->table instanceof NenoObject && $convertToDatabase)
		{
			$object->table_id = $this->table->getId();
		}
		elseif (!empty($this->table) && $convertToDatabase)
		{
			/* @var $table stdClass */
			$table            = $this->table;
			$object->table_id = $table->id;
		}

		return $object;
	}

	/**
	 * Remove all the translations associated to this field
	 *
	 * @return void
	 */
	public function removeTranslations()
	{
		$translations = $this->getTranslations();

		/* @var $translation NenoContentElementTranslation */
		foreach ($translations as $translation)
		{
			$translation->remove();
		}
	}

	/**
	 * Get all the translations for this field
	 *
	 * @return array
	 */
	public function getTranslations()
	{
		if ($this->translations === NULL)
		{
			$this->translations = NenoContentElementTranslation::getTranslations($this);
		}

		return $this->translations;
	}

	/**
	 * Set translations
	 *
	 * @param   array $translations Translations
	 *
	 * @return $this
	 */
	public function setTranslations(array $translations)
	{
		$this->translations = $translations;

		return $this;
	}

	/**
	 * Apply field filter
	 *
	 * @param   string $string String to apply the filter
	 *
	 * @return mixed
	 */
	public function applyFilter($string)
	{
		// If the string is empty, there's no need to filter it.
		if (empty($string))
		{
			return $string;
		}

		$filter = JFilterInput::getInstance();
		if (empty($this->filter))
		{
			$this->filter = 'RAW';
		}

		if ($this->filter == 'CMD')
		{
			$unwanted_array = array(
				'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
				'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
				'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
				'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
				'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );

			$string = strtr($string, $unwanted_array);
		}

		return $filter->clean($string, $this->filter);
	}

	/**
	 * Get Filter
	 *
	 * @return string
	 */
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * Set filter
	 *
	 * @param   string $filter Filter
	 *
	 * @return $this
	 */
	public function setFilter($filter)
	{
		$this->filter = $filter;

		return $this;
	}

	/**
	 * Discover the element
	 *
	 * @return bool True on success
	 */
	public function discoverElement()
	{
		NenoHelper::setSetupState(
		  JText::sprintf(
			'COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP_TABLE_FIELD',
			$this->getTable()->getGroup()->getGroupName(),
			$this->getTable()->getTableName(),
			$this->getFieldName()
		  ),
		  '3.1'
		);

		if ($this->persistTranslations() === true)
		{
			$this
			  ->setDiscovered(true)
			  ->persist();
		}
	}

	/**
	 * Get Field name
	 *
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->fieldName;
	}

	/**
	 * Set field name
	 *
	 * @param   string $fieldName Field name
	 *
	 * @return $this
	 */
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;

		return $this;
	}

	/**
	 * Persist a specific string
	 *
	 * @param array  $string
	 * @param string $language
	 * @param array  $commonData
	 * @param array  $primaryKeyData
	 * @param array  $translationMethods Translation method list
	 * @param bool   $keepState Keep translation state unchanged
	 *
	 * @return void
	 */
	protected function persistStringForLanguage($string, $language, $commonData, $primaryKeyData, $translationMethods, $keepState = false)
	{
		$commonData['language'] = $language;
		$commonData['string']   = $string['string'] === NULL ? '' : $string['string'];

		// If the string is empty or is a number, let's mark as translated.
		if (empty($string['string']) || is_numeric($string['string']))
		{
			$commonData['state'] = NenoContentElementTranslation::TRANSLATED_STATE;
		}
		else
		{
			if ($keepState == false)
			{
				$commonData['state'] = NenoContentElementTranslation::NOT_TRANSLATED_STATE;
			}
		}

		$translation     = new NenoContentElementTranslation($commonData);
		$sourceData      = array();
		$fieldBreakpoint = array();

		foreach ($primaryKeyData as $primaryKey)
		{
			$field     = self::getFieldByTableAndFieldName($this->getTable(), $primaryKey);
			$fieldData = array(
			  'field' => $field,
			  'value' => $string[$primaryKey]
			);

			$sourceData[]                 = $fieldData;
			$fieldBreakpoint[$primaryKey] = $string[$primaryKey];
		}

		// Save breakpoint into the database
		NenoSettings::set('field_breakpoint', json_encode($fieldBreakpoint));

		$translation->setSourceElementData($sourceData);

		// If the translation does not exists already, let's add it
		if ($translation->existsAlready() && $keepState == false)
		{
			$translation = NenoContentElementTranslation::getTranslationBySourceElementData($sourceData, $language, $this->getId());
			$translation->setElement($this);

			if ($translation->refresh())
			{
				$translation->persist();
			}
		}

		$translation = $this->persistTranslationMethodForTranslation($translation, $language, $translationMethods);

		$this->translations[] = $translation;
	}

	/**
	 * Persist translation methods for a translation
	 *
	 * @param NenoContentElementTranslation $translation        Translation
	 * @param string                        $language           Language
	 * @param array                         $translationMethods Translation method list
	 *
	 * @return NenoContentElementTranslation
	 */
	protected function persistTranslationMethodForTranslation($translation, $language, $translationMethods)
	{
		$currentTranslationMethods = $translation->getTranslationMethods();

		if (empty($currentTranslationMethods[$language]))
		{
			if (!empty($translationMethods[$language]))
			{
				$translationMethodsTr = $translationMethods[$language];

				foreach ($translationMethodsTr as $translationMethodTr)
				{
					$translation->addTranslationMethod($translationMethodTr->translation_method_id);
				}
			}
		}

		$translation->persist();

		return $translation;
	}

	/**
	 * Persist progression counters
	 *
	 * @return void
	 */
	protected function persistProgressCounters()
	{
		$progressCounters = $this->getProgressCounters();
		if (!NenoSettings::get('installation_completed'))
		{
			NenoHelper::setSetupState(
			  JText::sprintf(
				'COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP_TABLE_FIELD_PROGRESS',
				$this->getTable()->getGroup()->getGroupName(),
				$this->getTable()->getTableName(),
				$this->getFieldName(),
				$progressCounters['processed'],
				$progressCounters['total']
			  ),
			  3
			);
		}
	}

	/**
	 * Persist a specific string
	 *
	 * @param array  $string
	 * @param array  $languages
	 * @param string $defaultLanguage
	 * @param array  $commonData
	 * @param array  $primaryKeyData
	 * @param array  $translationMethods Translation method list
	 * @param bool   $keepState Keep translation state unchanged
	 *
	 * @return void
	 */
	protected function persistString($string, $languages, $defaultLanguage, $commonData, $primaryKeyData, $translationMethods, $keepState = false)
	{
		$this->persistProgressCounters();

		if ($string['state'] == 1 || ($string['state'] == 0 && NenoSettings::get('copy_unpublished', 1)) || ($string['state'] == -2 && NenoSettings::get('copy_trashed', 0)))
		{
			foreach ($languages as $language)
			{
				if ($defaultLanguage !== $language->lang_code)
				{
					$this->persistStringForLanguage($string, $language->lang_code, $commonData, $primaryKeyData, $translationMethods, $keepState);
				}
			}
		}
	}

	/**
	 * Persist all the translations
	 *
	 * @param   array|null  $recordId Record id to just load that row
	 * @param   string|null $language Language tag
	 *
	 * @return bool True on success
	 */
	public function persistTranslations($recordId = NULL, $language = NULL)
	{
		if ($this->translate)
		{
			$commonData = array(
			  'contentType' => NenoContentElementTranslation::DB_STRING,
			  'contentId'   => $this->getId(),
			  'content'     => $this,
			  'state'       => NenoContentElementTranslation::NOT_TRANSLATED_STATE,
			  'timeAdded'   => new DateTime,
			  'comment'     => $this->comment
			);

			if ($language !== NULL)
			{
				$languageData            = new stdClass;
				$languageData->lang_code = $language;
				$languages               = array($languageData);
			}
			else
			{
				$languages = NenoHelper::getLanguages(false);
			}

			$defaultLanguage    = NenoSettings::get('source_language');
			$this->translations = array();
			$strings            = $this->getStrings($recordId);
			$primaryKeyData     = $this->getTable()->getPrimaryKey();
			$translationMethods = NenoHelper::getTranslationMethodsByTableId($this->table->getId());

			if (!empty($strings))
			{
				foreach ($strings as $string)
				{
					$this->persistString($string, $languages, $defaultLanguage, $commonData, $primaryKeyData, $translationMethods);
				}

				NenoSettings::set('field_breakpoint', NULL);
			}
		}
		else
		{
			$translationsCount = count($this->translations);
			for ($i = 0; $i < $translationsCount; $i++)
			{
				$translation = $this->translations[$i];
				/* @var $translation NenoContentElementTranslation */
				$translation->refresh();

				$this->translations[$i] = $translation;
			}
		}

		return true;
	}
	
	public function convertContentToTranslation($record, $asocId, $lang)
	{
		if ($this->translate)
		{
			$commonData = array(
				'contentType' => NenoContentElementTranslation::DB_STRING,
				'contentId'   => $this->getId(),
				'content'     => $this,
				'state'       => NenoContentElementTranslation::TRANSLATED_STATE,
				'timeAdded'   => new DateTime,
				'comment'     => $this->comment
			);

			$languageData            = new stdClass;
			$languageData->lang_code = $lang;
			$languages               = array($languageData);

			$defaultLanguage               = NenoSettings::get('source_language');
			$this->translations            = array();
			$primaryKeyData                = $this->getTable()->getPrimaryKey();
			$string                        = $this->getStrings($record);
			$string[0][$primaryKeyData[0]] = $asocId;

			$translationMethods = NenoHelper::getTranslationMethodsByTableId($this->table->getId());

			if (!empty($string))
			{
				$this->persistString($string[0], $languages, $defaultLanguage, $commonData, $primaryKeyData, $translationMethods, true);
			}
		}
		else
		{
			$translationsCount = count($this->translations);
			for ($i = 0; $i < $translationsCount; $i++)
			{
				$translation = $this->translations[$i];
				/* @var $translation NenoContentElementTranslation */
				$translation->refresh();

				$this->translations[$i] = $translation;
			}
		}

		return true;
	}
	
	/**
	 * Get installation progress counters
	 *
	 * @return array
	 */
	public function getProgressCounters()
	{
		$db                = JFactory::getDbo();
		$query             = $db->getQuery(true);
		$subqueryTotal     = $db->getQuery(true);
		$subqueryProcessed = $db->getQuery(true);

		$subqueryTotal
		  ->select('COUNT(*)')
		  ->from($this->table->getTableName());

		$subqueryProcessed
		  ->select('COUNT(*)')
		  ->from($this->table->getTableName());

		$primaryKeyData = $this->getTable()->getPrimaryKey();
		$breakpoint     = NenoSettings::get('field_breakpoint', NULL);

		if (!empty($breakpoint))
		{
			$breakpoint = json_decode($breakpoint, true);
			foreach ($primaryKeyData as $primaryKey)
			{
				if (!empty($breakpoint[$primaryKey]))
				{
					$subqueryProcessed->where($db->quoteName($primaryKey) . ' < ' . $db->quote($breakpoint[$primaryKey]));
				}
			}
		}
		else
		{
			$subqueryProcessed = 0;
		}

		// If there's no filter applied, let's applied the ones for the tables
		if ($this->getTable()->isTranslate() == 2)
		{
			$filters = $this->getTable()->getTableFilters();

			foreach ($filters as $filter)
			{
				if ($subqueryProcessed !== 0)
				{
					$subqueryProcessed->where(NenoHelper::getWhereClauseForTableFilters($filter));
				}

				$subqueryTotal->where(NenoHelper::getWhereClauseForTableFilters($filter));
			}
		}

		$query
		  ->select(
			array(
			  '(' . (string) $subqueryTotal . ') AS total',
			  '(' . (string) $subqueryProcessed . ') AS processed'
			)
		  );

		$db->setQuery($query);
		$progressCounters = $db->loadAssoc();

		return $progressCounters;
	}

	/**
	 * Get all the strings related to this field
	 *
	 * @param   array|null $recordId Record id to just load that row
	 *
	 * @return array
	 */
	protected function getStrings($recordId = NULL)
	{
		$rows       = array();
		$primaryKey = $this->getTable()->getPrimaryKey();

		// If the table has primary key, let's go through them
		if (!empty($primaryKey))
		{
			$db             = JFactory::getDbo();
			$query          = $db->getQuery(true);
			$filtersApplied = false;

			$primaryKeyData = $this->getTable()->getPrimaryKey();
			$breakpoint     = NenoSettings::get('installation_completed') ? NULL : NenoSettings::get('field_breakpoint', NULL);

			if (!empty($breakpoint))
			{
				$breakpoint = json_decode($breakpoint, true);
			}

			foreach ($primaryKeyData as $primaryKey)
			{
				$query->select($db->quoteName($primaryKey));

				if (!empty($recordId[$primaryKey]))
				{
					$query->where($db->quoteName($primaryKey) . ' = ' . $recordId[$primaryKey]);
					$filtersApplied = true;
				}
				elseif (!empty($breakpoint[$primaryKey]))
				{
					$query->where($db->quoteName($primaryKey) . ' >= ' . $breakpoint[$primaryKey]);
					$filtersApplied = true;
				}

				$query->order($db->quoteName($primaryKey) . ' ASC');
			}

			$query
			  ->select($db->quoteName($this->getFieldName(), 'string'))
			  ->from($this->getTable()->getTableName());

			if ($this->getTable()->hasState())
			{
				$query->select('state');
			}
			else
			{
				$query->select('1 AS state');
			}

			// If there's no filter applied, let's applied the ones for the tables
			if (!$filtersApplied && $this->getTable()->isTranslate() == 2)
			{
				$filters = $this->getTable()->getTableFilters();

				foreach ($filters as $filter)
				{
					$query->where(NenoHelper::getWhereClauseForTableFilters($filter));
				}
			}

			$db->setQuery($query);
			$rows = $db->loadAssocList();
		}

		return $rows;
	}

	/**
	 * Get a ContentElementField related to a table and field name
	 *
	 * @param   NenoContentElementTable $table     Table
	 * @param   string                  $fieldName Field name
	 *
	 * @return NenoContentElementField
	 */
	public static function getFieldByTableAndFieldName(NenoContentElementTable $table, $fieldName)
	{
		// Get fields related to this table
		$fields = $table->getFields(false);
		$field  = NULL;

		if (!empty($fields))
		{
			$fields = $table->getFields(false);
			$found  = false;

			for ($i = 0; $i < count($fields) && !$found; $i++)
			{
				/* @var $field NenoContentElementField */
				$field = $fields[$i];

				if ($field->getFieldName() == $fieldName)
				{
					$found = true;
				}
			}

			if ($found)
			{
				if ($field->getId() == NULL)
				{
					$field = self::getFieldDataFromDatabase($table->getId(), $fieldName);
				}

				return $field;
			}

			return false;
		}
		else
		{
			return self::getFieldDataFromDatabase($table->getId(), $fieldName);
		}
	}

	/**
	 * Load field from the database
	 *
	 * @param   integer $tableId   Table Id
	 * @param   string  $fieldName Field name
	 *
	 * @return NenoContentElementField
	 */
	private static function getFieldDataFromDatabase($tableId, $fieldName)
	{
		$field = self::load(array('table_id'   => $tableId,
		                          'field_name' => $fieldName
		));

		return $field;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function persist()
	{
		if ($this->isNew())
		{
			$this->filter = 'RAW';

			// If this field name has a established filter, let's set it
			if (isset(self::$filterMapByFieldName[strtolower($this->fieldName)]))
			{
				$this->filter = self::$filterMapByFieldName[strtolower($this->fieldName)];
			}
		}

		return parent::persist();
	}

	/**
	 * Check if the field has been discovered already
	 *
	 * @return boolean
	 */
	public function isDiscovered()
	{
		return $this->discovered;
	}

	/**
	 * Set discovered flag
	 *
	 * @param   boolean $discovered Discovered flag
	 *
	 * @return $this
	 */
	public function setDiscovered($discovered)
	{
		$this->discovered = $discovered;

		return $this;
	}
}
