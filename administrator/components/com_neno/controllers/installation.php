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
class NenoControllerInstallation extends JControllerAdmin
{
	/**
	 * Field hierarchy level
	 */
	const FIELD_LEVEL = '2.1';
	/**
	 * Language string hierarchy level
	 */
	const LANGUAGE_STRING_LEVEL = '2.2';

	/**
	 * Load installation step
	 *
	 * @return void
	 */
	public function loadInstallationStep()
	{
		$step = NenoSettings::get('installation_status', 0);

		if (empty($step))
		{
			$layout = JLayoutHelper::render('installationgetstarted', NULL, JPATH_NENO_LAYOUTS);
		}
		else
		{
			$layout = JLayoutHelper::render('installationstep' . $step, $this->getDataForStep($step), JPATH_NENO_LAYOUTS);
		}

		$sidebar = '';

		if ($step == 7)
		{
			NenoHelperBackend::addSubmenu();
			$sidebar = JHtmlSidebar::render();
		}

		echo json_encode(array(
		  'installation_step' => $layout,
		  'jsidebar'          => $sidebar,
		  'step'              => $step
		));

		JFactory::getApplication()->close();
	}

	/**
	 * Get data for 1st step
	 *
	 * @return stdClass
	 */
	protected function getDataForStep1()
	{
		$data                = new stdClass;
		$languages           = NenoHelper::findLanguages(true);
		$data->select_widget = JHtml::_('select.genericlist', $languages, 'source_language', NULL, 'iso', 'name', NenoSettings::get('source_language'));

		return $data;
	}

	/**
	 * Get data for 3rd step
	 *
	 * @return stdClass
	 */
	protected function getDataForStep3()
	{
		$data                       = new stdClass;
		$language                   = JFactory::getLanguage();
		$default                    = NenoSettings::get('source_language');
		$knownLanguages             = $language->getKnownLanguages();
		$languagesData              = array();
		$defaultTranslationsMethods = NenoHelper::getDefaultTranslationMethods();
		$db                         = JFactory::getDbo();
		$query                      = $db->getQuery(true);
		$query
		  ->insert('#__neno_content_language_defaults')
		  ->columns(
			array(
			  'lang',
			  'translation_method_id',
			  'ordering'
			)
		  );

		$insert = false;

		foreach ($knownLanguages as $key => $knownLanguage)
		{
			if ($knownLanguage['tag'] != $default)
			{
				$insert                                    = true;
				$languagesData[$key]                       = $knownLanguage;
				$languagesData[$key]['lang_code']          = $knownLanguage['tag'];
				$languagesData[$key]['title']              = $knownLanguage['name'];
				$languagesData[$key]['translationMethods'] = $defaultTranslationsMethods;
				$languagesData[$key]['errors']             = NenoHelper::getLanguageErrors($languagesData[$key]);
				$languagesData[$key]['placement']          = 'installation';
				$languagesData[$key]['image']              = NenoHelper::getLanguageImage($knownLanguage['tag']);
				$languagesData[$key]['published']          = NenoHelper::isLanguagePublished($knownLanguage['tag']);
				$languagesData[$key]['comment']            = NenoHelper::getLanguageTranslatorComment($knownLanguage['tag']);

				foreach ($defaultTranslationsMethods as $ordering => $defaultTranslationsMethod)
				{
					$query->values($db->quote($knownLanguage['tag']) . ',' . $defaultTranslationsMethod->id . ',' . ($ordering + 1));
				}
			}
		}

		if ($insert)
		{
			$db->setQuery($query);
			$db->execute();
		}

		$data->languages = $languagesData;

		return $data;
	}

	/**
	 * Get data for 4th step
	 *
	 * @return stdClass
	 */
	protected function getDataForStep4()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db            = JFactory::getDbo();
		$query         = $db->getQuery(true);
		$data          = new stdClass;
		$tablesIgnored = NenoHelper::getDoNotTranslateTables();

		/* @var $config \Joomla\Registry\Registry */
		$config = JFactory::getConfig();

