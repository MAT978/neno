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
$languages = $displayData->languages;

JHtml::_('behavior.keepalive');

?>
<table class="table table-striped">
	<tr>
		<th><?php echo JText::_('COM_NENO_INSTALL_LANGUAGES_LANGUAGE_NAME'); ?></th>
		<th><?php echo JText::_('JVERSION'); ?></th>
		<th></th>
	</tr>
	<?php foreach ($languages as $language): ?>
		<tr>
			<td><?php echo $language['name']; ?></td>
			<td><?php echo $language['version']; ?></td>
			<td class="action-cell" data-language-iso="<?php echo $language['iso'] ?>">
				<button type="button" class="btn" id="<?php echo $language['iso'] ?>"
				        data-update="<?php echo $language['update_id']; ?>"
				        data-language="<?php echo $language['iso'] ?>">
					<?php echo JText::_('JTOOLBAR_INSTALL'); ?>
				</button>
			</td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
	jQuery("#languages-modal").find('button[data-language]').click(function () {
		var button = jQuery(this);
		var iso = button.attr('data-language');
		button.hide();
		button.parent().append('<div class="loading loading-iso-' + button.attr('data-language') + '"></div>')
		jQuery.ajax({
			url: 'index.php?option=com_neno&task=installLanguage',
			data: {
				update: jQuery(this).data('update'),
				language: jQuery(this).data('language'),
				placement: '<?php echo $displayData->placement; ?>'
			},
			type: 'POST',
			success: function (html) {
				if (html != 'err') {
					var response = jQuery(html);
					var cell = jQuery('.action-cell [data-language-iso="' + iso + '"]');
					cell.html('<div class="icon-checkmark"></div>');
					response.insertBefore('#add-languages-button');
					<?php if($displayData->placement === 'installation'): ?>
					bindEventsInstallation(3);
					<?php else: ?>
					bindEvents();
					<?php endif; ?>

					loadMissingTranslationMethodSelectors();
					jQuery('.loading-iso-' + iso).removeClass('loading').addClass('icon-checkmark');
				} else {
					jQuery('.loading-iso-' + iso).removeClass('loading').addClass('icon-cancel-2');
				}
			}
		});
	});
</script>
