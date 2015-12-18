<?php
/**
 * @package     Neno
 * @subpackage  Factory
 *
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoLanguageFile
 *
 * @since  1.0
 */
class NenoLanguageFile
{
	/**
	 * @var array
	 */
	protected $strings;

	/**
	 * @var string
	 */
	protected $language;

	/**
	 * @var string
	 */
	protected $extension;

	/**
	 * Constructor
	 *
	 * @param   string  $language    Language of the file
	 * @param   string  $extension   Extension that owns the file
	 * @param   boolean $loadStrings If the strings related to this file will be loaded
	 */
	public function __construct($language, $extension = null, $loadStrings = false)
	{
		$this->language  = $language;
		$this->extension = $extension;

		$this->strings = array();

		// If the loadString flag is activated, let's load the strings
		if ($loadStrings)
		{
			$this->loadStringsFromFile();
		}
	}

	/**
	 * Load the strings from the language file
	 *
	 * @return bool True on success, false otherwise
	 */
	public function loadStringsFromFile()
	{
		$filePath = $this->getFilePath();

		// Check if the file exists.
		if (file_exists($filePath))
		{
			$strings = NenoHelperFile::readLanguageFile($this->getFilePath());

			if ($strings !== false)
			{
				// Init the string array
				$this->strings = array();

				// Loop through all the strings and index them creating the key
				foreach ($strings as $key => $string)
				{
					$this->strings[ $this->extension . '.ini:' . $key ] = $string;
				}

				return true;
			}
		}

		return false;
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
	 * Return the file name of a particular language file
	 *
	 * @return string
	 */
	public function getFileName()
	{
		$fileName = $this->language . ".[EXTENSION]ini";
		$fileName = str_replace('[EXTENSION]', (is_null($this->extension) ? '' : $this->extension . '.'), $fileName);

		return $fileName;
	}

	/**
	 * Based on the file path, this method creates a NenoLanguageFile object.
	 *
	 * @param   string $filePath Language file path
	 *
	 * @return NenoLanguageFile
	 */
	public static function getLanguageFileBasedOnPath($filePath)
	{
		$fileName  = NenoHelper::getFileName($filePath);
		$fileParts = explode('.', $fileName);

		if (count($fileParts) > 1)
		{
			list($language, $extension) = $fileParts;
		}
		else
		{
			$language  = $fileParts[0];
			$extension = null;
		}

		$languageFile = new NenoLanguageFile($language, $extension, true);

		return $languageFile;
	}

	/**
	 * Get Language string
	 *
	 * @param   string $language Language JISO
	 * @param   string $key      Language string key
	 *
	 * @return bool|string
	 */
	public static function getLanguageString($language, $key)
	{
		$languageStringInfo = self::getLanguageFileStringInfoFromStringKey($key);

		// If there were errors extracting the information from the key, return false.
		if (empty($languageStringInfo['extension']) || empty($languageStringInfo['constant']))
		{
			return false;
		}

		$languageFile = self::openLanguageFile($language, $languageStringInfo['extension']);

		return $languageFile->getString($languageStringInfo['constant']);
	}

	/**
	 * Takes a string (used as key in internal arrays) and splits it into an array of information
	 * Example key: com_phocagallery.sys.ini:COM_PHOCAGALLERY_XML_DESCRIPTION
	 *
	 * @param   string $key Language file key
	 *
	 * @return array
	 */
	public static function getLanguageFileStringInfoFromStringKey($key)
	{
		$info = array();

		if (empty($key))
		{
			return $info;
		}

		// Split by : to separate file name and constant
		list($fileName, $info['constant']) = explode(':', $key);

		// Split the file name by . for additional information
		$fileParts         = explode('.', $fileName);
		$info['extension'] = $fileParts[0];

		// Add .sys and other file parts to the name
		foreach ($fileParts as $k => $filePart)
		{
			if ($k > 0 && $filePart != 'ini')
			{
				$info['extension'] .= '.' . $filePart;
			}
		}

		return $info;
	}

	/**
	 * Open a language file and read it.
	 *
	 * @param   string $language  Language (JISO)
	 * @param   string $extension Extension name
	 *
	 * @return NenoLanguageFile
	 */
	public static function openLanguageFile($language, $extension)
	{
		$languageFile = new NenoLanguageFile($language, $extension, true);

		return $languageFile;
	}

	/**
	 * Get a particular string based on its constant
	 *
	 * @param   string $constant String constant
	 *
	 * @return bool|string
	 */
	public function getString($constant)
	{
		// Making sure the constant is uppercase
		$constant = strtoupper($constant);

		if (isset($this->strings[ $constant ]))
		{
			return $this->strings[ $constant ];
		}

		return false;
	}

	/**
	 * Get all the languages files based on a language
	 *
	 * @param   string $language Language (JISO)
	 *
	 * @return array
	 */
	public static function getLanguagesFilesBasedOnLanguage($language)
	{
		$files   = array();
		$folders = self::getLanguageFileFolders(null, $language);

		if (!empty($folders))
		{
			foreach ($folders as $folder)
			{
				$files = array_merge($files, self::getLanguageFilesInPath($folder, $language));
			}
		}

		// Debug
		NenoLog::log('Found ' . count($files) . ' language files in ' . $language . '', 3);

		if (!empty($files))
		{
			$files = NenoHelper::removeCoreLanguageFilesFromArray($files, $language);
		}

		return $files;
	}

	/**
	 * Get all the folders that contain language files related to an extension and/or a language
	 *
	 * @param   null|string $extension Extension name or null if it's not specified
	 * @param   null|string $language  Language tag (JISO) or null it's not specified
	 *
	 * @return array
	 */
	protected static function getLanguageFileFolders($extension = null, $language = null)
	{
		$folders = array();

		// Always language first
		if (!is_null($language))
		{
			$folders[] = JPATH_SITE . '/language/' . $language . '/';
		}
		else
		{
			$folders[] = JPATH_SITE . '/language/';
		}

		// If extension is given then try to be more specific about where the folders may be (for performance)
		if (!empty($extension))
		{
			// Split extension name by _ to determine if it is a component, module or plugin
			$extensionParts = explode('_', $extension);

			$specificPath = '';

			if ($extensionParts[0] == 'com')
			{
				$specificPath = JPATH_SITE . '/components/' . $extension . '/language/';
			}
			else
			{
				if ($extensionParts[0] == 'mod')
				{
					$specificPath = JPATH_SITE . '/modules/' . $extension . '/language/';
				}
				else
				{
					if ($extensionParts[0] == 'plg')
					{
						$specificPath = JPATH_SITE . '/plugins/';
					}
				}
			}

			if (is_file($specificPath))
			{
				$folders[] = $specificPath;
			}
		}
		else
		{
			$folders[] = JPATH_SITE . '/components/';
			$folders[] = JPATH_SITE . '/modules/';
			$folders[] = JPATH_SITE . '/plugins/';
		}

		// Always template overwrite last
		$folders[] = JPATH_SITE . '/templates/';

		return $folders;
	}

	/**
	 * Get all the languages files in a particular path
	 *
	 * @param   string      $path             Path
	 * @param   null|string $language         Language tag
	 * @param   bool        $recursive        If a recursive search should be applied
	 * @param   bool        $ignoreJoomlaCore If Joomla core languages files should be ignored
	 *
	 * @return array
	 */
	protected static function getLanguageFilesInPath($path, $language = null, $recursive = true, $ignoreJoomlaCore = true)
	{
		if (NenoHelper::endsWith($path, '/'))
		{
			$path = substr($path, 0, mb_strlen($path) - 1);
		}

		jimport('joomla.filesystem.folder');

		if (is_null($language))
		{
			$filter = '\.ini$';
		}
		else
		{
			$filter = '^' . $language . '.*\.ini$';
		}

		NenoLog::log('Looking for language files in [' . $language . '] inside: ' . $path, 3);

		// Load list
		$files = JFolder::files($path, $filter, $recursive, true);

		// Remove Joomla core files if needed
		if ($ignoreJoomlaCore === true && !empty($files))
		{
			$files = self::removeCoreLanguageFilesFromArray($files, $language);
		}

		// Debug
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				NenoLog::log('Found file: ' . $file, 3);
			}
		}