		$query
		  ->select('DISTINCT TABLE_NAME')
		  ->from('INFORMATION_SCHEMA.COLUMNS')
		  ->where(
			array(
			  'COLUMN_NAME = ' . $db->quote('language'),
			  'TABLE_SCHEMA = ' . $db->quote($config->get('db')),
			  'TABLE_NAME NOT LIKE ' . $db->quote('%neno%'),
			  'TABLE_NAME NOT LIKE ' . $db->quote('%\_\_%'),
			  'TABLE_NAME NOT LIKE ' . $db->quote('%menu'),
			)
		  );

		$db->setQuery($query);
		$tables = $db->loadArray();

		$tablesFound = array();

		foreach ($tables as $table)
		{
			if (!in_array(str_replace($db->getPrefix(), '#__', $table), $tablesIgnored))
			{
				$sourceLanguage      = NenoSettings::get('source_language');
				$sourceLanguageParts = explode('-', $sourceLanguage);
				$query
				  ->clear()
				  ->select(
					array(
					  'COUNT(*) AS counter',
					  'language',
					  $db->quote($table) . ' AS `table`'
					)
				  )
				  ->from($db->quoteName($table))
				  ->where(
					array(
					  'language <> ' . $db->quote('*'),
					  'language <> ' . $db->quote(''),
					  'language <> ' . $db->quote($sourceLanguage),
					  'language <> ' . $db->quote($sourceLanguageParts[0]),
					)
				  )
				  ->group('language');

				$db->setQuery($query);
				$recordsFound = $db->loadObjectList();

				if (!empty($recordsFound))
				{
					$tablesFound = array_merge($tablesFound, $recordsFound);
				}
			}
		}

		$data->tablesFound = $tablesFound;

