<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * test Table class
 *
 * @since  1.0
 */
class NenoTableGroupElement extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  A database connector object
	 */
	public function __construct($db)
	{
		parent::__construct('#__neno_content_element_groups', 'id', $db);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed  $pk  Primary key
	 *
	 * @return  bool
	 */
	public function delete($pk = null)
	{
		/* @var $group NenoContentElementGroup */
		$group = NenoContentElementGroup::load($pk);

		return $group->remove();
	}
}
