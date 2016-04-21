<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

?>

<div class="installation-step">
	<div class="installation-body span12">
		<h2><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_TITLE'); ?></h2>

		<p>
			<?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_MESSAGE'); ?> <span class="icon-thumbs-up"></span>
		</p>

		<h3><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_TITLE'); ?></h3>

		<p><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_MESSAGE'); ?></p>

		<div>
			<p><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_LI_1'); ?></p>

			<p><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_LI_2'); ?></p>

			<p><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_LI_3'); ?></p>

			<p><?php echo JText::_('COM_NENO_INSTALLATION_SETUP_COMPLETE_WHAT_IS_NEXT_LI_4'); ?></p>
		</div>
	</div>

	<?php echo JLayoutHelper::render('installationbottom', 6, JPATH_NENO_LAYOUTS); ?>
</div>
