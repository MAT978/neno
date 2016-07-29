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
 * Class NenoContentElementTable
 *
 * @since  1.0
 */
class NenoContentElementTable extends NenoContentElement implements NenoContentElementInterface
{
	/**
	 * @var stdClass
	 */
	public $wordCount;

	/**
	 * @var NenoContentElementGroup
	 */
	protected $group;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var array
	 */
	protected $primaryKey;

	/**
	 * @var boolean
	 */
	protected $translate;

	/**
	 * @var array|null
	 */
	protected $fields;

	/**
	 * @var bool
	 */
	protected $discovered;

	/**
	 * @var int
	 */
	public $recordCount;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Table data
	 * @param   bool  $loadExtraData Load Extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);

		$data = (array) $data;

		// Make sure the name of the table is properly formatted.
		$this->tableName = NenoHelper::unifyTableName($this->tableName);

		$this->primaryKey = is_array($this->primaryKey) ? json_encode($this->primaryKey) : json_decode($this->primaryKey);

		if (!empty($data['groupId']) && $loadParent)
		{
			$this->group = NenoContentElementGroup::load($data['groupId'], $loadExtraData);
		}

		// Init the field list
		$this->fields = null;

		if (!$this->isNew())
		{
			$this->getFields($loadExtraData);

			if ($loadExtraData)
			{
				$this->getWordCount();
				$this->getRecordCount();
			}
		}
	}

	/**
	 * Loads record count
	 *
	 * @return void
	 */
	protected function getRecordCount()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('COUNT(*) AS counter')
			->from($db->quoteName($this->getTableName()));

		if ($this->translate == 2)
		{
			$filters = $this->getTableFilters();

			foreach ($filters as $filter)
			{
				$query->where(NenoHelper::getWhereClauseForTableFilters($filter));
			}
		}

		$db->setQuery($query);
		$this->recordCount = $db->loadResult();
	}

	/**
	 * Get the fields related to this table
	 *
	 * @param   bool $loadExtraData                Load Extra data flag for fields
	 * @param   bool $onlyTranslatable             Returns only the translatable fields
	 * @param   bool $onlyFieldsWithNoTranslations Returns only fields with no translations
	 * @param   bool $loadParent                   Load parent table
	 *
	 * @return array
	 */
	public function getFields($loadExtraData = false, $onlyTranslatable = false, $onlyFieldsWithNoTranslations = false, $loadParent = false)
	{
		if ($this->fields === null || $onlyFieldsWithNoTranslations)
		{
			$this->fields = array();

			if ($onlyFieldsWithNoTranslations)
			{
				/* @var $db NenoDatabaseDriverMysqlx */
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query
					->select('f.id')
					->from('#__neno_content_element_fields AS f')
					->where(
						array(
							'f.table_id = ' . $this->getId(),
							'NOT EXISTS (SELECT 1 FROM #__neno_content_element_translations AS tr WHERE tr.content_id = f.id AND tr.content_type = ' . $db->quote('db_string') . ')'
						)
					);

				if ($onlyTranslatable)
				{
					$query->where('f.translate = 1');
				}

				$db->setQuery($query);
				$fields = $db->loadArray();

				foreach ($fields as $field)
				{
					$field = NenoContentElementField::load($field);

					if (!empty($field))
					{
						$this->fields[] = $field;
					}
				}
			}
			else
			{
				$fieldsInfo      = self::getElementsByParentId(NenoContentElementField::getDbTable(), 'table_id', $this->getId(), true);
				$fieldsInfoCount = count($fieldsInfo);

				for ($i = 0; $i < $fieldsInfoCount; $i++)
				{
					$fieldInfo        = $fieldsInfo[$i];
					$fieldInfo->table = $this;
					$field            = new NenoContentElementField($fieldInfo, $loadExtraData, $loadParent);

					// Insert the field only if the $onlyTranslatable flag is off or if the flag is on and the field is translatable
					if (($field->isTranslatable() && $onlyTranslatable) || !$onlyTranslatable)
					{
						$this->fields[] = $field;
					}
				}
			}
		}
		elseif ($onlyTranslatable)
		{
			$fields = array();
			/* @var $field NenoContentElementField */
			foreach ($this->fields as $key => $field)
			{
				if ($field->isTranslatable())
				{
					$fields[] = $field;
				}
			}

			return $fields;
		}

		return $this->fields;
	}

	/**
	 * Set the fields related to this table
	 *
	 * @param   array $fields Table fields
	 *
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;

		return $this;
	}

	/**
	 * Get an object with the amount of words per state
	 *
	 * @return stdClass
	 */
	public function getWordCount()
	{
		if ($this->wordCount === null)
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
				->from($db->quoteName(NenoContentElementField::getDbTable(), 'f'))
				->innerJoin(
					$db->quoteName(NenoContentElementTranslation::getDbTable(), 'tr') .
					' ON tr.content_id = f.id AND tr.content_type = ' .
					$db->quote('db_string') .
					' AND tr.language LIKE ' . $db->quote($workingLanguage)
				)
				->where(
					array(
						'f.table_id = ' . $this->getId(),
						'f.translate = 1'
					)
				)
				->group('tr.state');

			$db->setQuery($query);
			$statistics = $db->loadAssocList('state');

			$this->wordCount        = $this->generateWordCountObjectByStatistics($statistics);
			$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;
		}

		return $this->wordCount;
	}

	/**
	 * Load a table using its ID
	 *
	 * @param   integer $tableId Table Id
	 *
	 * @return bool|NenoContentElementTable
	 */
	public static function getTableById($tableId)
	{
		$table = self::load($tableId);

		return $table;
	}

	/**
	 * Get the group that contains this table
	 *
	 * @return NenoContentElementGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * Set the group that contains this table
	 *
	 * @param   NenoContentElementGroup $group Group
	 *
	 * @return $this
	 */
	public function setGroup(NenoContentElementGroup $group)
	{
		$this->group = $group;

		return $this;
	}

	/**
	 * Get Primary key
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		if (!is_array($this->primaryKey))
		{
			$this->primaryKey = (array) json_decode($this->primaryKey);
		}

		return $this->primaryKey;
	}

	/**
	 * Set Primary key
	 *
	 * @param   array $primaryKey Primary keys
	 *
	 * @return $this
	 */
	public function setPrimaryKey(array $primaryKey)
	{
		$this->primaryKey = $primaryKey;

		return $this;
	}

	/**
	 * Check if a table has been marked as translatable
	 *
	 * @return boolean
	 */
	public function hasBeenMarkedAsTranslatable()
	{
		return $this->translate;
	}

	/**
	 * Mark a table as translatable or not.
	 *
	 * @param   boolean $translate If the table needs to be translated
	 *
	 * @return $this
	 */
	public function markAsTranslatable($translate)
	{
		$this->translate = $translate;

		return $this;
	}

	/**
	 * Add a field to the field list.
	 *
	 * @param   NenoContentElementField $field Field
	 *
	 * @return NenoContentElementTable
	 */
	public function addField(NenoContentElementField $field)
	{
		$this->fields[] = $field;

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
		$object = parent::toObject($allFields, $recursive, $convertToDatabase);

		if (!empty($this->group) && $convertToDatabase)
		{
			$object->group_id = $this->group->getId();
		}

		// If it's an array, let's json it!
		if (is_array($this->primaryKey) && $convertToDatabase)
		{
			$object->primary_key = json_encode($this->primaryKey);
		}

		return $object;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function remove()
	{
		$fields = $this->getFields();

		// Delete all the translations first
		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			$field->removeTranslations();
		}

		// The delete the field itself
		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			$field->remove();
		}

		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();
		$db->deleteShadowTables($this->getTableName());

		return parent::remove();
	}

	/**
	 * Get Table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * Set Table name
	 *
	 * @param   string $tableName Table name
	 *
	 * @return $this
	 */
	public function setTableName($tableName)
	{
		$this->tableName = $tableName;

		return $this;
	}

	/**
	 * Discover the element
	 *
	 * @param bool $leafLevels Go deeper into the hierarchy.
	 *
	 * @return bool True on success
	 */
	public function discoverElement($leafLevels = true)
	{
		NenoHelper::setSetupState(
			JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP_TABLE', $this->group->getGroupName(), $this->getTableName()), 2
		);

		if ($leafLevels)
		{
			if ($this->translate)
			{
				// Check if there are children not discovered
				$field = NenoContentElementField::load(array(
					'discovered' => 0,
					'table_id'   => $this->id,
					'_limit'     => 1,
					'translate'  => 1
				));

				if (!empty($field))
				{
					NenoSettings::set('installation_level', '2.1');
					NenoSettings::set('discovering_element_1.1', $this->id);
				}
				else
				{
					NenoSettings::set('discovering_element_1.1', 0);
					$this
						->setDiscovered(true)
						->persist();
				}
			}
			else
			{
				NenoHelper::setSetupState(
					JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_TABLE_TOO_MANY_RECORDS', $this->group->getGroupName(), $this->getTableName()), 2, 'error'
				);
			}
		}
		else
		{
			NenoSettings::set('discovering_element_1.1', 0);
			$this
				->setDiscovered(true)
				->persist();
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return boolean
	 */
	public function persist()
	{
		$isNew = $this->isNew();

		if ($isNew && $this->translate)
		{
			$this->checkTranslatableStatus();
		}

		$result = parent::persist();

		if ($result)
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db = JFactory::getDbo();

			// If the table has been marked as translated
			if ($this->translate)
			{
				// Creates shadow tables and copy the content on it
				$db->createShadowTables($this->tableName);

				if (!$isNew)
				{
					// Sync existing tables,
					$db->syncTable($this->tableName);
				}
			}

			if (!empty($this->fields))
			{
				/* @var $field NenoContentElementField */
				foreach ($this->fields as $field)
				{
					$field
						->setTable($this)
						//->setTranslate($field->isTranslatable() && $this->isTranslate())
						->persist();

					if ($field->getFieldName() === 'language' && $this->isTranslate())
					{
						$languages       = NenoHelper::getTargetLanguages();
						$defaultLanguage = NenoSettings::get('source_language');

						foreach ($languages as $language)
						{
							if ($language->lang_code != $defaultLanguage)
							{
								$db->deleteContentElementsFromSourceTableToShadowTables($this->tableName, $language->lang_code);
							}
						}

						$db->setContentForAllLanguagesToSourceLanguage($this->tableName, $defaultLanguage);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Check if the table is translatable
	 *
	 * @return boolean
	 */
	public function isTranslate()
	{
		return $this->translate;
	}

	/**
	 * Mark this table as translatable/untranslatable
	 *
	 * @param   boolean $translate Translation status
	 * @param   boolean $force     Force the status
	 *
	 * @return $this
	 */
	public function setTranslate($translate, $force = false)
	{
		$this->translate = $translate;

		// If the table has been marked as translatable, let's check for the content element file
		if ($this->translate && !$force)
		{
			$this->checkTranslatableStatus();
		}

		return $this;
	}

	/**
	 * Check if the table should be translatable
	 *
	 * @return void
	 */
	public function checkTranslatableStatus()
	{
		$filePath = $this->getContentElementFilename();

		if (file_exists($filePath))
		{
			$xml             = simplexml_load_file($filePath);
			$this->translate = ((int) $xml->translate) == 1;
		}
		else // Let's have a look to the table name
		{
			$this->translate = !(int) preg_match('/(log_)|(_log)[^a-zA-Z1-9]/', $this->tableName);

			// Let's check the amount of records
			if ($this->translate)
			{
				$this->getRecordCount();

				$this->translate = $this->recordCount < 1000;
			}
		}
	}

	/**
	 * Get content element filename
	 *
	 * @return string
	 */
	public function getContentElementFilename()
	{
		return NenoHelperFile::getContentElementFilePathBasedOnTableName($this->tableName);
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

	/**
	 * Check if a table has a state field
	 *
	 * @return bool
	 */
	public function hasState()
	{
		$fields = $this->getFields(false);
		$found  = false;

		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			if ($field->getFieldName() == 'state')
			{
				$found = true;
				break;
			}
		}

		return $found;
	}

	/**
	 * Get primary keys field
	 *
	 * @return array
	 */
	public function getPrimaryKeys()
	{
		$primaryKeys = array();

		foreach ($this->primaryKey as $primaryKey)
		{
			$primaryKeys[] = NenoContentElementField::load(
				array(
					'field_name' => $primaryKey,
					'table_id'   => $this->id
				)
			);
		}

		return $primaryKeys;
	}

	/**
	 * Sync table hierarchy with the content in the database
	 *
	 * @return void
	 */
	public function sync()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();

		$fieldsData = $db->getTableColumns($this->tableName);
		$fieldNames = array_keys($fieldsData);

		$query = $db->getQuery(true);

		// Check if any fields have been added
		$query
			->select('field_name')
			->from('#__neno_content_element_fields')
			->where('table_id = ' . (int) $this->id);

		$db->setQuery($query);
		$existingFieldsDiscovered = $db->loadColumn();

		$fieldsNotDiscovered = array_diff($fieldNames, $existingFieldsDiscovered);

		if (!empty($fieldsNotDiscovered))
		{
			foreach ($fieldsNotDiscovered as $fieldNotDiscovered)
			{
				$field = NenoHelperBackend::createFieldInstance($fieldNotDiscovered, $fieldsData[$fieldNotDiscovered], $this);

				// If this field has been saved on the database correctly, let's persist its content
				if ($field->persist())
				{
					$field->persistTranslations();
				}
			}

		}

		// Check if any fields have been removed
		$query
			->clear()
			->select('id')
			->from('#__neno_content_element_fields')
			->where(
				array(
					'field_name NOT IN (' . implode(',', $db->quote($fieldNames)) . ')',
					'table_id = ' . (int) $this->id
				)
			);

		$db->setQuery($query);
		$fieldIds        = $db->loadArray();
		$reArrangeFields = false;

		foreach ($fieldIds as $fieldId)
		{
			/* @var $field NenoContentElementField */
			$field = NenoContentElementField::load($fieldId);

			if (!empty($field))
			{
				if ($field->remove())
				{
					if (!empty($this->fields))
					{
						foreach ($this->fields as $key => $field)
						{
							if ($field->getId() == $fieldId)
							{
								$reArrangeFields = true;
								unset($this->fields[$key]);
								break;
							}
						}
					}
				}
			}
		}

		if ($reArrangeFields)
		{
			$this->fields = array_values($this->fields);
		}

		$db->syncTable($this->tableName);
	}

	public function checkIntegrity($language = null)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db           = JFactory::getDbo();
		$tableColumns = array_keys($db->getTableColumns($this->tableName));

		$languages = array();

		// If a language was passed, get languages otherwise
		if ($language !== null)
		{
			$languages[] = $language;
		}
		else
		{
			$languagesKnown = NenoHelper::getLanguages(false);

			foreach ($languagesKnown as $languageKnown)
			{
				$languages[] = $languageKnown->lang_code;
			}
		}

		if (in_array('language', $tableColumns))
		{
			foreach ($languages as $language)
			{
				$shadowTable = $db->generateShadowTableName($this->tableName, $language);

				$query = $db->getQuery(true);
				$query
					->update($db->quoteName($shadowTable))
					->set('language = ' . $db->quote($language));

				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Apply filter to existing translations
	 *
	 * @return void
	 */
	public function applyFiltersToExistingContent()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('t.id')
			->from('#__neno_content_element_translations AS t')
			->where(
				array(
					't.content_type = ' . $db->quote('db_string'),
					'EXISTS (SELECT 1 FROM #__neno_content_element_fields AS f WHERE t.content_id = f.id AND f.table_id = ' . (int) $this->id . ')'
				)
			);

		$db->setQuery($query);
		$translationIds = $db->loadColumn();
		$filters        = $this->getTableFilters();

		foreach ($translationIds as $translationId)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationId, false, true);
			$sqlQuery    = $translation->generateSqlStatement();

			$sqlQuery
				->clear('select')
				->select('1');

			foreach ($filters as $filter)
			{
				$sqlQuery->where(NenoHelper::getWhereClauseForTableFilters($filter));
			}

			$db->setQuery($sqlQuery);
			$result = $db->loadResult();

			// If the translation does not meet this requirements, let's delete it
			if (empty($result))
			{
				$translation->remove();
			}
		}
	}

	/**
	 * Get table filters
	 *
	 * @return array
	 */
	public function getTableFilters()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->clear()
			->select(
				array(
					'f.field_name AS field',
					'comparaison_operator AS operator',
					'filter_value AS value'
				)
			)
			->from('#__neno_content_element_table_filters AS tf')
			->innerJoin('#__neno_content_element_fields AS f ON tf.field_id = f.id')
			->where('tf.table_id = ' . (int) $this->id);

		$db->setQuery($query);
		$filters = $db->loadAssocList();

		return $filters;
	}

	/**
	 * Loads random records from this table
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getRandomContentFromTable($limit = 3)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$this->getFields();

		if (!empty($this->fields))
		{
			/* @var $field NenoContentElementField */
			foreach ($this->fields as $field)
			{
				$query->select($db->quoteName($field->getFieldName()));
			}
		}

		$query
			->from($db->quoteName($this->getTableName()))
			->order('RAND()');

		$db->setQuery($query, 0, $limit);

		return $db->loadObjectList();
	}

	/**
	 * This method will consolidate translations method the translations.
	 *
	 * @return void
	 */
	public function consolidateTranslateMethodsForTranslations()
	{
		$db               = JFactory::getDbo();
		$translationQuery = $db->getQuery(true);
		$workingLanguage  = NenoHelper::getWorkingLanguage();

		$translationQuery
			->select('tr.id')
			->from('#__neno_content_element_translations as tr')
			->innerJoin('#__neno_content_element_fields as f ON f.id = tr.content_id')
			->where(
				array(
					'f.table_id = ' . $db->quote($this->id),
					'tr.content_type = ' . $db->quote(NenoContentElementTranslation::DB_STRING),
					'tr.language = ' . $db->quote($workingLanguage)
				)
			);

		$deleteQuery = $db->getQuery(true);

		$deleteQuery
			->clear()
			->delete('#__neno_content_element_translation_x_translation_methods')
			->where('translation_id IN (' . (string) $translationQuery . ')');

		$db->setQuery($deleteQuery);

		// If everything has been executed properly, let's insert new translation methods
		if ($db->execute() !== false)
		{
			$translationQuery
				->select(
					array(
						'gtm.translation_method_id',
						'gtm.ordering'
					)
				)
				->innerJoin('#__neno_content_element_tables as t ON t.id = f.table_id')
				->innerJoin('#__neno_content_element_groups AS g ON t.group_id = g.id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON gtm.group_id = g.id')
				->where('gtm.lang = ' . $db->quote($workingLanguage));

			$insertQuery = 'INSERT INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering) ' . (string) $translationQuery;

			$db->setQuery($insertQuery);
			$db->execute();
		}
	}

	public function optimizeTranslations()
	{
		$fields   = $this->getPrimaryKeys();
		$fieldIds = array();
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);
		$subQuery = $db->getQuery(true);

		$subQuery
			->select(1)
			->from($db->quoteName($this->getTableName()) . ' AS t');

		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			$fieldIds[] = $field->getId();
			$subQuery->where('t.' . $db->quoteName($field->getFieldName()) . ' = ft.value', 'OR');
		}

		$query
			->select('translation_id')
			->from('#__neno_content_element_fields_x_translations AS ft')
			->where(
				array(
					'field_id IN (' . implode(',', $db->quote($fieldIds)) . ')',
					'NOT EXISTS (' . ((string) $subQuery) . ')'
				)
			);

		$db->setQuery($query);
		$translationIds = $db->loadColumn();

		foreach ($translationIds as $translationId)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationId);

			if (!empty($translation))
			{
				$translation->remove();
			}
		}

		return !empty($translationIds);
	}
}
