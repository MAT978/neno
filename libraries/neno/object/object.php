<?php
/**
 * @package     Neno
 * @subpackage  Job
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Class NenoObject
 *
 * @since  1.0
 */
abstract class NenoObject
{
	/**
	 * @var string
	 */
	private static $databaseTableNames = array();

	/**
	 * @var mixed
	 */
	protected $id;

	/**
	 * @var boolean
	 */
	protected $hasChanged;

	/**
	 * Constructor
	 *
	 * @param   mixed   $data          Content element data
	 * @param   boolean $loadExtraData LoadExtraData
	 */
	public function __construct($data, $loadExtraData = true)
	{
		// Create a JObject object to unify the way to assign the properties
		$data = $this->sanitizeConstructorData($data);

		// Create a reflection class to use it to dynamic properties loading
		$classReflection = $this->getClassReflectionObject();

		// Getting all the properties marked as 'protected'
		$properties = $classReflection->getProperties(ReflectionProperty::IS_PROTECTED);

		// Go through them and assign a value to them if they exist in the argument passed as parameter.
		foreach ($properties as $property)
		{
			if ($data->get($property->getName()) !== null)
			{
				$this->{$property->getName()} = $data->get($property->getName());
			}
		}

		$this->hasChanged;
	}

	/**
	 * Make sure that the data contains CamelCase properties
	 *
	 * @param   mixed $data Data to sanitize
	 *
	 * @return JObject
	 */
	protected function sanitizeConstructorData($data)
	{
		$data         = new JObject($data);
		$properties   = $data->getProperties();
		$sanitizeData = new JObject;

		foreach ($properties as $property => $value)
		{
			$sanitizeData->set(NenoHelper::convertDatabaseColumnNameToPropertyName($property), $value);
		}

		return $sanitizeData;
	}

	/**
	 * Get a ReflectionObject to work with it.
	 *
	 * @return ReflectionClass
	 */
	public function getClassReflectionObject()
	{
		$className       = get_called_class();
		$classReflection = new ReflectionClass($className);

		return $classReflection;
	}

	/**
	 * Generate WHERE clauses for load method
	 *
	 * @param JDatabaseQuery $query Database query object
	 * @param array          $fields
	 *
	 * @return JDatabaseQuery
	 */
	protected static function generateWhereClauses($query, $fields)
	{
		$db = JFactory::getDbo();
		foreach ($fields as $field => $value)
		{
			if (!in_array($field, array( '_order', '_limit', '_offset' )))
			{
				if (is_array($value))
				{
					// If this field has an special condition, let's apply it
					if (!empty($value['_field']) && !empty($value['_condition']) && !empty($value['_value']))
					{
						$query->where(
							$db->quoteName(NenoHelper::convertPropertyNameToDatabaseColumnName($value['_field'])) .
							' ' . $value['_condition'] . ' ' .
							$db->quote($value['_value'])
						);
					}
				}
				else
				{
					$query->where($db->quoteName(NenoHelper::convertPropertyNameToDatabaseColumnName($field)) . ' = ' . $db->quote($value));
				}
			}
		}

		return $query;
	}

	/**
	 * Generate other clauses for load method
	 *
	 * @param JDatabaseQuery $query Database query object
	 * @param array          $fields
	 *
	 * @return JDatabaseQuery
	 */
	protected static function generateOtherClauses($query, $fields)
	{
		// If order clauses have been set, let's process them
		if (!empty($fields['_order']))
		{
			foreach ($fields['_order'] as $orderField => $orderDirection)
			{
				$query->order($orderField . ' ' . $orderDirection);
			}
		}

		return $query;
	}

	/**
	 * This method parses load results
	 *
	 * @param array $fields        fields
	 * @param array $objects       Object list
	 * @param bool  $loadExtraData Load extra data flag
	 * @param bool  $loadParent    Load parent flag
	 *
	 * @return array|mixed
	 */
	protected static function parseLoadResult($fields, $objects, $loadExtraData, $loadParent)
	{
		$objectsData = array();

		if (empty($fields['_select']))
		{
			if (!empty($objects))
			{
				foreach ($objects as $object)
				{
					$objectsData[] = self::createObject($object, $loadExtraData, $loadParent);
				}
			}
		}
		else
		{
			$objectsData = $objects;
		}

		if (count($objectsData) == 1)
		{
			$objectsData = array_shift($objectsData);
		}

		return $objectsData;
	}

	/**
	 * This method creates an object for load method
	 *
	 * @param array $object        Object data
	 * @param bool  $loadExtraData LoadExtraData flag
	 * @param bool  $loadParent    loadParent flag
	 *
	 * @return stdClass
	 */
	protected static function createObject($object, $loadExtraData, $loadParent)
	{
		$className  = get_called_class();
		$objectData = new stdClass;

		foreach ($object as $key => $value)
		{
			$objectData->{NenoHelper::convertDatabaseColumnNameToPropertyName($key)} = $value;
		}

		return new $className($objectData, $loadExtraData, $loadParent);
	}

