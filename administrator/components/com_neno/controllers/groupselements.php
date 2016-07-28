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
class NenoControllerGroupsElements extends JControllerAdmin
{
	/**
	 * Method to import tables that need to be translated
	 *
	 * @return void
	 */
	public function discoverExtensions()
	{
		// Check all the extensions that haven't been discover yet
		NenoHelperBackend::groupingTablesNotDiscovered();

		$this
		  ->setRedirect('index.php?option=com_neno&view=groupselements')
		  ->redirect();
	}

	/**
	 * Enable/Disable a database table to be translate
	 *
	 * @return void
	 */
	public function enableDisableContentElementTable()
	{
		$input = JFactory::getApplication()->input;

		$tableId         = $input->getInt('tableId');
		$translateStatus = $input->getBool('translateStatus');

		$table  = NenoContentElementTable::getTableById($tableId);
		$result = 0;

		// If the table exists, let's work with it.
		if ($table !== false)
		{
			$table->markAsTranslatable($translateStatus);
			$table->persist();

			$result = 1;
		}

		echo $result;
		JFactory::getApplication()->close();
	}

	/**
	 * Toggle field translate field
	 *
	 * @return void
	 */
	public function toggleContentElementField()
	{
		$input = JFactory::getApplication()->input;

		$fieldId         = $input->getInt('fieldId');
		$translateStatus = $input->getBool('translateStatus');

		/* @var $field NenoContentElementField */
		$field = NenoContentElementField::load($fieldId, false, true);

		// If the table exists, let's work with it.
		if ($field !== false)
		{
			$field->setTranslate($translateStatus);

			if ($field->persist() === false)
			{
				NenoLog::log('Error saving new field state!', '', 0, NenoLog::PRIORITY_ERROR);
			}
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Toggle translate status for tables
	 *
	 * @return void
	 */
	public function toggleContentElementTable()
	{
		$input = JFactory::getApplication()->input;

		$tableId         = $input->getInt('tableId');
		$translateStatus = $input->getInt('translateStatus');

		/* @var $table NenoContentElementTable */
		$table = NenoContentElementTable::getTableById($tableId);

		// If the table exists, let's work with it.
		if ($table !== false)
		{
			$table->setTranslate($translateStatus, true);

			if ($table->persist() === false)
			{
				NenoLog::log('Error saving new table state!', '', 0, NenoLog::PRIORITY_ERROR);
			}
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Load table filter layout
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function getTableFilterModalLayout()
	{
		$app     = JFactory::getApplication();
		$input   = $app->input;
		$tableId = $input->getInt('tableId');

		if (!empty($tableId))
		{

			$displayData          = new stdClass;
			$displayData->filters = NenoHelperBackend::getTableFiltersData($tableId, 'filters');
			$displayData->tableId = $tableId;


			echo JLayoutHelper::render('libraries.neno.tablefilters', $displayData);
		}

		$app->close();
	}

	public function generateFieldFilterOutput()
	{
		$app     = JFactory::getApplication();
		$field   = $app->input->getInt('field');
		$tableId = $app->input->getInt('table');

		$filter = array(
		  'field' => $field,
		  'value' => ''
		);

		echo NenoHelperBackend::renderTableFilter($tableId, $filter);

		$app->close();
	}

	protected static function getFilterAttributesBasedOnFilterType($contentElementFilePath, $fieldName, $filterType)
	{
		$attributes = array();
		switch ($filterType)
		{
			case 'sql':
				$attributes['query'] = NenoHelper::getFieldAttributeFromContentElementFile($contentElementFilePath, $fieldName, 'query');

				break;
		}

		return $attributes;
	}

	/**
	 * Get elements
	 *
	 * @return void
	 */
	public function getElements()
	{
		$input   = JFactory::getApplication()->input;
		$groupId = $input->getInt('group_id');

		/* @var $group NenoContentElementGroup */
		$group                 = NenoContentElementGroup::load($groupId);
		$tables                = $group->getTables();
		$files                 = $group->getLanguageFiles();
		$displayData           = array();
		$displayData['group']  = $group->prepareDataForView();
		$displayData['tables'] = NenoHelper::convertNenoObjectListToJobjectList($tables);
		$displayData['files']  = NenoHelper::convertNenoObjectListToJobjectList($files);
		$tablesHTML            = JLayoutHelper::render('libraries.neno.rowelementtable', $displayData);

		echo $tablesHTML;

		JFactory::getApplication()->close();
	}

	public function getTranslationMethodSelector()
	{
		$app             = JFactory::getApplication();
		$input           = $this->input;
		$n               = $input->getInt('n', 0);
		$groupId         = $input->getInt('group_id');
		$selectedMethods = $input->get('selected_methods', array(), 'ARRAY');

		$translationMethods = NenoHelper::loadTranslationMethods();

		if (!empty($groupId))
		{
			$group = NenoContentElementGroup::load($groupId)
			  ->prepareDataForView();
		}
		else
		{
			$group                               = new stdClass;
			$group->assigned_translation_methods = array();
		}

		// Ensure that we know what was selected for the previous selector
		if (($n > 0 && !isset($selectedMethods[$n - 1])) || ($n > 0 && $selectedMethods[$n - 1] == 0))
		{
			JFactory::getApplication()->close();
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
			$app->close();
		}

		// Prepare display data
		$displayData                                 = array();
		$displayData['translation_methods']          = $translationMethods;
		$displayData['assigned_translation_methods'] = $group->assigned_translation_methods;
		$displayData['n']                            = $n;

		$selectorHTML = JLayoutHelper::render('libraries.neno.translationmethodselector', $displayData);

		echo $selectorHTML;

		$app->close();
	}

	/**
	 * Changing filter
	 *
	 * @return void
	 */
	public function changeFieldFilter()
	{
		$input = $this->input;
		$app   = JFactory::getApplication();

		$fieldId = $input->getInt('fieldId');
		$filter  = $input->getWord('filter');

		if (!empty($fieldId))
		{
			/* @var $field NenoContentElementField */
			$field = NenoContentElementField::load($fieldId, false, true);

			if (!empty($field))
			{
				$field
				  ->setFilter($filter)
				  ->persist();
			}
		}

		$app->close();
	}

	/**
	 * Scan for content task
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function scanForContent()
	{
		$input = $this->input;

		// Refresh content for groups
		$groups          = $input->get('groups', array(), 'ARRAY');
		$tables          = $input->get('tables', array(), 'ARRAY');
		$files           = $input->get('files', array(), 'ARRAY');
		$workingLanguage = NenoHelper::getWorkingLanguage();

		if (!empty($groups))
		{
			foreach ($groups as $groupId)
			{
				/* @var $group NenoContentElementGroup */
				$group = NenoContentElementGroup::load($groupId);

				if (!empty($group))
				{
					$group->refresh($workingLanguage);
				}
			}
		}
		elseif (!empty($tables) || !empty($files))
		{
			foreach ($tables as $tableId)
			{
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load($tableId);

				if (!empty($table) && $table->isTranslate())
				{
					// Sync table
					$table->sync();

					$fields = $table->getFields(false, true);

					if (!empty($fields))
					{
						/* @var $field NenoContentElementField */
						foreach ($fields as $field)
						{
							$field->persistTranslations(NULL, $workingLanguage);
						}
					}
				}
			}

			foreach ($files as $fileId)
			{
				/* @var $file NenoContentElementLanguageFile */
				$file = NenoContentElementLanguageFile::load($fileId);

				if (!empty($file))
				{
					$file->loadStringsFromFile();
					$languageStrings = $file->getLanguageStrings(true, true);

					if (!empty($languageStrings))
					{
						/* @var $languageString NenoContentElementLanguageString */
						foreach ($languageStrings as $languageString)
						{
							$languageString->persistTranslations($workingLanguage);
						}
					}
				}
			}
		}

		JFactory::getApplication()
		  ->redirect('index.php?option=com_neno&view=groupselements');
	}

	/**
	 * Move completed translations to the shadow tables
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function moveTranslationsToTarget()
	{
		$this->executeMethodOnTranslationListPassedByInput('moveTranslationToTarget');
	}

	protected function executeMethodOnTranslationListPassedByInput(
	  $method
	)
	{
		$translationIds = $this->getTranslationIdsListBasedOnInputParameters();

		foreach ($translationIds as $translationId)
		{
			/* @var $translation NenoContentElementTranslation */
			$translation = NenoContentElementTranslation::load($translationId, false, true);

			$translation->{$method}();
		}

