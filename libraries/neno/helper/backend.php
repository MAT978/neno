<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Neno Backend helper.
 *
 * @since  1.0
 */
class NenoHelperBackend
{
	/**
	 * Configure the Link bar.
	 *
	 * @param   string $vName View name
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_DASHBOARD'),
			'index.php?option=com_neno&view=dashboard',
			($vName == 'dashboard') ? true : false
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_EDITOR'),
			'index.php?option=com_neno&view=editor',
			($vName == 'editor') ? true : false
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_EXTERNAL_GROUPSELEMENTS'),
			'index.php?option=com_neno&view=groupselements',
			($vName == 'groupselements') ? true : false
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_EXTERNAL_TRANSLATIONS'),
			'index.php?option=com_neno&view=externaltranslations',
			($vName == 'externaltranslations') ? true : false
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_EXTERNAL_SETTINGS'),
			'index.php?option=com_neno&view=settings',
			($vName == 'settings') ? true : false
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_NENO_NAV_LINK_DEBUG_REPORT'),
			'index.php?option=com_neno&view=debug',
			($vName == 'debug') ? true : false
		);
	}

	/**
	 * Checks if there are any jobs in the queue
	 *
	 * @return bool
	 */
	public static function areThereAnyJobs()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(1)
			->from('#__neno_jobs');

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Get sidebar infobox HTML
	 *
	 * @param   string $viewName View name
	 *
	 * @return string
	 */
	public static function getSidebarInfobox($viewName = '')
	{
		return JLayoutHelper::render('sidebarinfobox', $viewName, JPATH_NENO_LAYOUTS);
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return JObject
	 */
	public static function getActions()
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		$assetName = 'com_neno';

		$actions = array(
			'core.admin',
			'core.manage',
			'core.create',
			'core.edit',
			'core.edit.own',
			'core.edit.state',
			'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Create the HTML for the fairly advanced title that allows changing the language you are working in
	 *
	 * @param   boolean $showLanguageDropDown If we should show the languages dropdown
	 *
	 * @return string
	 */
	public static function setAdminTitle($showLanguageDropDown = false)
	{
		$app  = JFactory::getApplication();
		$view = $app->input->getCmd('view', '');

		$document     = $app->getDocument();
		$currentTitle = $document->getTitle();
		$document->setTitle($currentTitle . ' - ' . JText::_('COM_NENO_TITLE_' . strtoupper($view)));

		// If there is a language constant then start with that
		$displayData = array(
			'view' => $view
		);

		if ($showLanguageDropDown)
		{
			$displayData['workingLanguage'] = NenoHelper::getWorkingLanguage();
			$displayData['targetLanguages'] = NenoHelper::getLanguages(false, false);
		}

		$adminTitleLayout     = JLayoutHelper::render('toolbar', $displayData, JPATH_NENO_LAYOUTS);
		$layout               = new JLayoutFile('joomla.toolbar.title');
		$html                 = $layout->render(array( 'title' => $adminTitleLayout, 'icon' => 'nope' ));
		$app->JComponentTitle = $html;
	}

	/**
	 * Method to clean a folder
	 *
	 * @param   string $path Folder path
	 *
	 * @return bool True on success
	 *
	 * @throws Exception
	 */
	public static function cleanFolder($path)
	{
		$folders = JFolder::folders($path);

		foreach ($folders as $folder)
		{
			try
			{
				JFolder::delete($path . '/' . $folder);
			} catch (UnexpectedValueException $e)
			{
				throw new Exception('An error occur deleting a folder: %s', $e->getMessage());
			}
		}

		$files = JFolder::files($path);

		foreach ($files as $file)
		{
			if ($file !== 'index.html')
			{
				JFile::delete($path . '/' . $file);
			}
		}
	}

	/**
	 * Get the latest message
	 *
	 * @return array|null
	 */
	public static function getSetupState()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('*')
			->from('#__neno_installation_messages')
			->where('fetched = 0')
			->order('id ASC');
		$db->setQuery($query);
		$messages = $db->loadAssocList('id');

		if (!empty($messages))
		{
			$query
				->clear()
				->update('#__neno_installation_messages')
				->set('fetched = 1')
				->where('id IN (' . implode(',', array_keys($messages)) . ')');
			$db->setQuery($query);
			$db->execute();
			$messages = array_values($messages);
		}

		return $messages;
	}

	/**
	 * Discover all the extensions that haven't been discovered yet
	 *
	 * @return void
	 */
	public static function createDoNotTranslateGroup()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();

		// Get all the tables that haven't been detected using naming convention.
		$tablesNotDiscovered = self::getTablesNotDiscovered();

		if (!empty($tablesNotDiscovered))
		{
			$doNotTranslateGroup = new NenoContentElementGroup(array( 'group_name' => 'Do not translate' ));
			$tablesIgnored       = NenoHelper::getDoNotTranslateTables();

			foreach ($tablesIgnored as $tableIgnored)
			{
				// Create an array with the table information
				$tableData = array(
					'tableName'  => $tableIgnored,
					'primaryKey' => $db->getPrimaryKey($tableIgnored),
					'translate'  => 0,
					'group'      => $doNotTranslateGroup
				);

				// Create ContentElement object
				$table = new NenoContentElementTable($tableData);

				// Get all the columns a table contains
				$fields = $db->getTableColumns($table->getTableName());

				foreach ($fields as $fieldName => $fieldType)
				{
					$fieldData = array(
						'fieldName' => $fieldName,
						'fieldType' => $fieldType,
						'translate' => NenoContentElementField::isTranslatableType($fieldType),
						'table'     => $table
					);

					$field = new NenoContentElementField($fieldData);
					$table->addField($field);
				}

				$doNotTranslateGroup->addTable($table);
			}

			$doNotTranslateGroup->persist();
		}
	}