	/**
	 * Load element from the database
	 *
	 * @param   mixed   $pk            Could be the ID of the element or an array of clauses
	 * @param   boolean $loadExtraData Load extra data once the object has been created
	 * @param   boolean $loadParent    If the parent should be loaded
	 *
	 * @return stdClass|array
	 */
	public static function load($pk, $loadExtraData = true, $loadParent = false)
	{
		if (!is_array($pk))
		{
			$pk = array( 'id' => $pk );
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(empty($pk['_select']) ? '*' : $pk['_select'])
			->from(self::getDbTable());

		$query  = self::generateWhereClauses($query, $pk);
		$query  = self::generateOtherClauses($query, $pk);
		$offset = empty($pk['_offset']) ? 0 : (int) $pk['_offset'];
		$limit  = empty($pk['_limit']) ? 0 : (int) $pk['_limit'];

		$db->setQuery($query, $offset, $limit);
		$objects     = $db->loadAssocList();
		$objectsData = self::parseLoadResult($pk, $objects, $loadExtraData, $loadParent);

		return $objectsData;
	}

	/**
	 * Get the name of the database to persist the object
	 *
	 * @return string
	 */
	public static function getDbTable()
	{
		$className = get_called_class();

		if (empty(self::$databaseTableNames[ $className ]))
		{
			$classNameComponents = NenoHelper::splitCamelCaseString($className);
			$classNameComponents[ count($classNameComponents) - 1 ] .= 's';
			self::$databaseTableNames[ $className ] = '#__' . implode('_', $classNameComponents);
		}

		return self::$databaseTableNames[ $className ];
	}

	/**
	 * Remove the object from the database
	 *
	 * @return bool
	 */
	public function remove()
	{
		// Only perform this task if the ID is not null or 0.
		if (!empty($this->id))
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db = JFactory::getDbo();

			return $db->deleteObject(self::getDbTable(), $this->id);
		}

		return false;
	}

	/**
	 * Method to persist object in the database
	 *
	 * @return boolean
	 */
	public function persist()
	{
		$db   = JFactory::getDbo();
		$data = $this->toObject();

		if ($this->isNew())
		{
			$id       = $this->generateId();
			$data->id = $id;
			$result   = $db->insertObject(self::getDbTable(), $data, 'id');

			// Just assign an id if it's null
			if (empty($id))
			{
				$data->id = $db->insertid();
			}

			$this->id = $data->id;
		}
		else
		{
			$result = $db->updateObject(self::getDbTable(), $data, 'id');
		}

		return $result;
	}

	/**
	 * Create a JObject using the properties of the class.
	 *
	 * @param   bool $allFields         To get all the fields
	 * @param   bool $recursive         Make this method recursive
	 * @param   bool $convertToDatabase If the variables should be converted to database
	 *
	 * @return stdClass
	 */
	public function toObject($allFields = false, $recursive = false, $convertToDatabase = true)
	{
		$data = new stdClass;

		// Getting all the properties marked as 'protected'
		$properties = $this->getProperties($allFields);

		// Go through them and assign a value to them if they exist in the argument passed as parameter.
		foreach ($properties as $property)
		{
			if ($property !== 'hasChanged')
			{
				$propertyConverted = NenoHelper::convertPropertyNameToDatabaseColumnName($property);

				if ($this->{$property} instanceof NenoObject)
				{
					$data->{$propertyConverted} = $this->{$property}->toObject($allFields, false);
				}
				elseif (is_array($this->{$property}))
				{
					$dataArray = array();

					foreach ($this->{$property} as $key => $value)
					{
						if ($recursive && $value instanceof NenoObject)
						{
							$dataArray[ $key ] = $value->toObject($allFields, $recursive, $convertToDatabase);
						}
						elseif (!$value instanceof NenoObject)
						{
							$dataArray[ $key ] = $value;
						}
					}

					$data->{$propertyConverted} = $dataArray;
				}
				elseif ($this->{$property} instanceof Datetime)
				{
					$data->{$propertyConverted} = $this->{$property}->format('Y-m-d H:i:s');
				}
				else
				{
					$data->{$propertyConverted} = $this->{$property};
				}
			}
		}

		return $data;
	}

	public function toArray($allFields = false, $recursive = false, $convertToDatabase = true){
		return get_object_vars($this->toObject($allFields, $recursive, $convertToDatabase));
	}

	/**
	 * Get all the properties
	 *
	 * @param   bool $allFields All fields
	 *
	 * @return array
	 */
	public function getProperties($allFields = false)
	{
		$classReflection = $this->getClassReflectionObject();

		if (!$allFields)
		{
			$mainProperties = $classReflection->getProperties(ReflectionProperty::IS_PROTECTED);
		}
		else
		{
			$mainProperties = $classReflection->getProperties();
		}

		// Getting all the properties marked as 'protected'
		$properties = array_diff(
			$mainProperties,
			$classReflection->getProperties(ReflectionProperty::IS_STATIC)
		);

		$propertyNames = array();

		/* @var $property ReflectionProperty */
		foreach ($properties as $property)
		{
			$propertyNames[] = $property->getName();
		}

		return $propertyNames;
	}

	/**
	 * Check if a record is new or not.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return empty($this->id);
	}

	/**
	 * Generate an id for a new record
	 *
	 * @return mixed
	 */
	public function generateId()
	{
		return null;
	}

	/**
	 * Get Record Id
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Prepare this data to be presented into the view
	 *
	 * @return stdClass
	 */
	public function prepareDataForView()
	{
		return $this->toObject(true, true, false);
	}
}
