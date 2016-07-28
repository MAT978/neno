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

JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip');
$items = $displayData->languages;

?>

<style>
</style>

<div class="installation-step">
	<div class="installation-body span12">
		<div class="error-messages"></div>
		<h2><?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_TITLE'); ?></h2>

		<p><?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_MESSAGE'); ?></p>

		<?php foreach ($items as $item): ?>
			<?php echo JLayoutHelper::render('libraries.neno.languageconfiguration', $item); ?>
		<?php endforeach; ?>

		<button type="button" class="btn btn-primary"
			id="add-languages-button">
			<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_ADD_LANGUAGE_BUTTON'); ?>
		</button>

		<button type="button" class="btn btn-success next-step-button">
			<?php echo JText::_('COM_NENO_INSTALLATION_NEXT'); ?>
		</button>
		<img src="<?php echo JUri::root(); ?>/media/neno/images/loading_mini.gif" class="hide loading-spin" />
	</div>

	<?php echo JLayoutHelper::render('libraries.neno.installationbottom', 3); ?>
</div>

<script>
	jQuery('#add-languages-button').click(function () {
		jQuery.ajax({
			url    : 'index.php?option=com_neno&task=showInstallLanguagesModal&placement=installation',
			success: function (html) {
				var languagesModal = jQuery('#languages-modal');
				languagesModal.find('.modal-body').empty().append(html);
				languagesModal.find('.modal-header h3').html("<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_LANGUAGE_MODAL_TITLE', true); ?>");
				languagesModal.modal('show');
			}
		});
	});
	loadMissingTranslationMethodSelectors();
</script>
