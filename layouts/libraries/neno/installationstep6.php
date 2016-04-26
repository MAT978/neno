<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

//No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

?>

<div class="installation-step">
	<div class="installation-body span12">
		<div class="error-messages"></div>
		<div id="database-tables-wrapper">
			<h1><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_TITLE'); ?></h1>

			<p><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_SUBTITLE_1'); ?></p>
			<p><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_SUBTITLE_2'); ?></p>
			<p><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_SUBTITLE_3'); ?></p>
			<table class="table">
				<tr>
					<th><?php echo JText::sprintf('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_SOURCE_LANGUAGE'); ?></th>
					<?php foreach ($displayData->languages as $language): ?>
						<th><?php echo $language->title; ?> <img
							  src="<?php echo JUri::root(); ?>/media/mod_languages/images/<?php echo $language->image; ?>.gif"
							  style="margin-bottom: 3px;"></th>
					<?php endforeach; ?>
				</tr>
				<?php foreach ($displayData->modules as $module): ?>
					<tr>
						<td><?php echo $module->title; ?>
							(<?php echo $module->module; ?>)
						</td>
						<?php foreach ($displayData->languages as $language): ?>
							<td>
								<?php echo JHtml::_(
								  'select.genericlist',
								  $module->languageModules[$language->lang_code]['modules'],
								  'jform[module_' . $module->id . '_' . $language->lang_code . ']',
								  '', 'id', 'title',
								  (empty($module->languageModules[$language->lang_code]['similar'])) ? 'create' : $module->languageModules[$language->lang_code]['similar']->id
								); ?>
							</td>
						<?php endforeach; ?>

					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<button type="button" class="btn btn-success next-step-button">
			<?php echo JText::_('COM_NENO_INSTALLATION_PROCEED_BUTTON'); ?>
		</button>
	</div>

	<?php echo JLayoutHelper::render('installationbottom', 5, JPATH_NENO_LAYOUTS); ?>
</div>