	/**
	 * Get tables that haven't been discovered yet
	 *
	 * @return array
	 */
	public static function getTablesNotDiscovered()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		/* @var $config Joomla\Registry\Registry */
		$config   = JFactory::getConfig();
		$database = $config->get('db');
		$dbPrefix = $config->get('dbprefix');

		$subQuery = $db->getQuery(true);
		$subQuery
			->select('1')
			->from($db->quoteName($database) . '.#__neno_content_element_tables AS cet')
			->where('cet.table_name LIKE REPLACE(dbt.table_name, ' . $db->quote($dbPrefix) . ', ' . $db->quote('#__') . ')');

		$query
			->select('REPLACE(TABLE_NAME, ' . $db->quote($dbPrefix) . ', \'#__\') AS table_name')
			->from('INFORMATION_SCHEMA.TABLES AS dbt')
			->where(
				array(
					'TABLE_TYPE = ' . $db->quote('BASE TABLE'),
					'TABLE_SCHEMA = ' . $db->quote($database),
					'db.table_name LIKE ' . $db->quote($dbPrefix . '%'),
					'REPLACE(dbt.table_name, ' . $db->quote($dbPrefix) . ', ' . $db->quote('#__') . ') NOT LIKE ' . $db->quote('#\_\_neno_%'),
					'REPLACE(dbt.table_name, ' . $db->quote($dbPrefix) . ', ' . $db->quote('#__') . ') NOT LIKE ' . $db->quote('#\_\_\_%'),
					'REPLACE(dbt.table_name, ' . $db->quote($dbPrefix) . ', ' . $db->quote('#__') . ') NOT IN (' . implode(',', $db->quote(self::getJoomlaTablesWithNoContent())) . ')',
					'NOT EXISTS ( ' . (string) $subQuery . ')'
				)
			);

		$db->setQuery($query);
		$tablesNotDiscovered = $db->loadArray();

		$query
			->clear()
			->select('t.table_name')
			->from('#__neno_content_element_tables AS t')
			->innerJoin('#__neno_content_element_groups AS g ON t.group_id = g.id')
			->where('g.other_group = 1');

		$db->setQuery($query);
		$discoveredTablesInOtherGroup = $db->loadArray();

