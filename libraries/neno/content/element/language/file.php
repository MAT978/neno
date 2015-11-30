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
 * Class NenoContentElementLanguageFile
 *
 * @since  1.0
 */
class NenoContentElementLanguageFile extends NenoContentElement implements NenoContentElementInterface
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
	protected $filename;

	/**
	 * @var string
	 */
	protected $language;

	/**
	 * @var string
	 */
	protected $extension;

	/**
	 * @var array
	 */
	protected $languageStrings;

	/**
	 * @var bool
	 */
	protected $discovered;

	/**
	 * @var bool
	 */
	protected $translate;

	/**
	 * Constructor
	 *
	 * @param   mixed $data          File data
	 * @param   bool  $loadExtraData Load extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);

		$data = (array) $data;

		if (!empty($this->filename))
		{
			$languageFileData = explode('.', $this->filename);

			$this->language = $languageFileData[0];
		}

		if (!empty($data['groupId']))
		{
			$this->group = NenoContentElementGroup::load($data['groupId'], $loadExtraData);
		}

		if ($loadExtraData && !$this->isNew())
		{
			$this->loadExtraData();
		}
	}

	/**
	 * Load Extra data
	 *
	 * @return void
	 */
	protected function loadExtraData()
	{
		$workingLanguage = NenoHelper::getWorkingLanguage();
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);

		$query
			->select(
				array(
					'SUM(word_counter) AS counter',
					'tr.state'
				)
			)
			->from('#__neno_content_element_translations as tr')
			->innerJoin('#__neno_content_element_language_strings AS ls ON tr.content_id = ls.id')
			->innerJoin('#__neno_content_element_language_files AS lf ON lf.id = ls.languagefile_id')
			->where(
				array(
					'content_type = ' . $db->quote('lang_string'),
					'lf.id = ' . $this->getId(),
					'tr.language = ' . $db->quote($workingLanguage)
				)
			)
			->group('tr.state');

		$db->setQuery($query);
		$statistics = $db->loadAssocList('state');

		$wordCount               = new stdClass;
		$wordCount->untranslated = 0;
		$wordCount->queued       = 0;
		$wordCount->changed      = 0;
		$wordCount->translated   = 0;

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

		$wordCount->total = $wordCount->untranslated + $wordCount->queued + $wordCount->changed + $wordCount->translated;
		$this->wordCount  = $wordCount;
	}

	/**
	 * Get language
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Set language
	 *
	 * @param   string $language Language
	 *
	 * @return $this
	 */
	public function setLanguage($language)
	{
		$this->language = $language;

		return $this;
	}

	/**
	 * Get group
	 *
	 * @return NenoContentElementGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * Set group
	 *
	 * @param   NenoContentElementGroup $group Group
	 *
	 * @return $this
	 */
	public function setGroup($group)
	{
		$this->group = $group;

		return $this;
	}

	/**
	 * Get extension name
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Set extension name
	 *
	 * @param   string $extension Extension name
	 *
	 * @return $this
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;

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

		return $object;
	}

	/**
	 * Get language strings
	 *
	 * @return array
	 */
	public function getLanguageStrings()
	{
		if ($this->languageStrings == null)
		{
			$this->loadStringsFromFile();
		}

		return $this->languageStrings;
	}

	/**
	 * Load the strings from the language file
	 *
	 * @param   bool $onlyNew Get only new records
	 *
	 * @return bool True on success, false otherwise
	 */
	public function loadStringsFromFile($onlyNew = false)
	{
		$filePath           = $this->getFilePath();
		$overwrittenStrings = $this->loadStringsFromTemplateOverwrite();

		// Check if the file exists.
		if (file_exists($filePath) || !empty($overwrittenStrings))
		{
			$strings = array();

			if (file_exists($filePath))
			{
				$strings = NenoHelper::readLanguageFile($filePath);
			}

			// Merging these two arrays
			$strings = array_merge($strings, $overwrittenStrings);

			if ($strings !== false)
			{
				// Init the string array
				$this->languageStrings = array();

				// Loop through all the strings
				foreach ($strings as $constant => $string)
				{
					// If this language string exists already, let's load it
					$languageString = NenoContentElementLanguageString::load(array( 'constant'        => $constant,
					                                                                'languagefile_id' => $this->id
					));

					// If it's not, let's create it
					if (empty($languageString))
					{
						$languageString = new NenoContentElementLanguageString(
							array(
								'constant'   => $constant,
								'string'     => $string,
								'time_added' => new DateTime
							)
						);
					}

					$languageString->setLanguageFile($this);

					if (($languageString->isNew() && $onlyNew) || !$onlyNew)
					{
						$this->languageStrings[] = $languageString;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Get the file path based on the language and extension properties
	 *
	 * @return string
	 */
	public function getFilePath()
	{
		$filePath = JPATH_ROOT . "/language/$this->language/" . $this->getFileName();

		return $filePath;
	}

	/**
	 * Get filename
	 *
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Set filename
	 *
	 * @param   string $filename Filename
	 *
	 * @return $this
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;

		return $this;
	}

	/**
	 * Load strings from the template overwrite
	 *
	 * @return array
	 */
	public function loadStringsFromTemplateOverwrite()
	{
		$strings  = array();
		$template = NenoHelper::getFrontendTemplate();

		if (!empty($template))
		{
			$filePath = JPATH_ROOT . '/templates/' . $template . '/language/' . $this->language . '/' . $this->getFileName();

			if (file_exists($filePath))
			{
				$strings = NenoHelper::readLanguageFile($filePath);
			}
		}

		return $strings;
	}

	/**
	 * Discover the element
	 *
	 * @return bool True on success
	 */
	public function discoverElement()
	{
		NenoHelper::setSetupState(
			JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP_TABLE', $this->group->getGroupName(), $this->getFilename()), 2
		);

		// Check if there are children not discovered
		$languageString = NenoContentElementLanguageString::load(array( 'discovered'      => 0,
		                                                                '_limit'          => 1,
		                                                                'languagefile_id' => $this->id
		));

		if (empty($languageString))
		{
			$this
				->setDiscovered(true)
				->persist();
		}
		else
		{
			NenoSettings::set('installation_level', '2.2');
			NenoSettings::set('discovering_element_1.2', $this->id);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function persist()
	{
		if (parent::persist())
		{
			if (!empty($this->languageStrings))
			{
				/* @var $languageString NenoContentElementLanguageString */
				foreach ($this->languageStrings as $languageString)
				{
					$languageString
						->setLanguageFile($this)
						->persist();
				}
			}
		}

		return false;
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

	public function remove()
	{
		if ($this->loadStringsFromFile())
		{
			/* @var $languageString NenoContentElementLanguageString */
			foreach ($this->languageStrings as $languageString)
			{
				$languageString->remove();
			}
		}

		return parent::remove();
	}

	/**
	 * Get translation status
	 *
	 * @return boolean
	 */
	public function isTranslate()
	{
		return $this->translate;
	}

	/**
	 * Set translation status
	 *
	 * @param boolean $translate
	 *
	 * @return $this
	 */
	public function setTranslate($translate)
	{
		$this->translate = $translate;

		return $this;
	}
}
