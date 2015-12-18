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
JHtml::_('behavior.keepalive');

$next = JFactory::getApplication()->input->getString('next', 'dashboard');

?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<h2><?php echo JText::_('COM_NENO_SETTINGS_SET_WORKING_LANGUAGE'); ?></h2>

		<div class="clearfix">
			<?php foreach ($this->langs as $lang): ?>
				<a class="btn btn-large span2 <?php echo $lang->isInstalled == false ? 'not-ready' : ''; ?>"
					data-language="<?php echo $lang->lang_code; ?>"
					href="index.php?option=com_neno&task=setworkinglang&lang=<?php echo $lang->lang_code; ?>&next=<?php echo $next; ?>">
					<h4>
						<?php if (file_exists(JPATH_SITE . '/media/mod_languages/images/' . $lang->image . '.gif')): ?>
							<img src="<?php echo JUri::root() . 'media/mod_languages/images/' . $lang->image . '.gif'; ?>" />
						<?php endif; ?>
						<?php echo $lang->title_native; ?>
					</h4>
					<?php if ($lang->isInstalled == false): ?>
						<span
							class="setting-up-messsage"><?php echo JText::_('COM_NENO_LANGUAGE_SETTING_UP_MESSAGE'); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<script>
		jQuery(document).ready(function () {
			checkLanguages();
			jQuery('.not-ready').off('click').on('click', function (e) {
				e.preventDefault();
				alert('<?php echo JText::_('COM_NENO_LANGUAGE_IS_NOT_READY_YET_MESSAGE', true); ?>');
			});

			interval = setInterval(checkLanguages, 5000);
		});

		function checkLanguages() {
			var notReadyLanguages = jQuery('.not-ready');
			if (notReadyLanguages.length != 0) {
				notReadyLanguages.each(function () {
					var button = jQuery(this);
					jQuery.ajax({
						url    : 'index.php?option=com_neno&task=isLanguageInstalled',
						type   : 'POST',
						data   : {
							language: button.data('language')
						},
						success: function (data) {
							if (data == 'ok') {
								button.removeClass('not-ready');
								button.find('.setting-up-messsage').remove();
							}
						}
					});
				});
			} else if (typeof interval != 'undefined') {
				clearInterval(interval);
			}
		}
	</script>
<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>