		return $data;
	}

	/**
	 * Get data for 5th step
	 *
	 * @return stdClass
	 */
	public function getDataForStep5()
	{
		$data   = new stdClass;
		$groups = NenoHelper::getGroups();

		/* @var $group NenoContentElementGroup */
		foreach ($groups as $key => $group)
		{
			$group->getTables();
			$group->getLanguageFiles();
			$groups[$key] = $group->prepareDataForView();
		}
		$data->groups = $groups;

		return $data;
	}

	/**
	 * Get data for 6th step
	 *
	 * @return stdClass
	 */
	public function getDataForStep6()
	{
		$data                      = new stdClass;
		$modules                   = NenoHelper::getModulesInSourceLanguage();
		$languages                 = NenoHelper::getLanguages(false, false);
		$createNew                 = new stdClass;
		$createNew->id             = 'create';
		$createNew->title          = JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_DROPDOWN_CREATE');
		$doNothing                 = new stdClass;
		$doNothing->id             = 'nothing';
		$doNothing->title          = JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_DROPDOWN_DO_NOTHING');
		$defaultOptionsForDropdown = array($createNew, $doNothing);

		foreach ($modules as $module)
		{
			$module->languageModules = array();
			
			$callback = NenoHelper::getCallbackForModulesFilteringByModuleType($module->module);
			
			foreach ($languages as $language)
			{
				$module->languageModules[$language->lang_code]['modules'] = array_merge($defaultOptionsForDropdown, NenoHelper::getSimilarModulesToModule($module, $language->lang_code));
				$module->languageModules[$language->lang_code]['similar'] = NenoHelper::getMostSimilarModuleForLanguage($module, $language->lang_code, $callback);
			}
		}
		$data->modules         = $modules;
		$data->languages       = $languages;
		$data->source_language = JFactory::getLanguage();

		return $data;
	}

	/**
	 * Get data for the installation step
	 *
	 * @param   int $step Step number
	 *
	 * @return stdClass
	 */
	protected function getDataForStep($step)
	{
		$methodName = 'getDataForStep' . $step;

		if (method_exists($this, $methodName))
		{
			return $this->{$methodName}();
		}

		return new stdClass;
	}

	/**
	 *
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function previewContentFromElement()
	{
		$app               = JFactory::getApplication();
		$input             = $app->input;
		$id                = $input->getInt('id');
		$type              = $input->getCmd('type');
		$displayData       = new stdClass;
		$displayData->type = $type;

		switch ($type)
		{
			case 'table':
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load($id);

				$displayData->name = $table->getTableName();
				$displayData->id   = $table->getId();

				if (!empty($table))
				{
					$displayData->records = $table->getRandomContentFromTable();
					$fields               = $table->getFields();

					/* @var $field NenoContentElementField */
					foreach ($fields as $key => $field)
					{
						if (NenoContentElementField::isTranslatableType($field->getFieldType()))
						{
							$fields[$key] = $field->prepareDataForView();
						}
						else
						{
							unset($fields[$key]);
						}
					}

					$displayData->fields = $fields;
				}
				break;
			case 'file':
				/* @var $languageFile NenoContentElementLanguageFile */
				$languageFile      = NenoContentElementLanguageFile::load($id);
				$displayData->name = $languageFile->getFilename();
				$displayData->id   = $languageFile->getId();

				if (!empty($languageFile))
				{
					$displayData->records = $languageFile->getRandomContentFromLanguageFile();
				}
				break;
		}

		echo JLayoutHelper::render('previewcontent', $displayData, JPATH_NENO_LAYOUTS);

		$app->close();
	}

	public function resetDiscoveringVariables()
	{
		NenoSettings::set('discovering_element_1.1', NULL);
		NenoSettings::set('discovering_element_0', NULL);
		NenoSettings::set('installation_level', NULL);
		NenoSettings::set('current_percent', NULL);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select(
			'(SELECT COUNT(*) FROM #__neno_content_element_fields WHERE translate = 1) + (SELECT COUNT(*) FROM #__neno_content_element_language_strings) AS counter'
		  );

		$db->setQuery($query);
		$elementsCounter = $db->loadResult();

		NenoSettings::set('percent_per_content_element', 100 / $elementsCounter);

		JFactory::getApplication()->close();
	}

	/**
	 * Process installation step
	 *
	 * @return void
	 */
	public function processInstallationStep()
	{
		$step        = NenoSettings::get('installation_status', 0);
		$moveForward = true;
		$app         = JFactory::getApplication();
		$response    = array('status' => 'ok');

		if ($step != 0)
		{
			$methodName = 'validateStep' . (int) $step;

			// Validate data.
			if (method_exists($this, $methodName))
			{
				$moveForward = $this->{$methodName}();
			}
		}

		if ($moveForward)
		{
			NenoSettings::set('installation_status', $step + 1);
		}
		else
		{
			$response['status'] = 'err';
			$messagesQueued     = $app->getMessageQueue();
			$messages           = array();

			foreach ($messagesQueued as $messageQueued)
			{
				if ($messageQueued['type'] === 'error')
				{
					$messages[] = $messageQueued['message'];
				}
			}

			$response['error_messages'] = $messages;
		}

		echo json_encode($response);

		JFactory::getApplication()->close();
	}

	/**
	 * Get previous messages
	 *
	 * @return void
	 */
	public function getPreviousMessages()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
		  ->select('*')
		  ->from('#__neno_installation_messages as m1')
		  ->where(
			array(
			  'm1.fetched = 1'
			)
		  )
		  ->group('level')
		  ->order(
			array(
			  'level ASC',
			  'id DESC'
			)
		  );

		$db->setQuery($query);
		echo json_encode($db->loadAssocList());

		JFactory::getApplication()->close();
	}

	/**
	 * Discovers components structure
	 *
	 * @return void
	 */
	protected function discoverStructure()
	{
		$finished = NenoSettings::get('installation_completed') == 1;

		if (!$finished)
		{
			$level = NenoSettings::get('installation_level', 0);

			if (!$this->isLeafLevel($level))
			{
				/* @var $element NenoContentElementTable */
				$element = $this->getElementByLevel($level);

				if ($element == NULL && $level == 0)
				{
					// If there aren't any, let's create do not translate group if it doesn't exist
					NenoHelperBackend::createDoNotTranslateGroup();
					$finished = true;
				}
				elseif (($element == NULL || ($element instanceof NenoContentElementTable && !$element->isDiscovered())) && $level != 0)
				{
					$this->goingBackInTheHierarchy($level);
				}
				else
				{
					$element->discoverElement();
				}
			}
			else
			{
				$this->goingBackInTheHierarchy($level);
			}
		}

		if ($finished)
		{
			echo 'ok';
		}
	}

	/**
	 * @param string $level Hierarchy level
	 *
	 * @return void
	 */
	protected function goingBackInTheHierarchy($level)
	{
		list($firstPart, $secondPart) = explode('.', $level);
		$firstPart--;

		if ($firstPart == 0)
		{
			NenoSettings::set('installation_level', $firstPart);
		}
		else
		{
			NenoSettings::set('installation_level', implode('.', array(
			  $firstPart,
			  $secondPart
			)));
		}
	}

	/**
	 * Checks if a level is a leaf
	 *
	 * @param string $level level
	 *
	 * @return bool
	 */
	protected function isLeafLevel($level)
	{
		list($branch,) = explode('.', $level);

		return $branch == 2;
	}

	/**
	 * Discovers components content
	 *
	 * @throws Exception
	 */
	protected function discoverContent()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);
		$finished = false;

		// Get all the fields that haven't been discovered already
		$element = $this->getLeafElement(self::FIELD_LEVEL);

		if ($element === NULL)
		{
			$element = $this->getLeafElement(self::LANGUAGE_STRING_LEVEL);
		}

		if ($element == NULL)
		{
			// Let's publish language plugins
			$query
			  ->clear()
			  ->update('#__extensions')
			  ->set('enabled = 1')
			  ->where(
				array(
				  'element LIKE ' . $db->quote('languagecode'),
				  'element LIKE ' . $db->quote('languagefilter')
				), 'OR'
			  );

			$db->setQuery($query);
			$db->execute();

			// Let's create menu structure
			NenoHelper::createMenuStructure();
			$finished = true;
		}
		else
		{
			/* @var $element NenoContentElementGroup */
			$element->discoverElement();
		}

		if ($finished)
		{
			echo 'ok';
		}
	}

	/**
	 * Execute discovering process
	 *
	 * @return void
	 */
	public function processDiscoveringStep()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$contentType = $input->getCmd('contentType');

		switch ($contentType)
		{
			case 'structure':
				$this->discoverStructure();
				break;

			case 'content':
				$this->discoverContent();
				break;
		}

		$app->close();
	}

	/**
	 * @param $type
	 *
	 * @return NenoContentElementInterface|null
	 */
	protected function getLeafElement($type)
	{
		return $this->getElementByLevel($type);
	}

	/**
	 * Get Group element
	 *
	 * @return NenoContentElementGroup|null
	 */
	protected function getGroupElement()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// This means to get a group that haven't been discovered yet
		$extensions = $db->quote(NenoHelper::whichExtensionsShouldBeTranslated());

		$query
		  ->clear()
		  ->select('e.*')
		  ->from('`#__extensions` AS e')
		  ->where(
			array(
			  'e.type IN (' . implode(',', $extensions) . ')',
			  'e.name NOT LIKE \'com_neno\'',
			  'NOT EXISTS (SELECT 1 FROM #__neno_content_element_groups_x_extensions AS ge WHERE ge.extension_id = e.extension_id)'
			)
		  )
		  ->order('name');
		$db->setQuery($query, 0, 1);
		$extension = $db->loadAssoc();

		if (!empty($extension))
		{
			$group         = NenoHelper::createGroupInstanceBasedOnExtensionId($extension);
			$extensionName = NenoHelper::getExtensionName($extension);
			$languageFiles = NenoHelper::getLanguageFiles($extensionName);
			$tables        = NenoHelper::getComponentTables($group, $extensionName);
			$group->setAssignedTranslationMethods(NenoHelper::getTranslationMethodsForLanguages());

			// If the group contains tables and/or language strings, let's save it
			if (!empty($tables) || !empty($languageFiles))
			{
				$group
				  ->setLanguageFiles($languageFiles)
				  ->setTables($tables);
			}

			$element = $group;
		}
		else
		{
			$element = NenoHelperBackend::groupingTablesNotDiscovered(false);
		}

		return $element;
	}

	/**
	 * Get table element
	 *
	 * @param int $tableId Table Id
	 *
	 * @return NenoContentElementTable|null
	 */
	protected function getTableElement($tableId)
	{
		$element = NULL;

		if (empty($tableId))
		{
			// Get one table that hasn't been discovered yet
			$table = NenoContentElementTable::load(
			  array(
				'discovered' => 0,
				'_limit'     => 1,
				'translate'  => 1,
				'group_id'   => NenoSettings::get('discovering_element_0')
			  ), false, true
			);
		}
		else
		{
			$table = NenoContentElementTable::load($tableId, false, true);
		}

		if (!empty($table))
		{
			$element = $table;
		}

		return $element;
	}

	/**
	 * Get language file element
	 *
	 * @param int $languageFileId
	 *
	 * @return null|NenoContentElementLanguageFile
	 */
	protected function getLanguageFileElement($languageFileId)
	{
		$element = NULL;

		if ($languageFileId == NULL)
		{
			// Get one table that hasn't been discovered yet
			$languageFile = NenoContentElementLanguageFile::load(
			  array(
				'discovered' => 0,
				'_limit'     => 1,
				'group_id'   => NenoSettings::get('discovering_element_0')
			  ), false, true
			);
		}
		else
		{
			$languageFile = NenoContentElementLanguageFile::load($languageFileId, false, true);
		}

		if (!empty($languageFile))
		{
			$element = $languageFile;
		}

		return $element;
	}

	/**
	 * Get field element
	 *
	 * @param int $fieldId Field id
	 *
	 * @return NenoContentElementField|null
	 */
	protected function getFieldElement($fieldId)
	{
		$element = NULL;

		if ($fieldId == NULL)
		{
			// Get one table that hasn't been discovered yet
			$field = NenoContentElementField::load(
			  array(
				'discovered' => 0,
				'_limit'     => 1,
				'translate'  => 1
			  ), false, true
			);
		}
		else
		{
			$field = NenoContentElementField::load($fieldId);
		}

		if (!empty($field) && $field)
		{
			$element = $field;
		}

		return $element;
	}

	/**
	 * Get language string element
	 *
	 * @param int $languageStringId Language string Id
	 *
	 * @return NenoContentElementLanguageString|null
	 */
	protected function getLanguageStringElement($languageStringId)
	{
		$element = NULL;

		if ($languageStringId == NULL)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select($db->quoteName('id'))
				->from($db->quoteName('#__neno_content_element_language_strings', 'ls'))
				->innerJoin('#__neno_content_element_language_files AS lf ON lf.id = ls.languagefile_id')
				->where(
					array(
						'ls.discovered = 0',
						'lf.translate = 1'
					)
				);

			$db->setQuery($query, 0, 1);
			$languageStringId = $db->loadResult();
		}

		$languageString = NenoContentElementLanguageString::load($languageStringId);

		if (!empty($languageString))
		{
			$element = $languageString;
		}

		return $element;
	}

	/**
	 * Get a particular element using the level
	 *
	 * @param   string $level Hierarchy level
	 *
	 * @return NenoContentElementInterface|null
	 */
	protected function getElementByLevel($level)
	{
		$element   = NULL;
		$elementId = NenoSettings::get('discovering_element_' . $level);
		$this->initPercents();

		switch ($level)
		{
			// Groups
			case '0':
				$element = $this->getGroupElement();
				break;

			// Tables
			case '1.1':
				$element = $this->getTableElement($elementId);
				break;

			// Language files
			case '1.2':
				$element = $this->getLanguageFileElement($elementId);
				break;

			// Fields
			case '2.1':
				$element = $this->getFieldElement($elementId);
				break;

			// Language strings
			case '2.2':
				$element = $this->getLanguageStringElement($elementId);
				break;
		}

		return $element;
	}

	/**
	 * Init percents
	 *
	 * @return void
	 */
	protected function initPercents()
	{
		$currentPercent = NenoSettings::get('current_percent', 0);

		if ($currentPercent == 0)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			// This means to get a group that haven't been discovered yet
			$extensions = $db->quote(NenoHelper::whichExtensionsShouldBeTranslated());

			$query
			  ->clear()
			  ->select('COUNT(e.extension_id)')
			  ->from('`#__extensions` AS e')
			  ->where(
				array(
				  'e.type IN (' . implode(',', $extensions) . ')',
				  'e.name NOT LIKE \'%neno%\'',
				)
			  )
			  ->order('name');
			$db->setQuery($query, 0, 1);
			$extensionsCounter = $db->loadResult();

			NenoSettings::set('percent_per_extension', 90 / ($extensionsCounter + 1));
		}
	}

	/**
	 * Fetch setup status
	 *
	 * @return void
	 */
	public function getSetupStatus()
	{
		$setupState = NenoHelperBackend::getSetupState();
		echo json_encode($setupState);
		JFactory::getApplication()->close();
	}

	/**
	 * Validate installation step 1
	 *
	 * @return bool
	 */
	protected function validateStep1()
	{
		$input          = $this->input;
		$sourceLanguage = $input->post->get('source_language');

		if (!empty($sourceLanguage))
		{
			$language           = JFactory::getLanguage();
			$knownLanguagesTags = array_keys($language->getKnownLanguages());

			if (!in_array($sourceLanguage, $knownLanguagesTags))
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);

				$query
				  ->select('update_id')
				  ->from('#__updates')
				  ->where('element = ' . $db->quote('pkg_' . $sourceLanguage))
				  ->order('update_id DESC');

				$db->setQuery($query, 0, 1);
				$updateId = $db->loadResult();

				if (!empty($updateId))
				{
					if (!NenoHelper::installLanguage($updateId))
					{
						return false;
					}
				}
			}

			// Once the language is installed, let's mark it as default
			JLoader::register('LanguagesModelInstalled', JPATH_ADMINISTRATOR . '/components/com_languages/models/installed.php');

			/* @var $model LanguagesModelInstalled */
			$model = JModelLegacy::getInstance('Installed', 'LanguagesModel');

			// If the language has been marked as default, let's save that on the settings
			if ($model->publish($sourceLanguage))
			{
				NenoSettings::set('source_language', $sourceLanguage, true);
			}

			return true;
		}

		return false;
	}

	/**
	 * Validate installation step 3
	 *
	 * @return bool
	 */
	protected function validateStep2()
	{
		$input = $this->input;
		$jform = $input->post->get('jform', array(), 'ARRAY');

		if (!empty($jform['translation_methods']))
		{
			foreach ($jform['translation_methods'] as $key => $translationMethod)
			{
				NenoSettings::set('translation_method_' . ($key + 1), $translationMethod);
			}

			return true;
		}

		return false;
	}

	/**
	 * Validate installation step 3
	 *
	 * @return bool
	 */
	protected function validateStep6()
	{
		$input = $this->input;
		$jform = $input->post->get('jform', array(), 'ARRAY');

		foreach ($jform as $moduleKey => $moduleAction)
		{
			list(, $sourceModuleId, $languageTag) = explode('_', $moduleKey);

			switch ($moduleAction)
			{
				// Let's create the module
				case 'create':
					NenoHelper::replicateModule($sourceModuleId, $languageTag);
					break;
				// If the user choose to do nothing or a module has been selected
				case'nothing':
				default:
					// Empty case
					break;
			}
		}

		NenoSettings::set('installation_completed', 1);

		return true;
	}

	/**
	 * @throws Exception
	 *
	 * @return void
	 */
	public function refreshRecordCounter()
	{
		$app     = JFactory::getApplication();
		$input   = $app->input;
		$tableId = $input->getInt('tableId');

		/* @var $table NenoContentElementTable */
		$table = NenoContentElementTable::load($tableId);

		echo JLayoutHelper::render('installationrecordcount', $table->prepareDataForView(), JPATH_NENO_LAYOUTS);

		$app->close();
	}
}
