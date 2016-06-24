<?php
/**
 * @package     Neno
 * @subpackage  Task
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoTaskWorkerLanguage
 *
 * @since  1.0
 */
class NenoTaskWorkerLanguage extends NenoTaskWorker
{
	/**
	 * Execute the task
	 *
	 * @param   array $taskData Task data
	 *
	 * @return bool True on success, false otherwise
	 */
	public function run($taskData)
	{
		if (!empty($taskData['language']))
		{
			$languageTag = $taskData['language'];
			JFactory::getApplication()
			  ->setUserState('com_neno.working_language', $languageTag);
			$groups                 = NenoHelper::getGroups(false);
			$mostTranslatedLanguage = NenoHelper::getMostTranslatedLanguage();

			/* @var $group NenoContentElementGroup */
			foreach ($groups as $group)
			{
				$group->copyTranslationMethodFromLanguage($mostTranslatedLanguage, $languageTag);
				$assignedTranslationMethods = $group->getAssignedTranslationMethods();
				if (!empty($assignedTranslationMethods))
				{
					$group->generateContentForLanguage($languageTag);
				}
			}
			
			// Publish language content
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
			  ->update('#__languages')
			  ->set('published = 1')
			  ->where('lang_code = ' . $db->quote($languageTag));
			$db->setQuery($query);
			$db->execute();
		}
	}
}
