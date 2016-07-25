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
class NenoModelProfessionalTranslations extends JModelList
{
	/**
	 * Get TC needed
	 *
	 * @return int
	 */
	public function getFundsNeeded()
	{
		$items = $this->getItems();
		$priceNeeded = 0;

		foreach ($items as $item){
			$priceNeeded += $item->euro_price;
		}

		return $priceNeeded;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$query
		  ->select(
			array(
			  'SUM(word_counter) AS words',
			  'trtm.translation_method_id',
			  'l.title_native',
			  'l.image',
			  'language'
			)
		  )
		  ->from('#__neno_content_element_translations AS tr')
		  ->innerJoin('#__neno_content_element_translation_x_translation_methods AS trtm ON trtm.translation_id = tr.id')
		  ->innerJoin('#__neno_translation_methods AS tm ON trtm.translation_method_id = tm.id')
		  ->leftJoin('#__languages AS l ON tr.language = l.lang_code')
		  ->where(
			array(
			  'state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
			  'NOT EXISTS (SELECT 1 FROM #__neno_jobs_x_translations AS jt WHERE tr.id = jt.translation_id)',
			  'tm.pricing_per_word <> 0',
			  'trtm.ordering = 1'
			)
		  )
		  ->group(
			array(
			  'trtm.translation_method_id',
			  'language'
			)
		  );

		return $query;
	}

	/**
	 * Get translator comment
	 *
	 * @return string|null
	 */
	public function getComment()
	{
		return NenoSettings::get('external_translators_notes');
	}

	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $key => $item)
		{
			$items[$key]->euro_price = $item->words * $this->getPrice($item->language);
		}

		return $items;
	}

	/**
	 *
	 *
	 * @param string $language
	 *
	 * @return mixed
	 */
	protected function getPrice($language)
	{
		return NenoHelper::getPriceByLanguagePair(NenoSettings::get('source_language'), $language);
	}
}