		return array_merge($discoveredTablesInOtherGroup, $tablesNotDiscovered);
	}

	/**
	 * Get a list of tables that should not be included into Neno
	 *
	 * @return array
	 */
	public static function getJoomlaTablesWithNoContent()
	{
		return array(
			'#__assets',
			'#__associations',
			'#__core_log_searches',
			'#__menu',
			'#__menu_types',
			'#__session',
			'#__messages_cfg',
			'#__schemas',
			'#__ucm_base',
			'#__updates',
			'#__update_sites',
			'#__update_sites_extensions'
		);
	}

	/**
	 * Grouping tables that haven't been discovered
	 *
	 * @param   bool $persist Persist the group
	 *
	 * @return NenoContentElementGroup
	 */
	public static function groupingTablesNotDiscovered($persist = true)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();

		// Get all the tables that haven't been detected using naming convention.
		$tablesNotDiscovered = self::getTablesNotDiscovered();
		$tablesAdded         = false;
		$otherGroup          = null;

		if (!empty($tablesNotDiscovered))
		{
			// Check if this group exists already
			$query = $db->getQuery(true);
			$query
				->select('g.id')
				->from('#__neno_content_element_groups AS g')
				->where('NOT EXISTS (SELECT 1 FROM #__neno_content_element_groups_x_extensions AS ge WHERE ge.group_id = g.id)');

			$db->setQuery($query);
			$groupId = $db->loadResult();

			if (!empty($groupId))
			{
				/* @var $otherGroup NenoContentElementGroup */
				$otherGroup = NenoContentElementGroup::load($groupId);
			}
			else
			{
				$otherGroup = new NenoContentElementGroup(array( 'group_name' => 'Other' ));
			}

			$tablesIgnored = NenoHelper::getDoNotTranslateTables();

			foreach ($tablesNotDiscovered as $tableNotDiscovered)
			{
				if (!in_array($tableNotDiscovered, $tablesIgnored))
				{
					// Create an array with the table information
					$tableData = array(
						'tableName'  => $tableNotDiscovered,
						'primaryKey' => $db->getPrimaryKey($tableNotDiscovered),
						'translate'  => 1,
						'group'      => $otherGroup
					);

					// Create ContentElement object
					$table = new NenoContentElementTable($tableData);

					// Get all the columns a table contains
					$fields = $db->getTableColumns($table->getTableName());

					foreach ($fields as $fieldName => $fieldType)
					{
						$fieldData = array(
							'fieldName' => $fieldName,
							'fieldType' => $fieldType,
							'translate' => NenoContentElementField::isTranslatableType($fieldType),
							'table'     => $table
						);

						$field = new NenoContentElementField($fieldData);
						$table->addField($field);
					}

					$otherGroup->addTable($table);
					$tablesAdded = true;
				}
			}

			$otherGroup->setAssignedTranslationMethods(NenoHelper::getTranslationMethodsForLanguages());

			if ($persist)
			{
				$otherGroup->persist();
			}
		}

		if (!$tablesAdded)
		{
			$otherGroup = null;
		}

		return $otherGroup;
	}

	/**
	 * Check if the database driver is enabled
	 *
	 * @return bool True if it's enabled, false otherwise
	 */
	public static function isDatabaseDriverEnabled()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('enabled')
			->from('#__extensions')
			->where(
				array(
					'folder = ' . $db->quote('system'),
					'type = ' . $db->quote('plugin'),
					'element = ' . $db->quote('neno'),
				)
			);

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Take an array of strings (enums) and parse them though JText and get the correct name
	 * Then return as comma separated list
	 *
	 * @param   array $methods Translation methods
	 *
	 * @return string
	 */
	public static function renderTranslationMethodsAsCSV($methods = array())
	{
		if (!empty($methods))
		{
			foreach ($methods as $key => $method)
			{
				$methods[ $key ] = JText::_(strtoupper($method->name_constant));
			}
		}

		return implode(', ', $methods);
	}

	/**
	 * Get client list in text/value format for a select field
	 *
	 * @return  array
	 */
	public static function getGroupOptions()
	{
		$options = array();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->select('id AS value, group_name AS text')
		            ->from('#__neno_content_element_groups AS n')
		            ->order('n.group_name');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		} catch (RuntimeException $e)
		{
			NenoLog::log($e->getMessage(), NenoLog::PRIORITY_ERROR);
		}

		array_unshift($options, JHtml::_('select.option', '0', JText::_('COM_NENO_SELECT_GROUP')));

		return $options;
	}

	/**
	 * Return all translation statuses present.
	 *
	 * @return  array
	 */
	public static function getStatuses()
	{
		$translationStatesText                                                                     = array();
		$translationStatesText[ NenoContentElementTranslation::TRANSLATED_STATE ]                  = JText::_('COM_NENO_STATUS_TRANSLATED');
		$translationStatesText[ NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE ] = JText::_('COM_NENO_STATUS_QUEUED');
		$translationStatesText[ NenoContentElementTranslation::SOURCE_CHANGED_STATE ]              = JText::_('COM_NENO_STATUS_CHANGED');
		$translationStatesText[ NenoContentElementTranslation::NOT_TRANSLATED_STATE ]              = JText::_('COM_NENO_STATUS_NOT_TRANSLATED');

		// Create a new query object.
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('DISTINCT state')
			->from('`#__neno_content_element_translations`');

		$db->setQuery($query);
		$statuses = $db->loadArray();

		$translationStatuses = array();

		foreach ($statuses as $status)
		{
			$translationStatuses[ $status ] = $translationStatesText[ $status ];
		}

		return $translationStatuses;
	}

	/**
	 * Print server information
	 *
	 * @param   string|array $serverInformation Server information
	 *
	 * @return string
	 */
	public static function printServerInformation($serverInformation)
	{
		ob_start();

		if (is_array($serverInformation))
		{
			foreach ($serverInformation as $key => $name)
			{
				if (is_array($name))
				{
					echo "### ";
				}
				else
				{
					echo '    ';
				}

				echo $key;

				if (is_array($name))
				{
					echo " ###\r    ";
				}

				echo self::printServerInformation($name) . "\r    ";
			}
		}
		else
		{
			echo ': ' . $serverInformation;
		}

		return ob_get_clean();
	}

	/**
	 * Get information about PHP
	 *
	 * @return array
	 */
	public static function getServerInfo()
	{
		ob_start();
		$phpInfo                   = array();
		$xml                       = new SimpleXMLElement(file_get_contents(JPATH_ADMINISTRATOR . '/components/com_neno/neno.xml'));
		$phpInfo['neno_version']   = (string) $xml->version;
		$config                    = JFactory::getConfig();
		$phpInfo['neno_log']       = self::tailCustom($config->get('log_path') . '/neno_log.php', 100);
		$phpInfo['joomla_version'] = JVERSION;

		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array(
					'name',
					'IF(enabled = 1,' . $db->quote('Enabled') . ',' . $db->quote('Disabled') . ') AS status'
				)
			)
			->from('#__extensions');

		$db->setQuery($query);
		$phpInfo['extensions'] = $db->loadAssocList('name');
		$phpInfo['phpinfo']    = array();
		phpinfo(11);

		if (preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
		{
			$directive = false;

			foreach ($matches as $match)
			{
				if (!empty($match[2]) && $match[2] == 'Directive')
				{
					$directive = true;
				}

				if (mb_strlen($match[1]))
				{
					$phpInfo[ $match[1] ] = array();
				}
				elseif (isset($match[3]))
				{
					$keys1 = array_keys($phpInfo);

					if ($directive)
					{
						$phpInfo[ end($keys1) ][ $match[2] ] = isset($match[4]) ? array(
							'Local Value'  => $match[3],
							'Master Value' => $match[4]
						) : $match[3];
					}
					else
					{
						$phpInfo[ end($keys1) ][ $match[2] ] = isset($match[4]) ? array(
							$match[3],
							$match[4]
						) : $match[3];
					}
				}
				else
				{
					$keys1                    = array_keys($phpInfo);
					$phpInfo[ end($keys1) ][] = $match[2];
				}
			}
		}

		if (!empty($phpInfo))
		{
			foreach ($phpInfo as $name => $section)
			{
				if ($name != 'extensions')
				{
					if (is_array($section))
					{
						foreach ($section as $key => $val)
						{
							if (is_numeric($key))
							{
								unset($phpInfo[ $name ][ $key ]);
							}
						}
					}
				}
			}
		}

		return $phpInfo;
	}

	/**
	 * Read file from the end to the beginning
	 *
	 * @param   string $filepath File path
	 * @param   int    $lines    Lines
	 * @param   bool   $adaptive Adaptive flag
	 *
	 * @return bool|string
	 */
	public static function tailCustom($filepath, $lines = 1, $adaptive = true)
	{
		// Open file
		$f = @fopen($filepath, "rb");

		if ($f === false)
		{
			return false;
		}

		// Sets buffer size
		if (!$adaptive)
		{
			$buffer = 4096;
		}
		else
		{
			$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
		}

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n")
		{
			$lines--;
		}

		// Start reading
		$output = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0)
		{
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0)
		{
			// Find first newline and remove all text before that
			$output = substr($output, mb_strpos($output, "\n") + 1);
		}

		// Close file and return
		fclose($f);

		return trim($output);
	}

	/**
	 * Consolidate translation methods
	 *
	 * @param   int   $groupId                  Group Id
	 * @param   array $languages                Language list
	 * @param   bool  $deleteTranslationMethods Delete previous translation methods
	 *
	 * @return void
	 */
	public static function consolidateTranslationMethods($groupId, array $languages, $deleteTranslationMethods = false)
	{
		foreach ($languages as $language)
		{
			self::consolidateTranslationMethodsForLanguage($groupId, $language, $deleteTranslationMethods);
		}
	}

	/**
	 * Consolidate translations for a particular language
	 *
	 * @param   int    $groupId                  Group id
	 * @param   string $language                 Language to consolidate
	 * @param   bool   $deleteTranslationMethods If translations method should be deleted
	 */
	protected static function consolidateTranslationMethodsForLanguage($groupId, $language, $deleteTranslationMethods)
	{
		$db       = JFactory::getDbo();
		$subQuery = $db->getQuery(true);

		if ($deleteTranslationMethods)
		{
			$subQuery
				->select('DISTINCT tr.id')
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_fields AS f ON tr.content_id = f.id')
				->innerJoin('#__neno_content_element_tables AS t ON f.table_id = t.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = t.group_id')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId
					)
				);

			$query = $db->getQuery(true);
			$query
				->delete('#__neno_content_element_translation_x_translation_methods')
				->where('translation_id IN (' . (string) $subQuery . ')');

			$db->setQuery($query);
			$db->execute();

			$subQuery
				->clear()
				->select('DISTINCT tr.id')
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_language_strings AS ls ON tr.content_id = ls.id')
				->innerJoin('#__neno_content_element_language_files AS lf ON ls.languagefile_id = lf.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = lf.group_id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON g.id = gtm.group_id AND tr.language = gtm.lang')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId
					)
				);
			$query
				->clear('where')
				->where('translation_id IN (' . (string) $subQuery . ')');
			$db->setQuery($query);
			$db->execute();
		}
		else
		{
			$subQuery2 = $db->getQuery(true);
			$subQuery2
				->select('1')
				->from('#__neno_content_element_translation_x_translation_methods AS trtm')
				->innerJoin('#__neno_translation_methods AS tm ON trtm.translation_method_id = tm.id')
				->where(
					array(
						'trtm.translation_id = tr.id',
						'FIND_IN_SET(gtm.translation_method_id,tm.acceptable_follow_up_method_ids)'
					)
				);

			// For database strings
			$subQuery
				->select(
					array(
						'tr.id',
						'gtm.translation_method_id',
						'gtm.ordering'
					)
				)
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_fields AS f ON tr.content_id = f.id')
				->innerJoin('#__neno_content_element_tables AS t ON f.table_id = t.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = t.group_id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON g.id = gtm.group_id AND tr.language = gtm.lang')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId
					)
				);

			$query = 'REPLACE INTO #__neno_content_element_translation_x_translation_methods (translation_id,translation_method_id,ordering) (' . (string) $subQuery . ')';
			$db->setQuery($query);
			$db->execute();

			$subQuery
				->clear()
				->select(
					array(
						'tr.id',
						'gtm.translation_method_id',
						'gtm.ordering'
					)
				)
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_fields AS f ON tr.content_id = f.id')
				->innerJoin('#__neno_content_element_tables AS t ON f.table_id = t.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = t.group_id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON g.id = gtm.group_id AND tr.language = gtm.lang')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId,
						'gtm.ordering > 1',
						'EXISTS (' . (string) $subQuery2 . ' )'
					)
				);

			$query = 'REPLACE INTO #__neno_content_element_translation_x_translation_methods (translation_id,translation_method_id,ordering) (' . (string) $subQuery . ')';
			$db->setQuery($query);
			$db->execute();

			$subQuery
				->clear()
				->select(
					array(
						'tr.id',
						'gtm.translation_method_id',
						'gtm.ordering'
					)
				)
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_language_strings AS ls ON tr.content_id = ls.id')
				->innerJoin('#__neno_content_element_language_files AS lf ON ls.languagefile_id = lf.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = lf.group_id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON g.id = gtm.group_id AND tr.language = gtm.lang')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId
					)
				);

			$query = 'REPLACE INTO #__neno_content_element_translation_x_translation_methods (translation_id,translation_method_id,ordering) (' . (string) $subQuery . ')';
			$db->setQuery($query);
			$db->execute();

			$subQuery
				->clear()
				->select(
					array(
						'tr.id',
						'gtm.translation_method_id',
						'gtm.ordering'
					)
				)
				->from('#__neno_content_element_translations AS tr')
				->innerJoin('#__neno_content_element_language_strings AS ls ON tr.content_id = ls.id')
				->innerJoin('#__neno_content_element_language_files AS lf ON ls.languagefile_id = lf.id')
				->innerJoin('#__neno_content_element_groups AS g ON g.id = lf.group_id')
				->leftJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON g.id = gtm.group_id AND tr.language = gtm.lang')
				->where(
					array(
						'tr.state = ' . NenoContentElementTranslation::TRANSLATED_STATE,
						'tr.content_type = ' . $db->quote('db_string'),
						'tr.language = ' . $db->quote($language),
						'g.id = ' . (int) $groupId,
						'gtm.ordering > 1',
						'EXISTS (' . (string) $subQuery2 . ' )'
					)
				);

			$query = 'REPLACE INTO #__neno_content_element_translation_x_translation_methods (translation_id,translation_method_id,ordering) (' . (string) $subQuery . ')';
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Get configuration setting for default action when loading a string
	 *
	 * @return  int
	 */
	public static function getDefaultTranslateAction()
	{
		// Create a new query object.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('setting_value')
			->from('`#__neno_settings`')
			->where('setting_key = "default_translate_action"');

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get the current version installed
	 *
	 * @return string
	 */
	public static function getNenoVersion()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('manifest_cache')
			->from('#__extensions')
			->where('element = ' . $db->quote('pkg_neno'));

		$db->setQuery($query);
		$manifestCache = json_decode($db->loadResult(), true);

		return $manifestCache['version'];
	}

	/**
	 * Render infobox
	 *
	 * @return string
	 */
	public static function renderVersionInfoBox()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$displayData                 = new stdClass;
		$displayData->currentVersion = self::getNenoVersion();

		$query
			->select('version')
			->from('#__updates')
			->where('element = ' . $db->quote('pkg_neno'));

		$db->setQuery($query);
		$newVersion = $db->loadResult();

		if (!empty($newVersion))
		{
			$displayData->newVersion = $newVersion;
		}

		return JLayoutHelper::render('versionbox', $displayData, JPATH_NENO_LAYOUTS);
	}

	/**
	 * Convert component context into a table name
	 *
	 * @param $context
	 *
	 * @return bool
	 */
	public static function getTableNameBasedOnComponentContext($context)
	{
		$contextSupported = array(
			'com_content.article' => '#__content'
		);

		return isset($contextSupported[ $context ]) ? $contextSupported[ $context ] : false;
	}
}
