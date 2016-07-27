<?php
/**
 * @package     Neno
 * @subpackage  Views
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * NenoViewPlgRender class
 *
 * @since  1.0
 */
class NenoViewPlgRender extends NenoView
{
	/**
	 * @var string
	 * @since 2.2.0
	 */
	protected $view;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return void
	 *
	 * @throws Exception This will happen if there are errors during the process to load the data
	 *
	 * @since 2.2.0
	 */
	public function display($tpl = null)
	{
		$this->view = $this->get('View');
		JToolbarHelper::title(JText::_('COM_NENO_DASHBOARD_TITLE'), 'screen');

		parent::display($tpl);
	}
}