		JFactory::getApplication()
		  ->redirect('index.php?option=com_neno&view=groupselements');
	}

	public function checkIntegrity()
	{
		$input = $this->input;

		// Refresh content for groups
		$groups          = $input->get('groups', array(), 'ARRAY');
		$tables          = $input->get('tables', array(), 'ARRAY');
		$workingLanguage = NenoHelper::getWorkingLanguage();

		if (!empty($groups))
		{
			foreach ($groups as $groupId)
			{
				$tables = NenoContentElementTable::load(
				  array(
					'group_id'  => $groupId,
					'translate' => 1
				  )
				);

				// Making sure the result is an array
				if (!is_array($tables))
				{
					$tables = array($tables);
				}

				/* @var $table NenoContentElementTable */
				foreach ($tables as $table)
				{
					// Check table integrity
					$table->checkIntegrity($workingLanguage);
				}
			}
		}
		elseif (!empty($tables))
		{
			foreach ($tables as $tableId)
			{
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load($tableId);

				if (!empty($table) && $table->isTranslate())
				{
					// Check table integrity
					$table->checkIntegrity($workingLanguage);
				}
			}
		}

		JFactory::getApplication()
		  ->redirect('index.php?option=com_neno&view=groupselements');
	}

	public function saveTableFilters()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$filters = $input->post->get('filters', array(), 'ARRAY');
		$tableId = $input->post->getInt('tableId');

		if (!empty($filters) && !empty($tableId))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
			  ->delete('#__neno_content_element_table_filters')
			  ->where('table_id = ' . (int) $tableId);

			$db->setQuery($query);
			$db->execute();

			$query
			  ->clear()
			  ->insert('#__neno_content_element_table_filters')
			  ->columns(
				array(
				  'table_id',
				  'field_id',
				  'comparaison_operator',
				  'filter_value'
				)
			  );

			foreach ($filters as $filter)
			{
				// Check if the filters are common fields
				$fieldName = NenoHelperBackend::getFieldName($filter['field']);

				$commonFields = array(
				  'state',
				  'published',
				  'created_by',
				  'created_user_id',
				  'modified_by',
				  'modified_user_id',
				  'catid'
				);

				if (in_array($fieldName, $commonFields))
				{
					$filter['operator'] = 'IN';
				}

				// Check if value is multiple
				if (is_array($filter['value']))
				{
					$filter['value'] = implode(',', $filter['value']);
				}

				$query
				  ->values(
					$db->quote($tableId) . ','
					. $db->quote($filter['field']) . ','
					. $db->quote($filter['operator']) . ','
					. $db->quote($filter['value'])
				  );
			}

			$db->setQuery($query);
			$db->execute();

			// Change table status
			$query
			  ->clear()
			  ->update('#__neno_content_element_tables')
			  ->set('translate = 2')
			  ->where('id = ' . $db->quote($tableId));

			$db->setQuery($query);
			$db->execute();

			// Adding task for table maintenance
			NenoTaskMonitor::addTask('maintenance', array('tableId' => $tableId));

			echo 'ok';
		}

		$app->close();
	}

	public function refreshWordCount()
	{
		$this->executeMethodOnTranslationListPassedByInput('persist');
	}

	/**
	 * Get translation ids based on input parameters
	 *
	 * @return array
	 */
	protected function getTranslationIdsListBasedOnInputParameters()
	{
		$input = $this->input;

		// Refresh content for groups
		$groups          = $input->get('groups', array(), 'ARRAY');
		$tables          = $input->get('tables', array(), 'ARRAY');
		$files           = $input->get('files', array(), 'ARRAY');
		$workingLanguage = NenoHelper::getWorkingLanguage();

		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = NenoContentElementTranslation::buildTranslationQuery($workingLanguage, $groups, $tables, NULL, $files);

		$db->setQuery($query);
		$translationIds = $db->loadArray();

		return $translationIds;
	}

	/**
	 * Toggle content element language file translate status
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function toggleContentElementLanguageFile()
	{
		$input = JFactory::getApplication()->input;

		$languageFile    = $input->getInt('fileId');
		$translateStatus = $input->getInt('translateStatus');

		/* @var $languageFile NenoContentElementLanguageFile */
		$languageFile = NenoContentElementLanguageFile::load($languageFile);

		// If the table exists, let's work with it.
		if ($languageFile !== false)
		{
			$languageFile
			  ->setTranslate($translateStatus)
			  ->persist();
		}

		JFactory::getApplication()->close();
	}
}
