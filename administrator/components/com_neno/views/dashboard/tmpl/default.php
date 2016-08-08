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
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

// Include the CSS file
$version = NenoHelperBackend::getNenoVersion();
JHtml::stylesheet('media/neno/css/admin.css?v=' . $version);

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

$workingLanguage = NenoHelper::getWorkingLanguage();

?>

<script type="text/javascript">

	jQuery(document).ready(bindEvents);

	function isLangReady(lang) {
		jQuery.ajax({
			url: 'index.php?option=com_neno&task=dashboard.toggleLanguage&language=' + lang,
			complete: function (data) {
				setTimeout(isLangReady, 5000);
			}
		});
	}

	function bindEvents() {
		//Bind the loader into the new selector
		loadMissingTranslationMethodSelectors();
		jQuery('.configuration-button').off('click').on('click', function () {
			jQuery(this).siblings('.language-configuration').slideToggle('fast');
		});

		jQuery(".radio").off('change').on('change', function () {
			jQuery.ajax({
				url: 'index.php?option=com_neno&task=dashboard.toggleLanguage&language=' + jQuery(this).data('language')
			});
		});

		jQuery(".remove-language-button").off('click').on('click', function () {
			var result = confirm("<?php echo JText::_('COM_NENO_DASHBOARD_REMOVING_LANGUAGE_MESSAGE_1', true) ?>\n\n<?php echo JText::_('COM_NENO_DASHBOARD_REMOVING_LANGUAGE_MESSAGE_2', true); ?>");

			if (result) {
				jQuery(this).closest('.language-wrapper').slideUp();
				jQuery.ajax({
					url: 'index.php?option=com_neno&task=removeLanguage&language=' + jQuery(this).data('language')
				});
			}

		});

		jQuery('.not-ready').off('click').on('click', function (e) {
			e.preventDefault();
			alert('<?php echo JText::_('COM_NENO_LANGUAGE_IS_NOT_READY_YET_MESSAGE', true); ?>');
		});

		jQuery('.save-translator-comment').off('click').on('click', function () {
			var language = jQuery(this).data('language');

			jQuery.post(
			  'index.php?option=com_neno&task=saveExternalTranslatorsComment',
			  {
				  placement: 'language',
				  language: language,
				  comment: jQuery(".comment-to-translator[data-language='" + language + "']").val()
			  },
			  function (response) {

				  if (response == 'ok') {
					  var text = '<?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_LANGUAGE_EDIT', true); ?>';
					  text = text.replace('%s', language);
					  jQuery(".add-comment-to-translator-button[data-language='" + language + "']").html('<span class="icon-pencil"></span> ' + text);
				  }

				  jQuery('#addCommentFor' + language).modal('toggle');
			  }
			);
		});

		jQuery('#publish-module').off('click').on('click', function () {
			jQuery("input[name='task']").val('dashboard.publishSwitcher');
			jQuery('#adminForm').submit();

		})
	}

</script>

<form action="index.php?option=com_neno&view=dashboard" method="post"
      name="adminForm"
      id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo JHtml::_('form.token'); ?>
		<?php if (!$this->isLanguageSwitcherPublished): ?>
			<?php echo JLayoutHelper::render('libraries.neno.languageswitcheralert', $this->positionField); ?>
		<?php endif; ?>
		<?php if (!empty($this->menuItemAliasIssue)): ?>
			<?php echo NenoHelperIssue::renderIssue($this->menuItemAliasIssue[0]); ?>
		<?php endif; ?>
		<div class="languages-holder">
			<?php foreach ($this->items as $item): ?>
				<?php $item->placement = 'dashboard'; ?>
				<?php echo JLayoutHelper::render('libraries.neno.languageconfiguration', $item); ?>
			<?php endforeach; ?>
			<button type="button" class="btn btn-primary"
			        id="add-languages-button">
				<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_ADD_LANGUAGE_BUTTON'); ?>
			</button>
		</div>
	</div>
</form>
<div class="modal hide fade" id="languages-modal">
	<div class="modal-header">
		&nbsp;
		<button type="button" class="close" data-dismiss="modal"
		        aria-hidden="true">&times;</button>
	</div>
	<div class="modal-body" style="height: 400px"></div>
	<div class="modal-footer">
		<a href="#" class="btn"
		   data-dismiss="modal"><?php echo JText::_('JTOOLBAR_CLOSE'); ?></a>
	</div>
</div>

<div class="modal hide fade" id="translationMethodModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal"
		        aria-hidden="true">&times;</button>
		<h3><?php echo JText::_('COM_NENO_TRANSLATION_METHOD_MODAL_TITLE'); ?></h3>
	</div>
	<div class="modal-body">
		<p>
			<?php echo JText::_('COM_NENO_TRANSLATION_METHOD_MODAL_MESSAGE'); ?>
		</p>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn"
		   data-dismiss="modal"><?php echo JText::_('JNO'); ?></a>
		<button type="button"
		        class="btn btn-primary yes-btn"><?php echo JText::_('JYES'); ?></button>
	</div>
</div>

<script>
	jQuery('#add-languages-button').click(function () {
		jQuery.ajax({
			url: 'index.php?option=com_neno&task=showInstallLanguagesModal&placement=dashboard',
			success: function (html) {
				var modal = jQuery('#languages-modal');
				modal.find('.modal-body').empty().append(html);
				modal.find('.modal-header h3').html("<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_LANGUAGE_MODAL_TITLE', true); ?>");
				modal.modal('show');
			}
		});
	})
</script>
<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>
