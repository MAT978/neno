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
 * Class NenoContentElement
 *
 * @since  1.0
 */
abstract class NenoContentElement extends NenoObject
{
	/**
	 * Loads all the elements using its parent id and the parent Id value
	 *
	 * @param   string  $elementsTableName    Table Name
	 * @param   string  $parentColumnName     Parent column name
	 * @param   string  $parentId             Parent Id
	 * @param   boolean $transformProperties  If the properties should be transform to CamelCase
	 * @param   array   $extraWhereStatements Extra where statements
	 *
	 * @return array
	 */
	public static function getElementsByParentId(
		$elementsTableName,
		$parentColumnName,
		$parentId,
		$transformProperties = false,
		$extraWhereStatements = array()
	){
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($elementsTableName)
			->where($parentColumnName . ' = ' . intval($parentId));

		if (!empty($extraWhereStatements))
		{
			foreach ($extraWhereStatements as $extraWhereStatement)
			{
				$query->where($extraWhereStatement);
			}
		}

		$db->setQuery($query);

		$elements = $db->loadObjectList();

		if ($transformProperties)
		{
			$elementsCount = count($elements);
			for ($i = 0; $i < $elementsCount; $i++)
			{
				$data = new stdClass;

				$elementArray = get_object_vars($elements[ $i ]);

				foreach ($elementArray as $property => $value)
				{
					$data->{NenoHelper::convertDatabaseColumnNameToPropertyName($property)} = $value;
				}

				$elements[ $i ] = $data;
			}
		}

		return $elements;
	}

	/**
	 * Load element from the database
	 *
	 * @param   mixed $pk            it could be the ID of the element or an array of clauses
	 * @param   bool  $loadExtraData Load extra data
	 * @param   bool  $loadParent    Load parent
	 * @param   bool  $cache         Allows to cache the result
	 *
	 * @return mixed|array
	 */
	public static function load($pk, $loadExtraData = true, $loadParent = false, $cache = true)
	{
		$arguments = func_get_args();

		// Check if the argument is an array
		if (is_array($pk))
		{
			$arguments = $pk;
		}

		if ($cache)
		{
			$cacheId = NenoCache::getCacheId(get_called_class() . '.' . __FUNCTION__, $arguments);
			$data    = NenoCache::getCacheData($cacheId);

			if ($data === null)
			{
				$data = parent::load($pk, $loadExtraData, $loadParent);
				NenoCache::setCacheData($cacheId, $data);
			}
		}
		else
		{
			$data = parent::load($pk, $loadExtraData, $loadParent);
		}

		return $data;
	}

	/**
	 * Set that the content has changed
	 *
	 * @return $this
	 */
	public function contentHasChanged()
	{
		$this->hasChanged = true;

		return $this;
	}

	/**
	 * Generate word count object based on the element statistics
	 *
	 * @param array $statistics Element statistics
	 *
	 * @return stdClass
	 */
	protected function generateWordCountObjectByStatistics($statistics)
	{
		$wordCount               = new stdClass;
		$wordCount->total        = 0;
		$wordCount->untranslated = 0;
		$wordCount->translated   = 0;
		$wordCount->queued       = 0;
		$wordCount->changed      = 0;

		// Assign the statistics
		foreach ($statistics as $state => $data)
		{
			switch ($state)
			{
				case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
					$wordCount->untranslated = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
					$wordCount->queued = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
					$wordCount->changed = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::TRANSLATED_STATE:
					$wordCount->translated = (int) $data['counter'];
					break;
			}
		}

		return $wordCount;
	}

}
