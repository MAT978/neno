<?php

$compress    = ($argc > 1 && $argv[1] == '--compress');
$extractPath = dirname(__FILE__);
$folders     = folders($extractPath);
$folder      = array_pop($folders);
$packagePath = $extractPath . $folder;

// Neno Component folders
$componentPath = $packagePath . DIRECTORY_SEPARATOR . 'com_neno';

// Creating package
if (file_exists($componentPath))
{
	if (rmdirRecursive($componentPath) !== true)
	{
		return false;
	}
}

if (file_exists($packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard'))
{
	if (rmdirRecursive($packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard') !== true)
	{
		return false;
	}
}

if (file_exists($packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno'))
{
	if (rmdirRecursive($packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno') !== true)
	{
		return false;
	}
}

if (file_exists($packagePath . DIRECTORY_SEPARATOR . 'lib_neno'))
{
	if (rmdirRecursive($packagePath . DIRECTORY_SEPARATOR . 'lib_neno') !== true)
	{
		return false;
	}
}

if (file_exists($packagePath . DIRECTORY_SEPARATOR . 'packages'))
{
	if (rmdirRecursive($packagePath . DIRECTORY_SEPARATOR . 'packages') !== true)
	{
		return false;
	}
}

mkdir($packagePath . DIRECTORY_SEPARATOR . 'packages');

if (mkdir($componentPath, 0777, true) !== true)
{
	return false;
}

// Administrator
if (rename($extractPath . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_neno', $componentPath . DIRECTORY_SEPARATOR . 'back') !== true)
{
	return false;
}

// Languages
$languageFiles = files($extractPath . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'language', true);

foreach ($languageFiles as $languageFile)
{
	$languageFileName = basename($languageFile);
	list($language, ,) = explode('.', $languageFileName);

	if (endsWith($languageFile, 'com_neno.ini') || endsWith($languageFile, 'com_neno.sys.ini'))
	{
		if (!file_exists($componentPath . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language))
		{
			mkdir($componentPath . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language);
		}
		rename($languageFile, $componentPath . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $languageFileName);
	}
	elseif (endsWith($languageFile, 'mod_neno_dashboard.ini') || endsWith($languageFile, 'mod_neno_dashboard.sys.ini'))
	{
		if (!file_exists($packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language))
		{
			mkdir($packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language);
		}
		rename($languageFile, $packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $languageFileName);
	}
	else
	{
		if (!file_exists($packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language))
		{
			mkdir($packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language);
		}
		rename($languageFile, $packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $languageFileName);
	}
}
if (rename($extractPath . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'language', $componentPath . DIRECTORY_SEPARATOR . 'languages') !== true)
{
	return false;
}

// Front-end
if (rename($extractPath . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_neno', $componentPath . DIRECTORY_SEPARATOR . 'front') !== true)
{
	return false;
}

// Media files
if (rename($extractPath . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'neno', $componentPath . DIRECTORY_SEPARATOR . 'media') !== true)
{
	return false;
}

// Layouts
if (rename($extractPath . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'neno', $componentPath . DIRECTORY_SEPARATOR . 'layouts') !== true)
{
	return false;
}

// Cli
if (rename($extractPath . DIRECTORY_SEPARATOR . 'cli', $componentPath . DIRECTORY_SEPARATOR . 'cli') !== true)
{
	return false;
}

// Moving installation manifest
if (rename($componentPath . DIRECTORY_SEPARATOR . 'back' . DIRECTORY_SEPARATOR . 'neno.xml', $componentPath . DIRECTORY_SEPARATOR . 'neno.xml') !== true)
{
	return false;
}

// Moving installation script
if (rename($componentPath . DIRECTORY_SEPARATOR . 'back' . DIRECTORY_SEPARATOR . 'script.php', $componentPath . DIRECTORY_SEPARATOR . 'script.php') !== true)
{
	return false;
}

// Neno Plugin folder
if (rename($extractPath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'neno', $packagePath . DIRECTORY_SEPARATOR . 'plg_system_neno') !== true)
{
	return false;
}

// Neno module folder
if (rename($extractPath . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'mod_neno_dashboard', $packagePath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard') !== true)
{
	return false;
}

// Neno library folder
if (rename($extractPath . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'neno', $packagePath . DIRECTORY_SEPARATOR . 'lib_neno') !== true)
{
	return false;
}

// Deleting empty folders
if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'administrator') !== true)
{
	return false;
}

if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'components') !== true)
{
	return false;
}

if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'plugins') !== true)
{
	return false;
}

if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'libraries') !== true)
{
	return false;
}

if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'layouts') !== true)
{
	return false;
}

if (rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'media') !== true)
{
	return false;
}

$files = files($extractPath);

$rootFiles          = array(
  'pkg_neno.xml',
  'script.php',
  'codeception.yml',
  'composer.json',
  'RoboFile.php'
);
$noExtensionFolders = array(
  'tests',
  'media',
  'layouts',
  'cli',
  'packages',
  'vendor'
);

foreach ($files as $file)
{
	if (!in_array($file, $rootFiles))
	{
		unlink($extractPath . DIRECTORY_SEPARATOR . $file);
	}
}

$folders = folders($extractPath);

foreach ($folders as $extensionFolder)
{
	if (!in_array($extensionFolder, $noExtensionFolders))
	{
		// Parse installation file.

		if ($extensionFolder === 'mod_neno_dashboard')
		{
			$installationFileContent = file_get_contents($extractPath . DIRECTORY_SEPARATOR . $extensionFolder . DIRECTORY_SEPARATOR . 'mod_neno_dashboard.xml');
		}
		else
		{
			$installationFileContent = file_get_contents($extractPath . DIRECTORY_SEPARATOR . $extensionFolder . DIRECTORY_SEPARATOR . 'neno.xml');
		}

		if ($extensionFolder == 'lib_neno')
		{
			$libraryFolders   = folders($extractPath . DIRECTORY_SEPARATOR . $extensionFolder);
			$libraryStructure = '';

			foreach ($libraryFolders as $libraryFolder)
			{
				$libraryStructure .= '<folder>' . $libraryFolder . '</folder>' . "\r\t\t";
			}

			$libraryFiles = files($extractPath . DIRECTORY_SEPARATOR . $extensionFolder);

			foreach ($libraryFiles as $libraryFile)
			{
				if ($libraryFile != 'neno.xml')
				{
					$libraryStructure .= '<filename>' . $libraryFile . '</filename>' . "\r\t\t";
				}
			}

			$installationFileContent = str_replace('XXX_LIBRARY_STRUCTURE', $libraryStructure, $installationFileContent);
		}

		if ($extensionFolder === 'mod_neno_dashboard')
		{
			file_put_contents($extractPath . DIRECTORY_SEPARATOR . $extensionFolder . DIRECTORY_SEPARATOR . 'mod_neno_dashboard.xml', $installationFileContent);
		}
		else
		{
			file_put_contents($extractPath . DIRECTORY_SEPARATOR . $extensionFolder . DIRECTORY_SEPARATOR . 'neno.xml', $installationFileContent);
		}

	}
}

if ($compress)
{
	// Compress component
	$files   = files($componentPath, true);
	$zipData = array();

	foreach ($files as $file)
	{
		$zipData[] = array(
		  'name' => str_replace($componentPath . DIRECTORY_SEPARATOR, '', $file),
		  'file' => $file
		);
	}

	createZip($packagePath . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'com_neno.zip', $zipData);

	rmdirRecursive($componentPath);

	// Compress plugin
	$files   = files($extractPath . DIRECTORY_SEPARATOR . 'plg_system_neno', true);
	$zipData = array();

	foreach ($files as $file)
	{
		$zipData[] = array(
		  'name' => str_replace($extractPath . DIRECTORY_SEPARATOR . 'plg_system_neno' . DIRECTORY_SEPARATOR, '', $file),
		  'file' => $file
		);
	}

	createZip($packagePath . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'plg_system_neno.zip', $zipData);

	rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'plg_system_neno');

	// Compress module
	$files   = files($extractPath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard', true);
	$zipData = array();

	foreach ($files as $file)
	{
		$zipData[] = array(
		  'name' => str_replace($extractPath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard' . DIRECTORY_SEPARATOR, '', $file),
		  'file' => $file
		);
	}

	createZip($packagePath . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'mod_neno_dashboard.zip', $zipData);

	rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'mod_neno_dashboard');

	// Compress library
	$files   = files($extractPath . DIRECTORY_SEPARATOR . 'lib_neno', true);
	$zipData = array();

	foreach ($files as $file)
	{
		$zipData[] = array(
		  'name' => str_replace($extractPath . DIRECTORY_SEPARATOR . 'lib_neno' . DIRECTORY_SEPARATOR, '', $file),
		  'file' => $file
		);
	}

	createZip($packagePath . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'lib_neno.zip', $zipData);
	rmdirRecursive($extractPath . DIRECTORY_SEPARATOR . 'lib_neno');

	// Compress library
	$files   = files($extractPath, true);
	$zipData = array();

	foreach ($files as $file)
	{
		$zipData[] = array(
		  'name' => str_replace($extractPath . DIRECTORY_SEPARATOR, '', $file),
		  'file' => $file
		);
	}

	createZip($packagePath . DIRECTORY_SEPARATOR . 'pkg_neno.zip', $zipData);

	rmdirRecursive($packagePath . DIRECTORY_SEPARATOR . 'packages');
	unlink($packagePath . DIRECTORY_SEPARATOR . 'pkg_neno.xml');
	unlink($packagePath . DIRECTORY_SEPARATOR . 'script.php');
}

function folders($path)
{
	$it      = new DirectoryIterator($path);
	$folders = array();

	while ($it->valid())
	{
		if (is_dir($it->getPathname()) && !$it->isDot() && $it->getFilename() != '.git')
		{
			$folders[] = $it->getFilename();
		}

		$it->next();
	}

	return $folders;
}

function files($path, $recursive = false)
{
	$it    = new DirectoryIterator($path);
	$files = array();

	while ($it->valid())
	{
		if (is_file($it->getPathname()) && !$it->isDot() && $it->getFilename() != '.git')
		{
			$files[] = $recursive ? $it->getPathname() : $it->getFilename();
		}
		elseif (is_dir($it->getPathname()) && !$it->isDot() && $it->getFilename() != '.git' && $recursive)
		{
			$files = array_merge($files, files($it->getPathname(), $recursive));
		}

		$it->next();
	}

	return $files;
}

function createZip($path, $zipData)
{
	$zip = new ZipArchive;

	if ($zip->open($path, ZipArchive::CREATE) !== true)
	{
		echo "Error opening zip file";

		return false;
	}

	foreach ($zipData as $element)
	{
		if (!$zip->addFile($element['file'], $element['name']))
		{
			echo "Error adding " . $element['name'] . "\n";
		}
	}

	if ($zip->close() !== true)
	{
		echo "Error closing zip file";

		return false;
	}

	return true;
}

function rmdirRecursive($dir)
{
	$it = new RecursiveDirectoryIterator($dir);
	$it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($it as $file)
	{
		if ('.' === $file->getBasename() || '..' === $file->getBasename())
		{
			continue;
		}
		if ($file->isDir())
		{
			rmdir($file->getPathname());
		}
		else
		{
			unlink($file->getPathname());
		}
	}

	return rmdir($dir);
}

function endsWith($string, $suffix)
{
	return $suffix === "" || mb_strpos($string, $suffix, mb_strlen($string) - mb_strlen($suffix)) !== false;
}

