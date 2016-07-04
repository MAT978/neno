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
			$groups      = NenoHelper::getGroups(false, true, $languageTag);
			$groupsList  = NenoSettings::get('installing_language_' . $languageTag, NULL);

			if ($groupsList === NULL)
			{
				$groupsList = array();
				/* @var $group NenoContentElementGroup */
				foreach ($groups as $group)
				{
					$groupsList[] = $group->getId();
				}
			}

			/* @var $group NenoContentElementGroup */
			foreach ($groups as $group)
			{
				if (in_array($group->getId(), $groupsList))
				{
					$group->generateContentForLanguage($languageTag);
					$index = array_search($group->getId(), $groupsList);

					if ($index !== false)
					{
						unset($groupsList[$index]);
					}

					NenoSettings::set('installing_language_' . $languageTag, $groupsList);
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

			NenoSettings::set('installing_language_' . $languageTag, NULL);
		}
	}
}
