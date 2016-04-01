<?php
/**
 * @package     Neno
 * @subpackage  Models
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;


/**
 * NenoModelGroupsElements class
 *
 * @since  1.0
 */
class NenoModelDashboard extends JModelList
{
	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function getItems()
	{
		return NenoHelper::getLanguageConfigurationData();
	}

	/**
	 * Get position field
	 *
	 * @return string
	 */
	public function getPositionField()
	{
		// Adding necessary files
		require_once JPATH_ADMINISTRATOR . '/components/com_templates/helpers/templates.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_modules/helpers/modules.php';
		JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/helpers/html');

		$language = JFactory::getLanguage();
		$language->load('com_modules', JPATH_BASE);
		$state     = 1;
		$positions = JHtml::_('modules.positions', 0, $state);

		// Add custom position to options
		$customGroupText = JText::_('COM_MODULES_CUSTOM_POSITION');

		// Build field
		$attr = array (
			'id'        => 'jform_position',
			'list.attr' => 'class="chzn-custom-value" '
				. 'data-custom_group_text="' . $customGroupText . '" '
				. 'data-no_results_text="' . JText::_('COM_MODULES_ADD_CUSTOM_POSITION') . '" '
				. 'data-placeholder="' . JText::_('COM_MODULES_TYPE_OR_SELECT_POSITION') . '" '
		);

		return JHtml::_('select.groupedlist', $positions, 'jform[position]', $attr);
	}

	/**
	 * Check if the language switcher has been published already
	 *
	 * @param   bool $createdAndPublished True to check whether the module has created and published or just created.
	 *
	 * @return bool
	 */
	public function getIsSwitcherPublished($createdAndPublished = true)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(1)
			->from('#__modules')
			->where('module = ' . $db->quote('mod_languages'));

		if ($createdAndPublished)
		{
			$query->where(
				array (
					'position <> \'\'',
					'published = 1 '
				)
			);
		}

		$db->setQuery($query);

		return $db->loadResult() == 1 || !NenoSettings::get('show_language_switcher_warning', 1);
	}
}
