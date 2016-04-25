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
class NenoControllerMoveElementConfirm extends JControllerAdmin
{
	/**
	 * Show confirmation before moving anything
	 *
	 * @return void
	 */
	public function show()
	{
		$input = JFactory::getApplication()->input;

		// Overwrite the view
		$input->set('view', 'moveelementconfirm');

		$groups = $input->get('groups', array(), 'array');
		$tables = $input->get('tables', array(), 'array');
		$files  = $input->get('files', array(), 'files');

		// If a group is selected then load all the elements from that group
		if (!empty($groups))
		{
			foreach ($groups as $groupId)
			{
				$group = NenoContentElementGroup::getGroup($groupId);

				// Handle tables
				$groupTables = $group->getTables();

				if (!empty($groupTables))
				{
					/* @var $groupTable NenoContentElementGroup */
					foreach ($groupTables as $groupTable)
					{
						// Add the table id to the tables array
						$tables[] = $groupTable->getId();
					}
				}

				// Handle files
				$groupFiles = $group->getLanguageFiles();

				if (!empty($groupFiles))
				{
					/* @var $groupFile NenoContentElementLanguageFile */
					foreach ($groupFiles as $groupFile)
					{
						// Add the file id to the files array
						$files[] = $groupFile->getId();
					}
				}
			}
		}

		// Remove duplicates
		array_unique($tables);
		array_unique($files);

		// Load table info
		if (!empty($tables))
		{
			foreach ($tables as $key => $table)
			{
				$tables[ $key ] = NenoContentElementTable::load($table);
			}
		}

		// Load files info
		if (!empty($files))
		{
			foreach ($files as $key => $file)
			{
				$files[ $key ] = NenoContentElementLanguageFile::load($file);
			}
		}

		// Show output
		// Get the view

		/* @var $view NenoViewMoveElementConfirm */
		$view         = $this->getView('MoveElementConfirm', 'html');
		$view->groups = NenoHelper::convertNenoObjectListToJobjectList(NenoHelper::getGroups());

		// Assign data from the model
		$view->tables = NenoHelper::convertNenoObjectListToJobjectList($tables);
		$view->files  = NenoHelper::convertNenoObjectListToJobjectList($files);

		// Display the view
		$view->display();
	}

	/**
	 * Move elements
	 *
	 * @return void
	 */
	public function move()
	{
		$input = JFactory::getApplication()->input;

		$groupId = $input->getInt('group_id');
		/* @var $group NenoContentElementGroup */
		$group  = NenoContentElementGroup::load($groupId);
		$tables = $input->get('tables', array(), 'array');
		$files  = $input->get('files', array(), 'files');

		$url = JRoute::_('index.php?option=com_neno&view=groupselements', false);

		// Ensure that group_id was set
		if (empty($groupId))
		{
			$this->setMessage(JText::_('COM_NENO_VIEW_MOVELEMENTCONFIRM_ERR_GROUP_REQUIRED'), 'error');
			$this->setRedirect($url);
			$this->redirect();
		}

		// Ensure that there is at least one table or file
		if (empty($tables) && empty($files))
		{
			$this->setRedirect($url);
			$this->redirect();
		}

		$msg = '';

		// Move tables
		if (count($tables) > 0)
		{
			foreach ($tables as $tableId)
			{
				/* @var $table NenoContentElementTable */
				$table = NenoContentElementTable::load($tableId, true, true);
				$table
					->setGroup($group)
					->persist();

				$table->consolidateTranslateMethodsForTranslations();
			}

			$msg .= JText::sprintf('COM_NENO_VIEW_MOVELEMENTCONFIRM_X_TABLES_MOVED', count($tables));

		}

		// Move files
		if (count($files) > 0)
		{
			foreach ($files as $fileId)
			{
				/* @var $file NenoContentElementLanguageFile */
				$file = NenoContentElementLanguageFile::load($fileId);
				$file
					->setGroup($group)
					->persist();

				$file->consolidateTranslateMethodsForTranslations();
			}

			$msg .= JText::sprintf('COM_NENO_VIEW_MOVELEMENTCONFIRM_X_FILES_MOVED', count($files));
		}

		$this->setMessage($msg);
		$this->setRedirect($url);
		$this->redirect();
	}

	/**
	 * Cancel action
	 *
	 * @return void
	 */
	public function cancel()
	{
		$url = JRoute::_('index.php?option=com_neno&view=groupselements', false);
		$this->setRedirect($url);
		$this->redirect();
	}
}
