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
class NenoControllerGroupElement extends JControllerForm
{
	/**
	 * Constructor
	 *
	 * @param   array $config Constructor configuration
	 *
	 * @throws Exception
	 */
	public function __construct($config = array())
	{
		$this->view_list = 'groupselements';
		parent::__construct($config);
	}

	/**
	 * Generate content element file
	 *
	 * @return void
	 */
	public function downloadContentElementFile()
	{
		$input   = JFactory::getApplication()->input;
		$tableId = $input->getInt('table_id');

		/* @var $table NenoContentElementTable */
		$table = NenoContentElementTable::load($tableId, false, true);

		/* @var $tableObject stdClass */
		$tableObject = $table->prepareDataForView();

		// Make file name
		$tableName = str_replace('#__', '', $tableObject->table_name);

		$fileName                  = $tableName . '_contentelements.xml';
		$displayData               = array();
		$displayData['table_name'] = $tableName;
		$displayData['table']      = $tableObject;

		// Output XML
		header('Content-Type: application/xml; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');

		// Creating XML file (the right way to do it!)
		$nenoXml = new SimpleXMLElement('<neno />');
		$nenoXml->addAttribute('type', 'contentelement');
		$nenoXml->addChild('name', $tableName);
		$nenoXml->addChild('author', 'Neno - http://www.neno-translate.com');
		$nenoXml->addChild('version', '1.0.0');
		$nenoXml->addChild('description', 'Definition of the table ' . $tableName);
		$nenoXml->addChild('translate', $tableObject->translate);

		$reference = $nenoXml->addChild('reference');
		$reference->addAttribute('type', 'content');
		$tableNode = $reference->addChild('table');
		$tableNode->addAttribute('name', $tableName);
		$tableFilters = $table->getTableFilters();

		if (!empty($tableFilters))
		{
			$tableNode->addAttribute('filter', json_encode($tableFilters));
		}

		foreach ($tableObject->fields as $field)
		{
			$fieldNode = $tableNode->addChild('field', $field->field_name);
			$fieldNode->addAttribute('type', (in_array($field->field_name, $displayData['table']->primary_key)) ? 'referenceid' : 'text');
			$fieldNode->addAttribute('name', $field->field_name);
			$fieldNode->addAttribute('translate', $field->translate);
		}

		echo $nenoXml->asXML();

		JFactory::getApplication()->close();
	}
}
