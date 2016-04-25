<?php
/**
 * @package     Neno
 * @subpackage  ContentElementModel
 *
 * @copyright   Copyright (c) 2016 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoContentElementModelTranslation
 *
 * @since  1.0
 */
class NenoContentElementModelTranslation extends NenoContentElementTranslation
{
	/**
	 * @var string
	 */
	public $typeAlias = 'com_neno.translation';

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Element data
	 * @param   bool  $loadExtraData Load extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);
	}


}
