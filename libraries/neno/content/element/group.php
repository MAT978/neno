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
 * Class NenoContentElementGroup
 *
 * @since  1.0
 */
class NenoContentElementGroup extends NenoContentElement implements NenoContentElementInterface
{
	/**
	 * @var array|null
	 */
	public $assignedTranslationMethods;
	/**
	 * @var int
	 */
	public $elementCount;
	/**
	 * @var stdClass
	 */
	public $wordCount;
	/**
	 * @var array|null
	 */
	public $languageFiles;
	/**
	 * @var
	 */
	public $extensions;
	/**
	 * @var string
	 */
	protected $groupName;
	/**
	 * @var array|null
	 */
	protected $tables;
	/**
	 * @var bool
	 */
	protected $otherGroup;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Group data
	 * @param   bool  $loadExtraData Load extra data flag
	 */
	public function __construct($data, $loadExtraData = true)
	{
		parent::__construct($data);

		$this->tables                     = NULL;
		$this->languageFiles              = NULL;
		$this->assignedTranslationMethods = array();
		$this->extensions                 = array();
		$this->elementCount               = NULL;
		$this->wordCount                  = NULL;

		// Only search for the statistics for existing groups
		if (!$this->isNew())
		{
			$this->getExtensionIdList();
			$this->getElementCount();
			$this->calculateExtraData();

			if ($loadExtraData)
			{
				$this->getWordCount();
			}
		}
	}

	/**
	 * Get extension list
	 *
	 * @return void
	 */
	protected function getExtensionIdList()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('extension_id')
		  ->from('#__neno_content_element_groups_x_extensions')
		  ->where('group_id = ' . $this->id);