		return $files === false ? array() : $files;
	}

	/**
	 * Takes an array of language files and filters out known language files shipped with Joomla
	 *
	 * @param   array  $files Files to translate
	 * @param   string $lang  Language tag
	 *
	 * @return array
	 */
	public static function removeCoreLanguageFilesFromArray($files, $lang)
	{
		$coreFiles = array(

			// Core components language files
			$lang . '.com_ajax.ini'
		,
			$lang . '.com_config.ini'
		,
			$lang . '.com_contact.ini'
		,
			$lang . '.com_content.ini'
		,
			$lang . '.com_finder.ini'
		,
			$lang . '.com_neno.ini'
		,
			$lang . '.com_mailto.ini'
		,
			$lang . '.com_media.ini'
		,
			$lang . '.com_messages.ini'
		,
			$lang . '.com_newsfeeds.ini'
		,
			$lang . '.com_search.ini'
		,
			$lang . '.com_tags.ini'
		,
			$lang . '.com_users.ini'
		,
			$lang . '.com_weblinks.ini'
		,
			$lang . '.com_wrapper.ini'
		,
			$lang . '.files_joomla.sys.ini'
		,
			$lang . '.finder_cli.ini'

			// Main language file
		,
			$lang . '.ini'

			// Libraries language files
		,
			$lang . '.lib_fof.sys.ini'
		,
			$lang . '.lib_idna_convert.sys.ini'
		,
			$lang . '.lib_joomla.ini'
		,
			$lang . '.lib_joomla.sys.ini'
		,
			$lang . '.lib_phpass.sys.ini'
		,
			$lang . '.lib_phpmailer.sys.ini'
		,
			$lang . '.lib_phputf8.sys.ini'
		,
			$lang . '.lib_simplepie.sys.ini'

			// Modules language files
		,
			$lang . '.mod_articles_archive.ini'
		,
			$lang . '.mod_articles_archive.sys.ini'
		,
			$lang . '.mod_articles_categories.ini'
		,
			$lang . '.mod_articles_categories.sys.ini'
		,
			$lang . '.mod_articles_category.ini'
		,
			$lang . '.mod_articles_category.sys.ini'
		,
			$lang . '.mod_articles_latest.ini'
		,
			$lang . '.mod_articles_latest.sys.ini'
		,
			$lang . '.mod_articles_news.ini'
		,
			$lang . '.mod_articles_news.sys.ini'
		,
			$lang . '.mod_articles_popular.ini'
		,
			$lang . '.mod_articles_popular.sys.ini'
		,
			$lang . '.mod_banners.ini'
		,
			$lang . '.mod_banners.sys.ini'
		,
			$lang . '.mod_breadcrumbs.ini'
		,
			$lang . '.mod_breadcrumbs.sys.ini'
		,
			$lang . '.mod_custom.ini'
		,
			$lang . '.mod_custom.sys.ini'
		,
			$lang . '.mod_feed.ini'
		,
			$lang . '.mod_feed.sys.ini'
		,
			$lang . '.mod_finder.ini'
		,
			$lang . '.mod_finder.sys.ini'
		,
			$lang . '.mod_footer.ini'
		,
			$lang . '.mod_footer.sys.ini'
		,
			$lang . '.mod_languages.ini'
		,
			$lang . '.mod_languages.sys.ini'
		,
			$lang . '.mod_login.ini'
		,
			$lang . '.mod_login.sys.ini'
		,
			$lang . '.mod_menu.ini'
		,
			$lang . '.mod_menu.sys.ini'
		,
			$lang . '.mod_random_image.ini'
		,
			$lang . '.mod_random_image.sys.ini'
		,
			$lang . '.mod_related_items.ini'
		,
			$lang . '.mod_related_items.sys.ini'
		,
			$lang . '.mod_search.ini'
		,
			$lang . '.mod_search.sys.ini'
		,
			$lang . '.mod_stats.ini'
		,
			$lang . '.mod_stats.sys.ini'
		,
			$lang . '.mod_syndicate.ini'
		,
			$lang . '.mod_syndicate.sys.ini'
		,
			$lang . '.mod_tags_popular.ini'
		,
			$lang . '.mod_tags_popular.sys.ini'
		,
			$lang . '.mod_tags_similar.ini'
		,
			$lang . '.mod_tags_similar.sys.ini'
		,
			$lang . '.mod_users_latest.ini'
		,
			$lang . '.mod_users_latest.sys.ini'
		,
			$lang . '.mod_weblinks.ini'
		,
			$lang . '.mod_weblinks.sys.ini'
		,
			$lang . '.mod_whosonline.ini'
		,
			$lang . '.mod_whosonline.sys.ini'
		,
			$lang . '.mod_wrapper.ini'
		,
			$lang . '.mod_wrapper.sys.ini'

			// Template language files
		,
			$lang . '.tpl_beezsss3.ini'
		,
			$lang . '.tpl_beez3.sys.ini'
		,
			$lang . '.tpl_beez3.ini'
		,
			$lang . '.tpl_protostar.ini'
		,
			$lang . '.tpl_protostar.sys.ini'

			// Template overrides that should be ignored
		,
			$lang . '.tpl_hathor.ini'
		,
			$lang . '.tpl_hathor.sys.ini'
		,
			$lang . '.tpl_isis.ini'
		,
			$lang . '.tpl_isis.sys.ini'
		);

		$validFiles = array();

		// Filter
		foreach ($files as $file)
		{
			$found = false;

			// Loop through all the core files
			for ($i = 0; $i < count($coreFiles) && !$found; $i++)
			{
				$strlen = mb_strlen($coreFiles[ $i ]);

				if (substr($file, mb_strlen($file) - $strlen, $strlen) == $coreFiles[ $i ])
				{
					$found = true;
				}
			}

			// If the file wasn't found, let's add it as a valid translatable file
			if (!$found)
			{
				$validFiles[] = $file;
			}
		}

		// Get new keys
		$files = array_values($files);

		return $files;
	}

	/**
	 * Set string to the language file
	 *
	 * @param   string $constant String constant
	 * @param   string $string   String text
	 *
	 * @return NenoLanguageFile
	 */
	public function setString($constant, $string)
	{
		$constant = strtoupper($constant);

		$this->strings[ $constant ] = $string;

		return $this;
	}

	/**
	 * Save the language file data into the file
	 *
	 * @return bool True on success
	 */
	public function saveStringsIntoFile()
	{
		// Save strings to a file
	}

	/**
	 * Get all the strings stored on this language file
	 *
	 * @return array
	 */
	public function getStrings()
	{
		return $this->strings;
	}

	/**
	 * Set the strings stored on this language file
	 *
	 * @param   array $strings Language strings
	 *
	 * @return NenoLanguageFile
	 */
	public function setStrings($strings)
	{
		$this->strings = $strings;

		return $this;
	}

	/**
	 * Get the language of this file
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Set the language of this file.
	 *
	 * @param   string $language Language
	 *
	 * @return NenoLanguageFile
	 */
	public function setLanguage($language)
	{
		$this->language = $language;

		return $this;
	}

	/**
	 * Get the extension name that owns this language file
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Set the name of the extension that owns this language files
	 *
	 * @param   string $extension Extension name
	 *
	 * @return NenoLanguageFile
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;

		return $this;
	}
}
