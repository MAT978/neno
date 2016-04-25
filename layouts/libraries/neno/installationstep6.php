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
<style>
	#task-messages {
		height: 200px;
		background-color: #f5f5f5;
		padding: 20px;
		color: #808080;
		overflow: auto;
	}

	.log-level-2 {
		margin-left: 20px;
		font-weight: bold;
		margin-top: 16px;
	}

	.log-level-3 {
		margin-left: 40px;
	}

	#proceed-button {
		margin-top: 15px;
	}
</style>

<div class="installation-step">
	<div class="installation-body span12">
		<div class="error-messages"></div>
		<div id="database-tables-wrapper">
			<h1><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_TITLE'); ?></h1>

			<p><?php echo JText::_('COM_NENO_INSTALLATION_INSTALLATION_STEP_6_SUBTITLE'); ?></p>
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
							<td><?php echo JHtml::_('select.genericlist', $module->languageModules[$language->lang_code]['modules'], '', '', 'id', 'title', $module->languageModules[$language->lang_code]['similar']->id); ?></td>
						<?php endforeach; ?>

					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<button type="button" class="btn no-data" id="proceed-button"
		        disabled>
			<?php echo JText::_('COM_NENO_INSTALLATION_WARNING_MESSAGE_PROCEED_BUTTON'); ?>
		</button>
	</div>

	<?php echo JLayoutHelper::render('installationbottom', 4, JPATH_NENO_LAYOUTS); ?>
</div>

<script>
	var tableFiltersCallback = refreshRecordCounter;

	resetDiscoveringVariables();

	jQuery('#proceed-button').off('click').on('click', function () {
		if (jQuery('#backup-created-checkbox').prop('checked')) {
			jQuery('#database-tables-wrapper').slideToggle(400, function () {
				jQuery('#warning-message').slideToggle();
				jQuery('#installation-wrapper').slideToggle();
			});

			interval = setInterval(checkStatus, 2000);

			Notification.requestPermission(function (perm) {
				if (perm == 'granted') {
					notifications = true;
				}
			});
		}

		jQuery.installation = false;

		sendDiscoveringContentStep();
	});

	jQuery('#backup-created-checkbox').on('click', function () {
		jQuery('#proceed-button').attr('disabled', !jQuery(this).prop('checked'));
	});

	jQuery('.record-refresher-btn').on('click', refreshRecordCounter);

</script>