		$db->setQuery($query);
		$this->extensions = $db->loadArray();
	}

	/**
	 * Get how many tables this group has
	 *
	 * @return int
	 */
	public function getElementCount()
	{
		if ($this->elementCount === NULL)
		{
			$tableCounter = NenoContentElementTable::load(
			  array(
				'_select'  => array('COUNT(*) as counter'),
				'group_id' => $this->getId()
			  )
			);

			$languageFileCounter = NenoContentElementLanguageFile::load(
			  array(
				'_select'  => array('COUNT(*) as counter'),
				'group_id' => $this->getId()
			  )
			);

			$this->elementCount = (int) $tableCounter['counter'] + (int) $languageFileCounter['counter'];
		}

		return $this->elementCount;
	}

	/**
	 * Calculate language string statistics
	 *
	 * @return void
	 */
	public function calculateExtraData()
	{
		$this->assignedTranslationMethods = array();

		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('DISTINCT tm.*')
		  ->from('#__neno_content_element_groups_x_translation_methods AS gt')
		  ->innerJoin('#__neno_translation_methods AS tm ON gt.translation_method_id = tm.id')
		  ->where(
			array(
			  'group_id = ' . $this->id,
			  'lang = ' . $db->quote(NenoHelper::getWorkingLanguage())
			)
		  )
		  ->group('ordering')
		  ->order('ordering ASC');

		$db->setQuery($query);
		$this->assignedTranslationMethods = $db->loadObjectList();
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
			$db              = JFactory::getDbo();
			$query           = $db->getQuery(true);
			$workingLanguage = NenoHelper::getWorkingLanguage();
			$query
			  ->select(
				array(
				  'SUM(word_counter) AS counter',
				  't.state'
				)
			  )
			  ->from($db->quoteName(NenoContentElementLanguageString::getDbTable()) . ' AS ls')
			  ->innerJoin($db->quoteName(NenoContentElementLanguageFile::getDbTable()) . ' AS lf ON ls.languagefile_id = lf.id')
			  ->innerJoin(
				$db->quoteName(NenoContentElementTranslation::getDbTable()) .
				' AS t ON t.content_id = ls.id AND t.content_type = ' .
				$db->quote('lang_string') .
				' AND t.language LIKE ' . $db->quote($workingLanguage)
			  )
			  ->where('lf.group_id = ' . $this->getId())
			  ->group('t.state');

			$db->setQuery($query);
			$statistics = $db->loadAssocList('state');

			$this->wordCount = $this->generateWordCountObjectByStatistics($statistics);

			$query
			  ->clear()
			  ->select(
				array(
				  'SUM(word_counter) AS counter',
				  'tr.state'
				)
			  )
			  ->from('#__neno_content_element_tables AS t')
			  ->innerJoin('#__neno_content_element_fields AS f ON f.table_id = t.id')
			  ->innerJoin('#__neno_content_element_translations AS tr  ON tr.content_id = f.id AND tr.content_type = ' . $db->quote('db_string') . ' AND tr.language LIKE ' . $db->quote($workingLanguage))
			  ->where(
				array(
				  't.group_id = ' . $this->getId(),
				  't.translate IN (1,2)',
				  'f.translate = 1'
				)
			  )
			  ->group('tr.state');

			$db->setQuery($query);
			$statistics = $db->loadAssocList('state');

			// Assign the statistics
			foreach ($statistics as $state => $data)
			{
				switch ($state)
				{
					case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
						$this->wordCount->untranslated = (int) $data['counter'] + $this->wordCount->untranslated;
						break;
					case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
						$this->wordCount->queued = (int) $data['counter'] + $this->wordCount->queued;
						break;
					case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
						$this->wordCount->changed = (int) $data['counter'] + $this->wordCount->changed;
						break;
					case NenoContentElementTranslation::TRANSLATED_STATE:
						$this->wordCount->translated = (int) $data['counter'] + $this->wordCount->translated;
						break;
				}
			}

			$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;
		}

		return $this->wordCount;
	}

	/**
	 * Get a group object
	 *
	 * @param   integer $groupId       Group Id
	 * @param   bool    $loadExtraData Load extra data flag
	 *
	 * @return NenoContentElementGroup
	 */
	public static function getGroup($groupId, $loadExtraData = true)
	{
		$group = self::load($groupId, $loadExtraData);

		return $group;
	}

	/**
	 * Add a table to the list
	 *
	 * @param   NenoContentElementTable $table Table
	 *
	 * @return $this
	 */
	public function addTable(NenoContentElementTable $table)
	{
		$this->tables[] = $table;

		return $this;
	}

	/**
	 * Get all the tables related to this group
	 *
	 * @param   bool $loadExtraData           Calculate other data
	 * @param   bool $loadTablesNotDiscovered Only loads tables that have not been discovered yet
	 * @param   bool $avoidDoNotTranslate     Don't load tables marked as Don't translate
	 *
	 * @return array
	 */
	public function getTables($loadExtraData = true, $loadTablesNotDiscovered = false, $avoidDoNotTranslate = false)
	{
		if ($this->tables === NULL || $loadTablesNotDiscovered)
		{
			if ($loadTablesNotDiscovered)
			{
				$this->tables = NenoHelper::getComponentTables($this, NULL, false);
			}
			else
			{
				$this->tables = NenoContentElementTable::load(array('group_id' => $this->getId()), $loadExtraData);

				// If there's only one table
				if ($this->tables instanceof NenoContentElementTable)
				{
					$this->tables = array($this->tables);
				}

				/* @var $table NenoContentElementTable */
				foreach ($this->tables as $key => $table)
				{
					if ($avoidDoNotTranslate && !$this->tables[$key]->isTranslate())
					{
						unset ($this->tables[$key]);
						continue;
					}

					$this->tables[$key]->setGroup($this);
				}
			}
		}

		return $this->tables;
	}

	/**
	 * Set all the tables related to this group
	 *
	 * @param   array $tables Tables
	 *
	 * @return $this
	 */
	public function setTables(array $tables)
	{
		$this->tables = $tables;
		$this->contentHasChanged();

		return $this;
	}

	/**
	 * Persist extension data
	 *
	 * @return void
	 */
	protected function persistExtensionData()
	{
		if (!empty($this->extensions))
		{
			$db          = JFactory::getDbo();
			$deleteQuery = $db->getQuery(true);

			$deleteQuery
			  ->delete('#__neno_content_element_groups_x_extensions')
			  ->where('group_id = ' . $this->getId());

			$db->setQuery($deleteQuery);
			$db->execute();

			$insertQuery = $db->getQuery(true);

			$insertQuery
			  ->clear()
			  ->insert('#__neno_content_element_groups_x_extensions')
			  ->columns(
				array(
				  'extension_id',
				  'group_id'
				)
			  );

			foreach ($this->extensions as $extension)
			{
				$insertQuery->values((int) $extension . ',' . $this->getId());
			}

			$db->setQuery($insertQuery);
			$db->execute();
		}
	}

	/**
	 * Get translation methods for group based on its tables
	 *
	 * @return void
	 */
	protected function getTranslationMethodBasedOnTables()
	{
		// Check whether or not this group should have translation methods (For unknown groups we set them as do not translate)
		if (!empty($this->tables) && is_array($this->tables))
		{
			$fileFound = false;
			/* @var $table NenoContentElementTable */
			foreach ($this->tables as $table)
			{
				if (file_exists($table->getContentElementFilename()))
				{
					$fileFound = true;
					break;
				}
			}

			// if it has no file, let's assign manual translation.
			if (!$fileFound)
			{
				$this->assignedTranslationMethods = array();
				$languages                        = NenoHelper::getLanguages(false);

				foreach ($languages as $language)
				{
					$translationMethod                        = new stdClass;
					$translationMethod->lang                  = $language->lang_code;
					$translationMethod->translation_method_id = 1;
					$this->assignedTranslationMethods[]       = $translationMethod;
				}
			}
		}
	}

	/**
	 * Persist translation method data
	 *
	 * @return void
	 */
	protected function persistTranslationMethodData()
	{
		$this->getTranslationMethodBasedOnTables();

		if (!empty($this->assignedTranslationMethods))
		{
			$db          = JFactory::getDbo();
			$deleteQuery = $db->getQuery(true);
			$insertQuery = $db->getQuery(true);
			$insert      = false;

			$insertQuery
			  ->insert('#__neno_content_element_groups_x_translation_methods')
			  ->columns(
				array(
				  'group_id',
				  'lang',
				  'translation_method_id',
				  'ordering'
				)
			  );

			foreach ($this->assignedTranslationMethods as $translationMethod)
			{
				if (!empty($translationMethod->lang))
				{
					$deleteQuery
					  ->clear()
					  ->delete('#__neno_content_element_groups_x_translation_methods')
					  ->where(
						array(
						  'group_id = ' . $this->id,
						  'lang = ' . $db->quote($translationMethod->lang)
						)
					  );

					$db->setQuery($deleteQuery);
					$db->execute();

					if (!empty($translationMethod))
					{
						$insert = true;
						$insertQuery->values(
						  $this->id . ',' . $db->quote($translationMethod->lang) . ', ' . $db->quote($translationMethod->translation_method_id) . ', ' . $db->quote($translationMethod->ordering)
						);
					}
				}
			}

			if ($insert)
			{
				$db->setQuery($insertQuery);
				$db->execute();
			}
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return boolean
	 */
	public function persist()
	{
		$isNew  = $this->isNew();
		$result = parent::persist();

		// Check if the saving process has been completed successfully
		if ($result)
		{
			$this->persistExtensionData();

			if ($isNew)
			{
				$this->persistTranslationMethodData();
			}

			if (!empty($this->languageFiles))
			{
				/* @var $languageFile NenoContentElementLanguageFile */
				foreach ($this->languageFiles as $languageFile)
				{
					$languageFile->setGroup($this);
					$languageFile->persist();
				}
			}

			if (!empty($this->tables))
			{
				/* @var $table NenoContentElementTable */
				foreach ($this->tables as $table)
				{
					$table->setGroup($this);
					$table->persist();
				}
			}
		}

		return $result;
	}

	/**
	 * Create a NenoContentElementGroup based on the extension Id
	 *
	 * @param   integer $extensionId Extension Id
	 *
	 * @return NenoContentElementGroup
	 */
	public static function createNenoContentElementGroupByExtensionId($extensionId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			array(
			  'e.extension_id',
			  'e.name'
			)
		  )
		  ->from('`#__extensions` AS e')
		  ->where('e.extension_id = ' . (int) $extensionId);

		$db->setQuery($query);

		$extension = $db->loadAssoc();
		$group     = new NenoContentElementGroup(
		  array(
			'groupName'   => $extension['name'],
			'extensionId' => $extension['extension_id']
		  )
		);

		return $group;
	}

	/**
	 * Refresh NenoContentElementGroup data
	 *
	 * @param   string|null $language Language to update. Null for none
	 *
	 * @return void
	 */
	public function refresh($language = NULL)
	{
		$tables        = NenoHelper::getComponentTables($this);
		$languageFiles = array();

		if (!$this->isOtherGroup())
		{
			$elementName   = NenoHelper::getElementNameByGroupId($this->id);
			$languageFiles = NenoHelper::getLanguageFiles($elementName);
		}

		// If there are tables, let's assign to the group
		if (!empty($tables))
		{
			$this->setTables($tables);
		}

		// If there are language strings, let's assign to the group
		if (!empty($languageFiles))
		{
			$this->setLanguageFiles($languageFiles);
		}

		$this->sync();

		// If there are tables or language strings assigned, save the group
		if (!empty($tables) || !empty($languageFiles))
		{
			$this->persist();
		}

		$this->saveDatabaseTranslations($tables, $language);
		$this->saveLanguageFileTranslations($languageFiles, $language);
	}

	/**
	 * Save database translations for a particular language
	 *
	 * @param array  $tables   Database Tables
	 * @param string $language Language tag
	 *
	 * @return void
	 */
	protected function saveDatabaseTranslations($tables, $language)
	{
		if (!empty($tables))
		{
			/* @var $table NenoContentElementTable */
			foreach ($tables as $table)
			{
				if ($table->isTranslate())
				{
					$fields = $table->getFields(false, true);

					/* @var $field NenoContentElementField */
					foreach ($fields as $field)
					{
						$field->persistTranslations(NULL, $language);
					}
				}
			}
		}
	}

	/**
	 * Save language file translations for a particular language
	 *
	 * @param array  $languageFiles Language files
	 * @param string $language      Language tag
	 *
	 * @return void
	 */
	public function saveLanguageFileTranslations($languageFiles, $language)
	{
		if (!empty($languageFiles))
		{
			/* @var $languageFile NenoContentElementLanguageFile */
			foreach ($languageFiles as $languageFile)
			{
				if ($languageFile->isTranslate())
				{
					if ($languageFile->loadStringsFromFile())
					{
						$languageStrings = $languageFile->getLanguageStrings(true, $language);

						/* @var $languageString NenoContentElementLanguageString */
						foreach ($languageStrings as $languageString)
						{
							$languageString->persistTranslations($language);
						}
					}
				}
			}
		}
	}

	/**
	 * Check if it's other group
	 *
	 * @return boolean
	 */
	public function isOtherGroup()
	{
		return $this->otherGroup;
	}

	/**
	 * @param boolean $otherGroup
	 */
	public function setOtherGroup($otherGroup)
	{
		$this->otherGroup = $otherGroup;
	}

	/**
	 * Get group name
	 *
	 * @return string
	 */
	public function getGroupName()
	{
		return $this->groupName;
	}

	/**
	 * Set the group name
	 *
	 * @param   string $groupName Group name
	 *
	 * @return NenoContentElementGroup
	 */
	public function setGroupName($groupName)
	{
		$this->groupName = $groupName;

		return $this;
	}

	/**
	 * Sync group level hierarchy
	 *
	 * @return void
	 */
	public function sync()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db     = JFactory::getDbo();
		$tables = $db->getNenoTableList();

		foreach ($tables as $key => $table)
		{
			$tables[$key] = str_replace($db->getPrefix(), '#__', $table);
		}

		$query = $db->getQuery(true);
		$query
		  ->select('id')
		  ->from('#__neno_content_element_tables')
		  ->where(
			array(
			  'table_name NOT IN (' . implode(',', $db->quote($tables)) . ')',
			  'group_id = ' . (int) $this->id
			)
		  );

		$db->setQuery($query);
		$tableIds        = $db->loadArray();
		$reArrangeTables = false;

		foreach ($tableIds as $tableId)
		{
			/* @var $table NenoContentElementTable */
			$table = NenoContentElementTable::load($tableId);

			if (!empty($table) && $table->remove() && empty($this->tables))
			{
				/* @var $tableObject NenoContentElementTable */
				foreach ($this->tables as $key => $tableObject)
				{
					if ($tableObject->getId() == $tableId)
					{
						$reArrangeTables = true;
						unset($this->tables[$key]);
					}
				}
			}
		}

		if ($reArrangeTables)
		{
			$this->tables = array_values($this->tables);
		}

		if (empty($this->languageFiles))
		{
			/** @var  $file NenoContentElementLanguageFile */
			foreach ($this->languageFiles as $file)
			{
				$file->loadStringsFromFile();
				$file->getLanguageStrings();
			}
		}
	}

	/**
	 * Get Translation methods used.
	 *
	 * @return array
	 */
	public function getAssignedTranslationMethods()
	{
		if (empty($this->assignedTranslationMethods))
		{
			$this->calculateExtraData();
		}

		return $this->assignedTranslationMethods;
	}

	/**
	 * Copy translation methods from one language to another
	 *
	 * @param string $fromLanguage Language you are copying translation methods from
	 * @param string $toLanguage   Language you are copying translation methods to.
	 *
	 * @return boolean
	 */
	public function copyTranslationMethodFromLanguage($fromLanguage, $toLanguage)
	{
		$db    = JFactory::getDbo();
		$query = 'INSERT IGNORE INTO #__neno_content_element_groups_x_translation_methods (`group_id`,`lang`,`translation_method_id`,`ordering`) SELECT group_id, ' . $db->quote($toLanguage) . ', translation_method_id, ordering FROM #__neno_content_element_groups_x_translation_methods WHERE lang = ' . $db->quote($fromLanguage);
		$db->setQuery($query);

		return $db->execute() !== false;
	}

	/**
	 * Set translation methods used
	 *
	 * @param   array $assignedTranslationMethods Translation methods used
	 *
	 * @return $this
	 */
	public function setAssignedTranslationMethods(array $assignedTranslationMethods)
	{
		$this->assignedTranslationMethods = $assignedTranslationMethods;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function remove()
	{
		// Get the tables
		$tables = $this->getTables();

		/* @var $table NenoContentElementTable */
		foreach ($tables as $table)
		{
			$table->remove();
		}

		// Get language strings
		$languageStrings = $this->getLanguageFiles();

		/* @var $languageString NenoContentElementLanguageString */
		foreach ($languageStrings as $languageString)
		{
			$languageString->remove();
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
		  ->delete('#__neno_content_element_groups_x_translation_methods')
		  ->where('group_id = ' . $this->id);
		$db->setQuery($query);
		$db->execute();

		$query
		  ->clear()
		  ->delete('#__neno_content_element_groups_x_extensions')
		  ->where('group_id = ' . $this->id);
		$db->setQuery($query);
		$db->execute();

		return parent::remove();
	}

	/**
	 * Get all the language files
	 *
	 * @return array
	 */
	public function getLanguageFiles()
	{
		if ($this->languageFiles === NULL)
		{
			$this->languageFiles = NenoContentElementLanguageFile::load(array('group_id' => $this->getId()));

			if (!is_array($this->languageFiles))
			{
				$this->languageFiles = array($this->languageFiles);
			}
		}

		return $this->languageFiles;
	}

	/**
	 * Set language strings
	 *
	 * @param   array $languageStrings Language strings
	 *
	 * @return $this
	 */
	public function setLanguageFiles(array $languageStrings)
	{
		$this->languageFiles = $languageStrings;
		$this->contentHasChanged();

		return $this;
	}

	/**
	 * Get a list of extensions linked to this group
	 *
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * Set a list of extensions linked to this group
	 *
	 * @param   array $extensions Extension list
	 *
	 * @return $this
	 */
	public function setExtensions(array $extensions)
	{
		$this->extensions = $extensions;

		return $this;
	}

	/**
	 * Add an extension id to the list
	 *
	 * @param   int $extensionId Extension id
	 *
	 * @return $this
	 */
	public function addExtension($extensionId)
	{
		$this->extensions[] = $extensionId;
		$this->extensions   = array_unique($this->extensions);

		return $this;
	}

	/**
	 * Generate the content for a particular language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool True on success
	 */
	public function generateContentForLanguage($languageTag)
	{
		$languageFiles = $this->getLanguageFiles();
		$tables        = $this->getTables();

		$this->saveDatabaseTranslations($tables, $languageTag);
		$this->saveLanguageFileTranslations($languageFiles, $languageTag);

		// Assign default methods
		$db = JFactory::getDbo();

		/* @var $query NenoDatabaseQueryMysqlx */
		$query = $db->getQuery(true);

		$query
		  ->insertIgnore('#__neno_content_element_groups_x_translation_methods')
		  ->columns(
			array(
			  'group_id',
			  'lang',
			  'translation_method_id',
			  'ordering'
			)
		  );

		$firstTranslationMethod = NenoSettings::get('translation_method_1');
		$query->values($this->id . ',' . $db->quote($languageTag) . ', ' . $db->quote($firstTranslationMethod) . ', 1');

		$queryTranslations1 = 'INSERT IGNORE INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering)
							SELECT id, ' . $db->quote($firstTranslationMethod) . ',1 FROM #__neno_content_element_translations
							WHERE language = ' . $db->quote($languageTag) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE;

		$secondTranslationMethod = NenoSettings::get('translation_method_2');
		$queryTranslations2      = NULL;

		if (!empty($secondTranslationMethod))
		{
			$query->values($this->id . ',' . $db->quote($languageTag) . ', ' . $db->quote($secondTranslationMethod) . ', 2');
			$queryTranslations2 = 'INSERT IGNORE INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering)
							SELECT id, ' . $db->quote($secondTranslationMethod) . ',2 FROM #__neno_content_element_translations
							WHERE language = ' . $db->quote($languageTag) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE;
		}

		$db->setQuery($query);
		$db->execute();

		$db->setQuery($queryTranslations1);
		$db->execute();

		if (!empty($queryTranslations2))
		{
			$db->setQuery($queryTranslations2);
			$db->execute();
		}
	}

	/**
	 * Discover the element
	 *
	 * @return bool True on success
	 */
	public function discoverElement()
	{
		// Save the hierarchy first,
		if ($this->isNew() || NenoSettings::get('discovering_element_0') == $this->id)
		{
			NenoHelper::setSetupState(JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP', $this->groupName));
			$level = '1.1';
		}
		else
		{
			$level = '1.2';
		}

		$this->persist();

		$elementId = $this->id;

		if (empty($this->tables) && empty($this->languageFiles))
		{
			NenoHelper::setSetupState(JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_CONTENT_NOT_DETECTED', $this->getGroupName()), 1, 'warning');
			$level     = 0;
			$elementId = 0;
		}

		NenoSettings::set('installation_level', $level);
		NenoSettings::set('discovering_element_0', $elementId);
	}
}
